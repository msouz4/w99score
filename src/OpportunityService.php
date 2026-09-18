<?php
require_once __DIR__ . '/SyncService.php';

class OpportunityService {
    private SyncService $sync;
    private PDO $pdo;

    public function __construct() {
        $this->sync = new SyncService();
        $this->pdo = getPDOConnection();
    }

    /**
     * Obtém as partidas agendadas/futuras para análise (dia específico ou próximas não iniciadas)
     */
    public function getTargetMatches(?string $date = null): array {
        if ($date === 'all') {
            $stmt = $this->pdo->prepare("
                SELECT * FROM matches 
                WHERE status = 'notstarted' OR status = 'inprogress'
                ORDER BY start_timestamp ASC
                LIMIT 80
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $targetDate = ($date && $date !== 'today') ? $date : date('Y-m-d');
        
        // 1. Tentar buscar partidas do dia especificado na tabela matches
        $stmt = $this->pdo->prepare("
            SELECT * FROM matches 
            WHERE (status = 'notstarted' OR status = 'inprogress')
              AND DATE(match_date) = ?
            ORDER BY start_timestamp ASC
        ");
        $stmt->execute([$targetDate]);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Se não houver jogos para a data exata e for a busca padrão (hoje), busca os próximos jogos não iniciados
        if (empty($matches) && (!$date || $date === 'today')) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM matches 
                WHERE status = 'notstarted' OR status = 'inprogress'
                ORDER BY start_timestamp ASC
                LIMIT 60
            ");
            $stmt->execute();
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $matches;
    }

    /**
     * Analisa todas as partidas para um mercado específico ou todos com curadoria refinada (Top ~25)
     */
    public function analyzeOpportunities(
        string $market = 'all', 
        ?string $date = null, 
        int $minConfidence = 65, 
        array $favoriteTeamIds = [],
        int $maxLimit = 25
    ): array {
        $matches = $this->getTargetMatches($date);
        $opportunities = [];

        foreach ($matches as $match) {
            $homeId = (int)$match['home_team_id'];
            $awayId = (int)$match['away_team_id'];

            if (!$homeId || !$awayId) continue;
            if (!empty($favoriteTeamIds) && !in_array($homeId, $favoriteTeamIds) && !in_array($awayId, $favoriteTeamIds)) {
                continue;
            }

            $matchEventId = (int)$match['sofascore_event_id'];
            $matchTimestamp = (int)($match['start_timestamp'] ?? 0);

            $homeStatsVenue = $this->sync->getTeamVenueStats($homeId, 'home', true, $matchEventId, $matchTimestamp);
            $homeStatsAll   = $this->sync->getTeamVenueStats($homeId, 'all', true, $matchEventId, $matchTimestamp);
            $awayStatsVenue = $this->sync->getTeamVenueStats($awayId, 'away', true, $matchEventId, $matchTimestamp);
            $awayStatsAll   = $this->sync->getTeamVenueStats($awayId, 'all', true, $matchEventId, $matchTimestamp);
            $h2hMatches     = $this->sync->getH2HMatches($homeId, $awayId, $matchEventId, $matchTimestamp);

            $totalHomeSample = count($homeStatsAll['matches'] ?? []);
            $totalAwaySample = count($awayStatsAll['matches'] ?? []);
            $homeVenueSample = count($homeStatsVenue['matches'] ?? []);
            $awayVenueSample = count($awayStatsVenue['matches'] ?? []);
            
            // Requer amostragem estatística mínima confiável (pelo menos 4 jogos disputados)
            if ($totalHomeSample < 4 || $totalAwaySample < 4) {
                continue;
            }
            $isLowSample = ($homeVenueSample < 4 || $awayVenueSample < 4 || $totalHomeSample < 6 || $totalAwaySample < 6);

            $evaluations = $this->evaluateMatchMarkets(
                $match, 
                $homeStatsVenue, 
                $homeStatsAll, 
                $awayStatsVenue, 
                $awayStatsAll, 
                $h2hMatches
            );

            foreach ($evaluations as $mKey => $eval) {
                if ($market !== 'all' && $market !== $mKey) {
                    if ($market === 'cartoes' && str_starts_with($mKey, 'cartoes_')) {
                        // allow
                    } elseif ($market === 'cantos' && str_starts_with($mKey, 'cantos_')) {
                        // allow
                    } elseif ($market === 'gols' && str_starts_with($mKey, 'gols_')) {
                        // allow
                    } elseif ($market === 'finalizacoes' && str_starts_with($mKey, 'finalizacoes_')) {
                        // allow
                    } elseif (($market === 'finalizacoes_casa' || $market === 'finalizacoes_fora') && str_starts_with($mKey, $market . '_')) {
                        // allow
                    } else {
                        continue;
                    }
                }

                if ($eval['confidence'] >= $minConfidence) {
                    $compositeScore = ($eval['confidence'] * 0.65) + (($eval['consistency_pct'] ?? 70) * 0.35);
                    if (!empty($eval['streak_badge'])) {
                        $compositeScore += 4;
                    }

                    // Prioriza mercados principais (Gols, Cantos, Finalizações, Ambos Marcam) sobre cartões no feed
                    $isCoreMarket = in_array($mKey, [
                        'ambos_marcam', 'gols_ft', 'gols_ht', 'gols_st', 
                        'cantos_ft', 'cantos_ht', 'cantos_st', 
                        'finalizacoes_ft', 'finalizacoes_ht', 'finalizacoes_st',
                        'finalizacoes_casa_ft', 'finalizacoes_casa_ht',
                        'finalizacoes_fora_ft', 'finalizacoes_fora_ht',
                        'favorito_vence'
                    ]);
                    if ($isCoreMarket) {
                        $compositeScore += 3;
                    }

                    $opportunities[] = array_merge([
                        'composite_score' => $compositeScore,
                        'event_id' => $match['sofascore_event_id'] ?: $match['id'],
                        'tournament_name' => $match['season_name'] ?: 'Futebol',
                        'tournament_id' => $match['tournament_id'],
                        'match_date' => $match['match_date'],
                        'start_timestamp' => $match['start_timestamp'],
                        'home_team' => [
                            'id' => $homeId,
                            'name' => $match['home_team_name'],
                            'logo' => "api.php?action=get_image&type=team&id={$homeId}"
                        ],
                        'away_team' => [
                            'id' => $awayId,
                            'name' => $match['away_team_name'],
                            'logo' => "api.php?action=get_image&type=team&id={$awayId}"
                        ],
                        'market_key' => $mKey,
                        'home_games_count' => $homeVenueSample,
                        'away_games_count' => $awayVenueSample,
                        'home_total_games' => $totalHomeSample,
                        'away_total_games' => $totalAwaySample,
                        'is_low_sample'    => $isLowSample,
                        'composite_score'  => $compositeScore,
                    ], $eval);
                }
            }
        }

        // Ordenar por score de assertividade decrescente
        usort($opportunities, function($a, $b) {
            return ($b['composite_score'] ?? $b['confidence']) <=> ($a['composite_score'] ?? $a['confidence']);
        });

        // Curadoria inteligente: no feed geral ('all'), limitar para no máximo 25 e max 2 mercados por jogo
        if ($market === 'all' && $maxLimit > 0) {
            $curated = [];
            $matchUsage = [];
            
            foreach ($opportunities as $opp) {
                $eventId = $opp['event_id'];
                $matchUsage[$eventId] = ($matchUsage[$eventId] ?? 0);
                
                // Permite no máximo 2 mercados por partida no feed geral para diversificar
                if ($matchUsage[$eventId] < 2) {
                    $curated[] = $opp;
                    $matchUsage[$eventId]++;
                }
                
                if (count($curated) >= $maxLimit) {
                    break;
                }
            }
            
            // Se ainda não atingiu o limite, completa com os melhores restantes
            if (count($curated) < $maxLimit) {
                foreach ($opportunities as $opp) {
                    if (!in_array($opp, $curated, true)) {
                        $curated[] = $opp;
                        if (count($curated) >= $maxLimit) break;
                    }
                }
            }
            
            return $curated;
        }

        // Se filtrou por mercado específico, retorna até $maxLimit melhores daquele mercado
        if ($maxLimit > 0 && count($opportunities) > $maxLimit) {
            return array_slice($opportunities, 0, $maxLimit);
        }

        return $opportunities;
    }

    /**
     * Avalia todos os mercados para um jogo específico usando recência ponderada e linhas de altíssima assertividade (88%-92%+)
     */
    private function evaluateMatchMarkets(
        array $match, 
        array $hVenue, 
        array $hAll, 
        array $aVenue, 
        array $aAll, 
        array $h2h
    ): array {
        $results = [];

        $hName = $match['home_team_name'];
        $aName = $match['away_team_name'];

        $hMatchesHome = $hVenue['matches'] ?? [];
        $aMatchesAway = $aVenue['matches'] ?? [];

        $hCountHome = count($hMatchesHome);
        $aCountAway = count($aMatchesAway);
        $hCountAll  = count($hAll['matches'] ?? []);
        $aCountAll  = count($aAll['matches'] ?? []);

        // Amostragem
        $isLowSample = ($hCountHome < 4 || $aCountAway < 4 || $hCountAll < 6 || $aCountAll < 6);

        // -------------------------------------------------------------
        // 1. AMBOS MARCAM (BTTS: SIM) - Sniper Edition (>=90% Win Rate)
        // -------------------------------------------------------------
        $hScored = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['home_score_ft'] ?? 0)) > 0);
        $hConceded = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['away_score_ft'] ?? 0)) > 0);
        $aScored = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['away_score_ft'] ?? 0)) > 0);
        $aConceded = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['home_score_ft'] ?? 0)) > 0);

        $hAvgScored = (float)($hVenue['goals']['feitos']['avg_ft'] ?? 0);
        $aAvgScored = (float)($aVenue['goals']['feitos']['avg_ft'] ?? 0);
        $hAvgConceded = (float)($hVenue['goals']['cedidos']['avg_ft'] ?? 0);
        $aAvgConceded = (float)($aVenue['goals']['cedidos']['avg_ft'] ?? 0);

        $probHomeScores = ($hScored['weighted_pct'] * 0.55) + ($aConceded['weighted_pct'] * 0.45);
        $probAwayScores = ($aScored['weighted_pct'] * 0.55) + ($hConceded['weighted_pct'] * 0.45);

        $bttsConfidence = round(($probHomeScores + $probAwayScores) / 2);
        $consistencyPct = round(($hScored['pct'] + $aScored['pct'] + $hConceded['pct'] + $aConceded['pct']) / 4);

        // Filtro Sniper Ultra Rigoroso:
        // Ambos os times marcam em >=80% e sofrem em >=70%, médias >= 1.35 gols e sem jejum recente
        $isBttsElite = ($hScored['pct'] >= 80 && $aScored['pct'] >= 75 && $hConceded['pct'] >= 70 && $aConceded['pct'] >= 70 && $hAvgScored >= 1.35 && $aAvgScored >= 1.25 && $hScored['streak'] >= 1 && $aScored['streak'] >= 1 && !$isLowSample);

        if (!$isBttsElite) {
            $bttsConfidence = min(58, $bttsConfidence);
        }

        if (!empty($h2h)) {
            $h2hBttsCount = 0;
            foreach ($h2h as $hm) {
                if ((int)$hm['home_score_ft'] > 0 && (int)$hm['away_score_ft'] > 0) $h2hBttsCount++;
            }
            $h2hBttsPct = round(($h2hBttsCount / count($h2h)) * 100);
            $bttsConfidence = round(($bttsConfidence * 0.75) + ($h2hBttsPct * 0.25));
        }

        if ($isLowSample) {
            $bttsConfidence = round($bttsConfidence * 0.80);
        }
        $bttsConfidence = min(98, max(30, $bttsConfidence));

        $bttsStreak = min($hScored['streak'], $aScored['streak']);
        $bttsBadge = ($bttsStreak >= 3) ? "🔥 Ambos marcando há {$bttsStreak} jogos seguidos" : (($consistencyPct >= 75) ? "🎯 Consistência {$consistencyPct}%" : null);

        $results['ambos_marcam'] = [
            'market_name' => 'Ambos Marcam',
            'market_tag' => 'Ambos Marcam: SIM',
            'confidence' => $bttsConfidence,
            'consistency_pct' => $consistencyPct,
            'streak_badge' => $bttsBadge,
            'recent_form' => $hScored['recent_form'],
            'rating' => $this->getRatingLabel($bttsConfidence),
            'badge_color' => '#10b981',
            'main_stat' => "{$bttsConfidence}% Probabilidade",
            'stat_summary' => [
                "{$hName} marca em casa: {$hScored['pct']}% (Média: {$hAvgScored} gols)",
                "{$aName} marca fora: {$aScored['pct']}% (Média: {$aAvgScored} gols)",
                "{$hName} sofre gols em casa: {$hConceded['pct']}%",
                "{$aName} sofre gols fora: {$aConceded['pct']}%"
            ],
            'description' => "O **{$hName}** marcou em {$hScored['pct']}% dos seus jogos em casa e o **{$aName}** marcou em {$aScored['pct']}% como visitante. O cruzamento ofensivo e defensivo confirma alta probabilidade para Ambos Marcam."
        ];

        // -------------------------------------------------------------
        // 2. CANTOS PRIMEIRO TEMPO (HT) - Sniper Edition (Over 2.5 / Over 3.5)
        // -------------------------------------------------------------
        $hHtCornersAvg = (float)($hVenue['corners']['total']['avg_ht'] ?? 0);
        $aHtCornersAvg = (float)($aVenue['corners']['total']['avg_ht'] ?? 0);
        $expHtCorners = round(($hHtCornersAvg + $aHtCornersAvg) / 2, 2);

        $hHtCornersMade = (float)($hVenue['corners']['feitos']['avg_ht'] ?? 0);
        $hHtCornersCed  = (float)($hVenue['corners']['cedidos']['avg_ht'] ?? 0);
        $aHtCornersMade = (float)($aVenue['corners']['feitos']['avg_ht'] ?? 0);
        $aHtCornersCed  = (float)($aVenue['corners']['cedidos']['avg_ht'] ?? 0);

        $hHtCornersValues = array_map(fn($m) => ((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0)), $hMatchesHome);
        $aHtCornersValues = array_map(fn($m) => ((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0)), $aMatchesAway);

        $hHtOver25 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0))) >= 3);
        $aHtOver25 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0))) >= 3);

        $hHtOver35 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0))) >= 4);
        $aHtOver35 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0))) >= 4);

        if ($expHtCorners >= 5.5 && $hHtOver35['pct'] >= 85 && $aHtOver35['pct'] >= 85) {
            $selectedHtCornersCond = ['h' => $hHtOver35, 'a' => $aHtOver35, 'line' => 3.5, 'tag' => 'Mais de 3.5 Cantos 1ºT', 'bench' => 5.5];
        } else {
            $selectedHtCornersCond = ['h' => $hHtOver25, 'a' => $aHtOver25, 'line' => 2.5, 'tag' => 'Mais de 2.5 Cantos 1ºT', 'bench' => 4.0];
        }

        $htConsistency = round(($selectedHtCornersCond['h']['pct'] + $selectedHtCornersCond['a']['pct']) / 2);
        $cornersHtConfidence = round((($selectedHtCornersCond['h']['weighted_pct'] + $selectedHtCornersCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expHtCorners / $selectedHtCornersCond['bench']) * 80) * 0.25));
        
        // Filtro de segurança: Se volume médio for baixo ou inconsistente, rebaixa confiança
        if ($expHtCorners < 4.2 || $selectedHtCornersCond['h']['pct'] < 78 || $selectedHtCornersCond['a']['pct'] < 78) {
            $cornersHtConfidence = min(59, $cornersHtConfidence);
        }
        if ($isLowSample) $cornersHtConfidence = round($cornersHtConfidence * 0.85);
        $cornersHtConfidence = min(98, max(30, $cornersHtConfidence));

        $htStreak = max($selectedHtCornersCond['h']['streak'], $selectedHtCornersCond['a']['streak']);
        $htBadge = ($htStreak >= 3) ? "🔥 Linha batida no 1ºT em {$htStreak} jogos seguidos" : (($htConsistency >= 75) ? "🎯 Consistência {$htConsistency}%" : null);

        $results['cantos_ht'] = [
            'market_name' => 'Cantos Primeiro Tempo',
            'market_tag' => $selectedHtCornersCond['tag'],
            'confidence' => $cornersHtConfidence,
            'consistency_pct' => $htConsistency,
            'streak_badge' => $htBadge,
            'recent_form' => $selectedHtCornersCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($cornersHtConfidence),
            'badge_color' => '#8b5cf6',
            'main_stat' => "Média {$expHtCorners} Cantos 1ºT",
            'stat_summary' => [
                "Média esperada no 1ºT: {$expHtCorners} escanteios",
                "{$hName} em casa: {$hHtCornersMade} feitos / {$hHtCornersCed} cedidos (1ºT)",
                "{$aName} fora: {$aHtCornersMade} feitos / {$aHtCornersCed} cedidos (1ºT)",
                "Taxa da linha {$selectedHtCornersCond['tag']}: {$selectedHtCornersCond['h']['pct']}% casa / {$selectedHtCornersCond['a']['pct']}% fora"
            ],
            'description' => "Projeção de **{$expHtCorners} escanteios no 1º Tempo**. O **{$hName}** gera {$hHtCornersMade} e cede {$hHtCornersCed} cantos no 1ºT em casa. A taxa de acerto na linha {$selectedHtCornersCond['tag']} é de {$htConsistency}%.",
            'line_config' => [
                'current_line' => $selectedHtCornersCond['line'],
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Cantos',
                'period_tag' => '1ºT',
                'expected_value' => $expHtCorners,
                'h_values' => $hHtCornersValues,
                'a_values' => $aHtCornersValues,
            ]
        ];

        // -------------------------------------------------------------
        // 3. CANTOS SEGUNDO TEMPO (ST) - Sniper Edition (Over 2.5 / Over 3.5)
        // -------------------------------------------------------------
        $hStCornersAvg = (float)($hVenue['corners']['total']['avg_st'] ?? 0);
        $aStCornersAvg = (float)($aVenue['corners']['total']['avg_st'] ?? 0);
        $expStCorners = round(($hStCornersAvg + $aStCornersAvg) / 2, 2);

        $hStCornersMade = (float)($hVenue['corners']['feitos']['avg_st'] ?? 0);
        $hStCornersCed  = (float)($hVenue['corners']['cedidos']['avg_st'] ?? 0);
        $aStCornersMade = (float)($aVenue['corners']['feitos']['avg_st'] ?? 0);
        $aStCornersCed  = (float)($aVenue['corners']['cedidos']['avg_st'] ?? 0);

        $hStCornersValues = array_map(fn($m) => max(0, (((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0))) - (((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0)))), $hMatchesHome);
        $aStCornersValues = array_map(fn($m) => max(0, (((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0))) - (((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0)))), $aMatchesAway);

        $hStOver25 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $cHt = ((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0));
            $cFt = ((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0));
            return max(0, $cFt - $cHt) >= 3;
        });

        $aStOver25 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $cHt = ((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0));
            $cFt = ((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0));
            return max(0, $cFt - $cHt) >= 3;
        });

        $hStOver35 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $cHt = ((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0));
            $cFt = ((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0));
            return max(0, $cFt - $cHt) >= 4;
        });

        $aStOver35 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $cHt = ((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0));
            $cFt = ((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0));
            return max(0, $cFt - $cHt) >= 4;
        });

        if ($expStCorners >= 5.5 && $hStOver35['pct'] >= 85 && $aStOver35['pct'] >= 85) {
            $selectedStCond = ['h' => $hStOver35, 'a' => $aStOver35, 'line' => 3.5, 'tag' => 'Mais de 3.5 Cantos 2ºT', 'bench' => 5.5];
        } else {
            $selectedStCond = ['h' => $hStOver25, 'a' => $aStOver25, 'line' => 2.5, 'tag' => 'Mais de 2.5 Cantos 2ºT', 'bench' => 4.0];
        }

        $stConsistency = round(($selectedStCond['h']['pct'] + $selectedStCond['a']['pct']) / 2);
        $cornersStConfidence = round((($selectedStCond['h']['weighted_pct'] + $selectedStCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expStCorners / $selectedStCond['bench']) * 80) * 0.25));
        
        if ($expStCorners < 4.2 || $selectedStCond['h']['pct'] < 78 || $selectedStCond['a']['pct'] < 78) {
            $cornersStConfidence = min(59, $cornersStConfidence);
        }
        if ($isLowSample) $cornersStConfidence = round($cornersStConfidence * 0.85);
        $cornersStConfidence = min(98, max(30, $cornersStConfidence));

        $stStreak = max($selectedStCond['h']['streak'], $selectedStCond['a']['streak']);
        $stBadge = ($stStreak >= 3) ? "🔥 Linha batida no 2ºT em {$stStreak} jogos seguidos" : (($stConsistency >= 75) ? "🎯 Consistência {$stConsistency}%" : null);

        $results['cantos_st'] = [
            'market_name' => 'Cantos Segundo Tempo',
            'market_tag' => $selectedStCond['tag'],
            'confidence' => $cornersStConfidence,
            'consistency_pct' => $stConsistency,
            'streak_badge' => $stBadge,
            'recent_form' => $selectedStCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($cornersStConfidence),
            'badge_color' => '#a855f7',
            'main_stat' => "Média {$expStCorners} Cantos 2ºT",
            'stat_summary' => [
                "Média esperada no 2ºT: {$expStCorners} escanteios",
                "{$hName} em casa no 2ºT: {$hStCornersMade} feitos / {$hStCornersCed} cedidos",
                "{$aName} fora no 2ºT: {$aStCornersMade} feitos / {$aStCornersCed} cedidos",
                "Taxa da linha {$selectedStCond['tag']}: {$selectedStCond['h']['pct']}% mandante / {$selectedStCond['a']['pct']}% visitante"
            ],
            'description' => "Projeção de **{$expStCorners} escanteios na etapa final**. Ambas as equipes sustentam volume seguro no 2º tempo com {$stConsistency}% de consistência histórica na linha.",
            'line_config' => [
                'current_line' => $selectedStCond['line'],
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Cantos',
                'period_tag' => '2ºT',
                'expected_value' => $expStCorners,
                'h_values' => $hStCornersValues,
                'a_values' => $aStCornersValues,
            ]
        ];

        // -------------------------------------------------------------
        // 4. CANTOS TEMPO INTEGRAL (FT) - Sniper Edition (Over 6.5 / Over 7.5 / Over 8.5)
        // -------------------------------------------------------------
        $hFtCornersAvg = (float)($hVenue['corners']['total']['avg_ft'] ?? 0);
        $aFtCornersAvg = (float)($aVenue['corners']['total']['avg_ft'] ?? 0);
        $expFtCorners = round(($hFtCornersAvg + $aFtCornersAvg) / 2, 2);

        $hFtCornersMade = (float)($hVenue['corners']['feitos']['avg_ft'] ?? 0);
        $hFtCornersCed  = (float)($hVenue['corners']['cedidos']['avg_ft'] ?? 0);
        $aFtCornersMade = (float)($aVenue['corners']['feitos']['avg_ft'] ?? 0);
        $aFtCornersCed  = (float)($aVenue['corners']['cedidos']['avg_ft'] ?? 0);

        $hFtCornersValues = array_map(fn($m) => ((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0)), $hMatchesHome);
        $aFtCornersValues = array_map(fn($m) => ((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0)), $aMatchesAway);

        $hFtOver65 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0))) >= 7);
        $aFtOver65 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0))) >= 7);

        $hFtOver75 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0))) >= 8);
        $aFtOver75 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0))) >= 8);

        $hFtOver85 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0))) >= 9);
        $aFtOver85 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0))) >= 9);

        if ($expFtCorners >= 11.5 && $hFtOver85['pct'] >= 85 && $aFtOver85['pct'] >= 85) {
            $selectedFtCornersCond = ['h' => $hFtOver85, 'a' => $aFtOver85, 'line' => 8.5, 'tag' => 'Mais de 8.5 Escanteios', 'bench' => 11.5];
        } elseif ($expFtCorners >= 10.2 && $hFtOver75['pct'] >= 80 && $aFtOver75['pct'] >= 80) {
            $selectedFtCornersCond = ['h' => $hFtOver75, 'a' => $aFtOver75, 'line' => 7.5, 'tag' => 'Mais de 7.5 Escanteios', 'bench' => 10.2];
        } else {
            $selectedFtCornersCond = ['h' => $hFtOver65, 'a' => $aFtOver65, 'line' => 6.5, 'tag' => 'Mais de 6.5 Escanteios', 'bench' => 8.5];
        }

        $ftConsistency = round(($selectedFtCornersCond['h']['pct'] + $selectedFtCornersCond['a']['pct']) / 2);
        $cornersFtConfidence = round((($selectedFtCornersCond['h']['weighted_pct'] + $selectedFtCornersCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expFtCorners / $selectedFtCornersCond['bench']) * 80) * 0.25));
        
        if ($expFtCorners < 8.6 || $selectedFtCornersCond['h']['pct'] < 78 || $selectedFtCornersCond['a']['pct'] < 78) {
            $cornersFtConfidence = min(59, $cornersFtConfidence);
        }
        if ($isLowSample) $cornersFtConfidence = round($cornersFtConfidence * 0.85);
        $cornersFtConfidence = min(98, max(30, $cornersFtConfidence));

        $ftStreak = max($selectedFtCornersCond['h']['streak'], $selectedFtCornersCond['a']['streak']);
        $ftBadge = ($ftStreak >= 3) ? "🔥 Linha batida em {$ftStreak} jogos seguidos" : (($ftConsistency >= 75) ? "🎯 Consistência {$ftConsistency}%" : null);

        $results['cantos_ft'] = [
            'market_name' => 'Cantos Tempo Integral',
            'market_tag' => $selectedFtCornersCond['tag'],
            'confidence' => $cornersFtConfidence,
            'consistency_pct' => $ftConsistency,
            'streak_badge' => $ftBadge,
            'recent_form' => $selectedFtCornersCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($cornersFtConfidence),
            'badge_color' => '#3b82f6',
            'main_stat' => "Média {$expFtCorners} Cantos FT",
            'stat_summary' => [
                "Média combinada FT: {$expFtCorners} escanteios",
                "{$hName} em casa: {$hFtCornersMade} feitos / {$hFtCornersCed} cedidos (Média: {$hFtCornersAvg})",
                "{$aName} fora: {$aFtCornersMade} feitos / {$aFtCornersCed} cedidos (Média: {$aFtCornersAvg})",
                "Taxa da linha {$selectedFtCornersCond['tag']}: {$selectedFtCornersCond['h']['pct']}% casa / {$selectedFtCornersCond['a']['pct']}% fora"
            ],
            'description' => "Expectativa de **{$expFtCorners} escanteios totais** na partida. O **{$hName}** sustenta média de {$hFtCornersAvg} cantos em casa e o **{$aName}** apresenta {$aFtCornersAvg} fora.",
            'line_config' => [
                'current_line' => $selectedFtCornersCond['line'],
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Escanteios',
                'period_tag' => 'FT',
                'expected_value' => $expFtCorners,
                'h_values' => $hFtCornersValues,
                'a_values' => $aFtCornersValues,
            ]
        ];

        // -------------------------------------------------------------
        // 5. GOLS PRIMEIRO TEMPO (HT) - Sniper Edition (Over 0.5 / Over 1.5)
        // -------------------------------------------------------------
        $hHtGoalsAvg = (float)($hVenue['goals']['total']['avg_ht'] ?? 0);
        $aHtGoalsAvg = (float)($aVenue['goals']['total']['avg_ht'] ?? 0);
        $expHtGoals = round(($hHtGoalsAvg + $aHtGoalsAvg) / 2, 2);

        $hHtGoalsValues = array_map(fn($m) => ((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0)), $hMatchesHome);
        $aHtGoalsValues = array_map(fn($m) => ((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0)), $aMatchesAway);

        $hHtGoalsOver05 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0))) >= 1);
        $aHtGoalsOver05 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0))) >= 1);

        $hHtGoalsOver15 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0))) >= 2);
        $aHtGoalsOver15 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0))) >= 2);

        $isGoalsHtOver15 = ($expHtGoals >= 2.10 && $hHtGoalsOver15['pct'] >= 85 && $aHtGoalsOver15['pct'] >= 85);
        $selectedGoalsHtCond = $isGoalsHtOver15 ? ['h' => $hHtGoalsOver15, 'a' => $aHtGoalsOver15, 'line' => 1.5, 'tag' => 'Mais de 1.5 Gols no 1ºT', 'bench' => 2.10]
                                                : ['h' => $hHtGoalsOver05, 'a' => $aHtGoalsOver05, 'line' => 0.5, 'tag' => 'Mais de 0.5 Gols no 1ºT', 'bench' => 1.25];

        $goalsHtConsistency = round(($selectedGoalsHtCond['h']['pct'] + $selectedGoalsHtCond['a']['pct']) / 2);
        $goalsHtConfidence = round((($selectedGoalsHtCond['h']['weighted_pct'] + $selectedGoalsHtCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expHtGoals / $selectedGoalsHtCond['bench']) * 80) * 0.25));
        
        // Filtro de segurança para Gols 1ºT
        if ($expHtGoals < 1.25 || $selectedGoalsHtCond['h']['pct'] < 80 || $selectedGoalsHtCond['a']['pct'] < 80) {
            $goalsHtConfidence = min(59, $goalsHtConfidence);
        }
        if ($isLowSample) $goalsHtConfidence = round($goalsHtConfidence * 0.85);
        $goalsHtConfidence = min(98, max(30, $goalsHtConfidence));

        $gHtStreak = max($selectedGoalsHtCond['h']['streak'], $selectedGoalsHtCond['a']['streak']);
        $gHtBadge = ($gHtStreak >= 3) ? "🔥 Gol no 1ºT em {$gHtStreak} jogos seguidos" : (($goalsHtConsistency >= 80) ? "🎯 Consistência {$goalsHtConsistency}%" : null);

        $results['gols_ht'] = [
            'market_name' => 'Gols Primeiro Tempo',
            'market_tag' => $selectedGoalsHtCond['tag'],
            'confidence' => $goalsHtConfidence,
            'consistency_pct' => $goalsHtConsistency,
            'streak_badge' => $gHtBadge,
            'recent_form' => $selectedGoalsHtCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($goalsHtConfidence),
            'badge_color' => '#06b6d4',
            'main_stat' => "{$goalsHtConfidence}% Taxa Gol 1ºT",
            'stat_summary' => [
                "Média de gols no 1ºT: {$expHtGoals} gols",
                "{$hName} em casa: {$selectedGoalsHtCond['h']['pct']}% na linha {$selectedGoalsHtCond['tag']}",
                "{$aName} fora: {$selectedGoalsHtCond['a']['pct']}% na linha {$selectedGoalsHtCond['tag']}",
                "Média de gols 1ºT: {$hHtGoalsAvg} mandante / {$aHtGoalsAvg} visitante"
            ],
            'description' => "Em **{$selectedGoalsHtCond['h']['pct']}%** das partidas do **{$hName}** em casa e **{$selectedGoalsHtCond['a']['pct']}%** do **{$aName}** fora ocorreu gol no 1º Tempo.",
            'line_config' => [
                'current_line' => $selectedGoalsHtCond['line'],
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Gols',
                'period_tag' => '1ºT',
                'expected_value' => $expHtGoals,
                'h_values' => $hHtGoalsValues,
                'a_values' => $aHtGoalsValues,
            ]
        ];

        // -------------------------------------------------------------
        // 6. GOLS SEGUNDO TEMPO (ST) - Sniper Edition (Over 0.5 / Over 1.5)
        // -------------------------------------------------------------
        $hStGoalsAvg = (float)($hVenue['goals']['total']['avg_st'] ?? 0);
        $aStGoalsAvg = (float)($aVenue['goals']['total']['avg_st'] ?? 0);
        $expStGoals = round(($hStGoalsAvg + $aStGoalsAvg) / 2, 2);

        $hStGoalsValues = array_map(fn($m) => max(0, (((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0))) - (((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0)))), $hMatchesHome);
        $aStGoalsValues = array_map(fn($m) => max(0, (((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0))) - (((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0)))), $aMatchesAway);

        $hStGoalsOver05 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $gHt = ((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0));
            $gFt = ((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0));
            return max(0, $gFt - $gHt) >= 1;
        });

        $aStGoalsOver05 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $gHt = ((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0));
            $gFt = ((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0));
            return max(0, $gFt - $gHt) >= 1;
        });

        $hStGoalsOver15 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $gHt = ((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0));
            $gFt = ((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0));
            return max(0, $gFt - $gHt) >= 2;
        });

        $aStGoalsOver15 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $gHt = ((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0));
            $gFt = ((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0));
            return max(0, $gFt - $gHt) >= 2;
        });

        $isGoalsStOver15 = ($expStGoals >= 2.20 && $hStGoalsOver15['pct'] >= 85 && $aStGoalsOver15['pct'] >= 85);
        $selectedGoalsStCond = $isGoalsStOver15 ? ['h' => $hStGoalsOver15, 'a' => $aStGoalsOver15, 'line' => 1.5, 'tag' => 'Mais de 1.5 Gols no 2ºT', 'bench' => 2.20]
                                                : ['h' => $hStGoalsOver05, 'a' => $aStGoalsOver05, 'line' => 0.5, 'tag' => 'Mais de 0.5 Gols no 2ºT', 'bench' => 1.25];

        $goalsStConsistency = round(($selectedGoalsStCond['h']['pct'] + $selectedGoalsStCond['a']['pct']) / 2);
        $goalsStConfidence = round((($selectedGoalsStCond['h']['weighted_pct'] + $selectedGoalsStCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expStGoals / $selectedGoalsStCond['bench']) * 80) * 0.25));
        
        if ($expStGoals < 1.30 || $selectedGoalsStCond['h']['pct'] < 80 || $selectedGoalsStCond['a']['pct'] < 80) {
            $goalsStConfidence = min(59, $goalsStConfidence);
        }
        if ($isLowSample) $goalsStConfidence = round($goalsStConfidence * 0.85);
        $goalsStConfidence = min(98, max(30, $goalsStConfidence));

        $gStStreak = max($selectedGoalsStCond['h']['streak'], $selectedGoalsStCond['a']['streak']);
        $gStBadge = ($gStStreak >= 3) ? "🔥 Gol no 2ºT em {$gStStreak} jogos seguidos" : (($goalsStConsistency >= 80) ? "🎯 Consistência {$goalsStConsistency}%" : null);

        $results['gols_st'] = [
            'market_name' => 'Gols Segundo Tempo',
            'market_tag' => $selectedGoalsStCond['tag'],
            'confidence' => $goalsStConfidence,
            'consistency_pct' => $goalsStConsistency,
            'streak_badge' => $gStBadge,
            'recent_form' => $selectedGoalsStCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($goalsStConfidence),
            'badge_color' => '#14b8a6',
            'main_stat' => "Média {$expStGoals} Gols 2ºT",
            'stat_summary' => [
                "Média de gols no 2ºT: {$expStGoals} gols",
                "{$hName} em casa: {$selectedGoalsStCond['h']['pct']}% na linha {$selectedGoalsStCond['tag']}",
                "{$aName} fora: {$selectedGoalsStCond['a']['pct']}% na linha {$selectedGoalsStCond['tag']}",
                "Gols 2ºT Feitos/Cedidos: Mandante {$hVenue['goals']['feitos']['avg_st']} | Visitante {$aVenue['goals']['cedidos']['avg_st']}"
            ],
            'description' => "Projeção de **{$expStGoals} gols na segunda etapa**. Histórico consistente para a linha {$selectedGoalsStCond['tag']}.",
            'line_config' => [
                'current_line' => $selectedGoalsStCond['line'],
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Gols',
                'period_tag' => '2ºT',
                'expected_value' => $expStGoals,
                'h_values' => $hStGoalsValues,
                'a_values' => $aStGoalsValues,
            ]
        ];

        // -------------------------------------------------------------
        // 7. GOLS TEMPO INTEGRAL (FT) - Sniper Edition (Over 1.5 / Over 2.5)
        // -------------------------------------------------------------
        $hFtGoalsAvg = (float)($hVenue['goals']['total']['avg_ft'] ?? 0);
        $aFtGoalsAvg = (float)($aVenue['goals']['total']['avg_ft'] ?? 0);
        $expFtGoals = round(($hFtGoalsAvg + $aFtGoalsAvg) / 2, 2);

        $hFtGoalsValues = array_map(fn($m) => ((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0)), $hMatchesHome);
        $aFtGoalsValues = array_map(fn($m) => ((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0)), $aMatchesAway);

        $hFtOver15 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0))) >= 2);
        $aFtOver15 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0))) >= 2);

        $hFtOver25 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0))) >= 3);
        $aFtOver25 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0))) >= 3);

        $isGoalsFtOver25 = ($expFtGoals >= 3.20 && $hFtOver25['pct'] >= 85 && $aFtOver25['pct'] >= 85);
        $selectedGoalsFtCond = $isGoalsFtOver25 ? ['h' => $hFtOver25, 'a' => $aFtOver25, 'line' => 2.5, 'tag' => 'Mais de 2.5 Gols FT', 'bench' => 3.20]
                                                : ['h' => $hFtOver15, 'a' => $aFtOver15, 'line' => 1.5, 'tag' => 'Mais de 1.5 Gols FT', 'bench' => 2.25];

        $goalsFtConsistency = round(($selectedGoalsFtCond['h']['pct'] + $selectedGoalsFtCond['a']['pct']) / 2);
        $goalsFtConfidence = round((($selectedGoalsFtCond['h']['weighted_pct'] + $selectedGoalsFtCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expFtGoals / $selectedGoalsFtCond['bench']) * 80) * 0.25));
        
        if ($expFtGoals < 2.30 || $selectedGoalsFtCond['h']['pct'] < 80 || $selectedGoalsFtCond['a']['pct'] < 80) {
            $goalsFtConfidence = min(59, $goalsFtConfidence);
        }
        if ($isLowSample) $goalsFtConfidence = round($goalsFtConfidence * 0.85);
        $goalsFtConfidence = min(98, max(30, $goalsFtConfidence));

        $gFtStreak = max($selectedGoalsFtCond['h']['streak'], $selectedGoalsFtCond['a']['streak']);
        $gFtBadge = ($gFtStreak >= 3) ? "🔥 Over Gols em {$gFtStreak} jogos seguidos" : (($goalsFtConsistency >= 75) ? "🎯 Consistência {$goalsFtConsistency}%" : null);

        $results['gols_ft'] = [
            'market_name' => 'Gols Tempo Integral',
            'market_tag' => $selectedGoalsFtCond['tag'],
            'confidence' => $goalsFtConfidence,
            'consistency_pct' => $goalsFtConsistency,
            'streak_badge' => $gFtBadge,
            'recent_form' => $selectedGoalsFtCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($goalsFtConfidence),
            'badge_color' => '#f59e0b',
            'main_stat' => "Média {$expFtGoals} Gols/Jogo",
            'stat_summary' => [
                "Média combinada FT: {$expFtGoals} gols por jogo",
                "{$hName} em casa: média {$hFtGoalsAvg} gols ({$selectedGoalsFtCond['h']['pct']}% na linha)",
                "{$aName} fora: média {$aFtGoalsAvg} gols ({$selectedGoalsFtCond['a']['pct']}% na linha)",
                "Taxa da linha {$selectedGoalsFtCond['tag']}: {$goalsFtConsistency}% geral"
            ],
            'description' => "Partidas com média combinada de **{$expFtGoals} gols totais**. Consistência de {$goalsFtConsistency}% para {$selectedGoalsFtCond['tag']}.",
            'line_config' => [
                'current_line' => $selectedGoalsFtCond['line'],
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Gols',
                'period_tag' => 'FT',
                'expected_value' => $expFtGoals,
                'h_values' => $hFtGoalsValues,
                'a_values' => $aFtGoalsValues,
            ]
        ];

        // -------------------------------------------------------------
        // 8. CARTÕES PRIMEIRO TEMPO (HT) - Over 0.5 / Over 1.5
        // -------------------------------------------------------------
        $hHtCardsAvg = (float)($hVenue['yellow_cards']['total']['avg_ht'] ?? 0);
        $aHtCardsAvg = (float)($aVenue['yellow_cards']['total']['avg_ht'] ?? 0);
        $expHtCards = round(($hHtCardsAvg + $aHtCardsAvg) / 2, 2);

        $hHtCardsValues = array_map(fn($m) => ((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0)), $hMatchesHome);
        $aHtCardsValues = array_map(fn($m) => ((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0)), $aMatchesAway);

        $hHtCardsOver05 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0))) >= 1);
        $aHtCardsOver05 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0))) >= 1);

        $hHtCardsOver15 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0))) >= 2);
        $aHtCardsOver15 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0))) >= 2);

        $isCardsHtOver15 = ($expHtCards >= 2.2 && $hHtCardsOver15['pct'] >= 80 && $aHtCardsOver15['pct'] >= 80);
        $selectedCardsHtCond = $isCardsHtOver15 ? ['h' => $hHtCardsOver15, 'a' => $aHtCardsOver15, 'line' => 1.5, 'tag' => 'Mais de 1.5 Cartões 1ºT', 'bench' => 2.2]
                                                : ['h' => $hHtCardsOver05, 'a' => $aHtCardsOver05, 'line' => 0.5, 'tag' => 'Mais de 0.5 Cartões 1ºT', 'bench' => 1.1];

        $cardsHtConsistency = round(($selectedCardsHtCond['h']['pct'] + $selectedCardsHtCond['a']['pct']) / 2);
        $cardsHtConfidence = round((($selectedCardsHtCond['h']['weighted_pct'] + $selectedCardsHtCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expHtCards / $selectedCardsHtCond['bench']) * 80) * 0.25));
        if ($isLowSample) $cardsHtConfidence = round($cardsHtConfidence * 0.85);
        $cardsHtConfidence = min(98, max(30, $cardsHtConfidence));

        $cHtStreak = max($selectedCardsHtCond['h']['streak'], $selectedCardsHtCond['a']['streak']);
        $cHtBadge = ($cHtStreak >= 3) ? "🔥 Cartão no 1ºT em {$cHtStreak} jogos seguidos" : (($cardsHtConsistency >= 75) ? "🎯 Consistência {$cardsHtConsistency}%" : null);

        $results['cartoes_ht'] = [
            'market_name' => 'Cartões Primeiro Tempo',
            'market_tag' => $selectedCardsHtCond['tag'],
            'confidence' => $cardsHtConfidence,
            'consistency_pct' => $cardsHtConsistency,
            'streak_badge' => $cHtBadge,
            'recent_form' => $selectedCardsHtCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($cardsHtConfidence),
            'badge_color' => '#eab308',
            'main_stat' => "Média {$expHtCards} Cartões 1ºT",
            'stat_summary' => [
                "Média esperada no 1ºT: {$expHtCards} cartões",
                "Mandante em casa no 1ºT: {$hVenue['yellow_cards']['feitos']['avg_ht']} recebidos / {$hVenue['yellow_cards']['cedidos']['avg_ht']} provocados",
                "Visitante fora no 1ºT: {$aVenue['yellow_cards']['feitos']['avg_ht']} recebidos / {$aVenue['yellow_cards']['cedidos']['avg_ht']} provocados",
                "Taxa da linha {$selectedCardsHtCond['tag']}: {$selectedCardsHtCond['h']['pct']}% mandante / {$selectedCardsHtCond['a']['pct']}% visitante"
            ],
            'description' => "Média combinada de **{$expHtCards} cartões na etapa inicial**. Ambas as equipes apresentam consistência de {$cardsHtConsistency}% para a linha {$selectedCardsHtCond['tag']}.",
            'line_config' => [
                'current_line' => $selectedCardsHtCond['line'],
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Cartões',
                'period_tag' => '1ºT',
                'expected_value' => $expHtCards,
                'h_values' => $hHtCardsValues,
                'a_values' => $aHtCardsValues,
            ]
        ];

        // -------------------------------------------------------------
        // 9. CARTÕES SEGUNDO TEMPO (ST) - Over 1.5 / Over 2.5
        // -------------------------------------------------------------
        $hStCardsAvg = (float)($hVenue['yellow_cards']['total']['avg_st'] ?? 0);
        $aStCardsAvg = (float)($aVenue['yellow_cards']['total']['avg_st'] ?? 0);
        $expStCards = round(($hStCardsAvg + $aStCardsAvg) / 2, 2);

        $hStCardsValues = array_map(fn($m) => max(0, (((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0))) - (((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0)))), $hMatchesHome);
        $aStCardsValues = array_map(fn($m) => max(0, (((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0))) - (((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0)))), $aMatchesAway);

        $hStCardsOver15 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $cHt = ((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0));
            $cFt = ((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0));
            return max(0, $cFt - $cHt) >= 2;
        });

        $aStCardsOver15 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $cHt = ((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0));
            $cFt = ((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0));
            return max(0, $cFt - $cHt) >= 2;
        });

        $hStCardsOver25 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $cHt = ((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0));
            $cFt = ((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0));
            return max(0, $cFt - $cHt) >= 3;
        });

        $aStCardsOver25 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $cHt = ((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0));
            $cFt = ((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0));
            return max(0, $cFt - $cHt) >= 3;
        });

        if ($expStCards >= 3.0 && $hStCardsOver25['pct'] >= 80 && $aStCardsOver25['pct'] >= 80) {
            $selectedCardsStCond = ['h' => $hStCardsOver25, 'a' => $aStCardsOver25, 'line' => 2.5, 'tag' => 'Mais de 2.5 Cartões 2ºT', 'bench' => 3.0];
        } else {
            $selectedCardsStCond = ['h' => $hStCardsOver15, 'a' => $aStCardsOver15, 'line' => 1.5, 'tag' => 'Mais de 1.5 Cartões 2ºT', 'bench' => 2.0];
        }

        $cardsStConsistency = round(($selectedCardsStCond['h']['pct'] + $selectedCardsStCond['a']['pct']) / 2);
        $cardsStConfidence = round((($selectedCardsStCond['h']['weighted_pct'] + $selectedCardsStCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expStCards / $selectedCardsStCond['bench']) * 80) * 0.25));
        if ($isLowSample) $cardsStConfidence = round($cardsStConfidence * 0.85);
        $cardsStConfidence = min(98, max(30, $cardsStConfidence));

        $cStStreak = max($selectedCardsStCond['h']['streak'], $selectedCardsStCond['a']['streak']);
        $cStBadge = ($cStStreak >= 3) ? "🔥 Cartão no 2ºT em {$cStStreak} jogos seguidos" : (($cardsStConsistency >= 75) ? "🎯 Consistência {$cardsStConsistency}%" : null);

        $results['cartoes_st'] = [
            'market_name' => 'Cartões Segundo Tempo',
            'market_tag' => $selectedCardsStCond['tag'],
            'confidence' => $cardsStConfidence,
            'consistency_pct' => $cardsStConsistency,
            'streak_badge' => $cStBadge,
            'recent_form' => $selectedCardsStCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($cardsStConfidence),
            'badge_color' => '#f97316',
            'main_stat' => "Média {$expStCards} Cartões 2ºT",
            'stat_summary' => [
                "Média esperada no 2ºT: {$expStCards} cartões",
                "Mandante 2ºT: {$hVenue['yellow_cards']['feitos']['avg_st']} recebidos / {$hVenue['yellow_cards']['cedidos']['avg_st']} provocados",
                "Visitante 2ºT: {$aVenue['yellow_cards']['feitos']['avg_st']} recebidos / {$aVenue['yellow_cards']['cedidos']['avg_st']} provocados",
                "Taxa da linha {$selectedCardsStCond['tag']}: {$selectedCardsStCond['h']['pct']}% mandante / {$selectedCardsStCond['a']['pct']}% visitante"
            ],
            'description' => "Projeção de **{$expStCards} cartões no 2º Tempo** com consistência de {$cardsStConsistency}%.",
            'line_config' => [
                'current_line' => $selectedCardsStCond['line'],
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Cartões',
                'period_tag' => '2ºT',
                'expected_value' => $expStCards,
                'h_values' => $hStCardsValues,
                'a_values' => $aStCardsValues,
            ]
        ];

        // -------------------------------------------------------------
        // 10. CARTÕES TEMPO INTEGRAL (FT) - Over 2.5 / Over 3.5 / Over 4.5
        // -------------------------------------------------------------
        $hFtCardsAvg = (float)($hVenue['yellow_cards']['total']['avg_ft'] ?? 0);
        $aFtCardsAvg = (float)($aVenue['yellow_cards']['total']['avg_ft'] ?? 0);
        $expFtCards = round(($hFtCardsAvg + $aFtCardsAvg) / 2, 2);

        $hFtCardsValues = array_map(fn($m) => ((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0)), $hMatchesHome);
        $aFtCardsValues = array_map(fn($m) => ((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0)), $aMatchesAway);

        $hFtCardsOver25 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0))) >= 3);
        $aFtCardsOver25 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0))) >= 3);

        $hFtCardsOver35 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0))) >= 4);
        $aFtCardsOver35 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0))) >= 4);

        $hFtCardsOver45 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0))) >= 5);
        $aFtCardsOver45 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0))) >= 5);

        if ($expFtCards >= 5.5 && $hFtCardsOver45['pct'] >= 80 && $aFtCardsOver45['pct'] >= 80) {
            $selectedCardsFtCond = ['h' => $hFtOver45 ?? $hFtCardsOver45, 'a' => $aFtOver45 ?? $aFtCardsOver45, 'line' => 4.5, 'tag' => 'Mais de 4.5 Cartões FT', 'bench' => 5.5];
        } elseif ($expFtCards >= 4.4 && $hFtCardsOver35['pct'] >= 75 && $aFtCardsOver35['pct'] >= 75) {
            $selectedCardsFtCond = ['h' => $hFtCardsOver35, 'a' => $aFtCardsOver35, 'line' => 3.5, 'tag' => 'Mais de 3.5 Cartões FT', 'bench' => 4.4];
        } else {
            $selectedCardsFtCond = ['h' => $hFtCardsOver25, 'a' => $aFtCardsOver25, 'line' => 2.5, 'tag' => 'Mais de 2.5 Cartões FT', 'bench' => 3.2];
        }

        $cardsFtConsistency = round(($selectedCardsFtCond['h']['pct'] + $selectedCardsFtCond['a']['pct']) / 2);
        $cardsFtConfidence = round((($selectedCardsFtCond['h']['weighted_pct'] + $selectedCardsFtCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expFtCards / $selectedCardsFtCond['bench']) * 80) * 0.25));
        if ($isLowSample) $cardsFtConfidence = round($cardsFtConfidence * 0.85);
        $cardsFtConfidence = min(98, max(30, $cardsFtConfidence));

        $cFtStreak = max($selectedCardsFtCond['h']['streak'], $selectedCardsFtCond['a']['streak']);
        $cFtBadge = ($cFtStreak >= 3) ? "🔥 Linha batida em {$cFtStreak} jogos seguidos" : (($cardsFtConsistency >= 75) ? "🎯 Consistência {$cardsFtConsistency}%" : null);

        $results['cartoes_ft'] = [
            'market_name' => 'Cartões Tempo Integral',
            'market_tag' => $selectedCardsFtCond['tag'],
            'confidence' => $cardsFtConfidence,
            'consistency_pct' => $cardsFtConsistency,
            'streak_badge' => $cFtBadge,
            'recent_form' => $selectedCardsFtCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($cardsFtConfidence),
            'badge_color' => '#eab308',
            'main_stat' => "Média {$expFtCards} Cartões FT",
            'stat_summary' => [
                "Média combinada FT: {$expFtCards} cartões por jogo",
                "Mandante em casa: {$hVenue['yellow_cards']['feitos']['avg_ft']} recebidos / {$hVenue['yellow_cards']['cedidos']['avg_ft']} provocados",
                "Visitante fora: {$aVenue['yellow_cards']['feitos']['avg_ft']} recebidos / {$aVenue['yellow_cards']['cedidos']['avg_ft']} provocados",
                "Taxa da linha {$selectedCardsFtCond['tag']}: {$selectedCardsFtCond['h']['pct']}% casa / {$selectedCardsFtCond['a']['pct']}% fora"
            ],
            'description' => "Projeção de **{$expFtCards} cartões no jogo** com {$cardsFtConsistency}% de consistência histórica na linha {$selectedCardsFtCond['tag']}.",
            'line_config' => [
                'current_line' => $selectedCardsFtCond['line'],
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Cartões',
                'period_tag' => 'FT',
                'expected_value' => $expFtCards,
                'h_values' => $hFtCardsValues,
                'a_values' => $aFtCardsValues,
            ]
        ];

        // -------------------------------------------------------------
        // 11. FAVORITO VENCE / CHANCE DUPLA (Alta Convicção: >= 80% Win Rate)
        // -------------------------------------------------------------
        $hHomeWinCond = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['home_score_ft'] ?? 0)) > ((int)($m['away_score_ft'] ?? 0)));
        $hHomeUnbeatenCond = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['home_score_ft'] ?? 0)) >= ((int)($m['away_score_ft'] ?? 0)));
        $hHomeLossCond = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['away_score_ft'] ?? 0)) > ((int)($m['home_score_ft'] ?? 0)));

        $aAwayWinCond = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['away_score_ft'] ?? 0)) > ((int)($m['home_score_ft'] ?? 0)));
        $aAwayUnbeatenCond = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['away_score_ft'] ?? 0)) >= ((int)($m['home_score_ft'] ?? 0)));
        $aAwayLossCond = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['home_score_ft'] ?? 0)) > ((int)($m['away_score_ft'] ?? 0)));

        $hWinPct = $hHomeWinCond['pct'];
        $aWinPct = $aAwayWinCond['pct'];
        $hUnbeatenPct = $hHomeUnbeatenCond['pct'];
        $aUnbeatenPct = $aAwayUnbeatenCond['pct'];
        $aLossPct = $aAwayLossCond['pct'];
        $hLossPct = $hHomeLossCond['pct'];

        $hGoalDiff = ($hVenue['goals']['feitos']['avg_ft'] ?? 0) - ($hVenue['goals']['cedidos']['avg_ft'] ?? 0);
        $aGoalDiff = ($aVenue['goals']['feitos']['avg_ft'] ?? 0) - ($aVenue['goals']['cedidos']['avg_ft'] ?? 0);

        $hPower = ($hWinPct * 0.4) + ($hUnbeatenPct * 0.3) + ($aLossPct * 0.3) + ($hGoalDiff * 8);
        $aPower = ($aWinPct * 0.4) + ($aUnbeatenPct * 0.3) + ($hLossPct * 0.3) + ($aGoalDiff * 8);

        $isHomeFav = $hPower >= $aPower;
        $favTeamName = $isHomeFav ? $hName : $aName;
        $favRole = $isHomeFav ? 'Mandante' : 'Visitante';
        $favWinPct = $isHomeFav ? $hWinPct : $aWinPct;
        $favUnbeatenPct = $isHomeFav ? $hUnbeatenPct : $aUnbeatenPct;
        $underdogLossPct = $isHomeFav ? $aLossPct : $hLossPct;
        $underdogWinPct = $isHomeFav ? $aWinPct : $hWinPct;
        $favGoalDiff = $isHomeFav ? $hGoalDiff : $aGoalDiff;
        $favStreak = $isHomeFav ? $hHomeUnbeatenCond['streak'] : $aAwayUnbeatenCond['streak'];
        $favForm = $isHomeFav ? $hHomeUnbeatenCond['recent_form'] : $aAwayUnbeatenCond['recent_form'];

        $isDirectWin = ($favWinPct >= 75 && $underdogLossPct >= 55 && $favGoalDiff >= 0.8 && !$isLowSample);
        $isStrongChanceDupla = ($favUnbeatenPct >= 80 && $underdogWinPct <= 25 && !$isLowSample);

        if ($isDirectWin) {
            $favTag = "Vitória: {$favTeamName} ({$favRole})";
            $favMarketName = 'Favorito Vence';
            $favConfidence = round(($favWinPct * 0.55) + ($underdogLossPct * 0.45));
            $favBadge = ($favStreak >= 3) ? "🔥 {$favStreak} jogos invicto" : (($favWinPct >= 70) ? "🎯 {$favWinPct}% Vitórias" : null);
        } elseif ($isStrongChanceDupla) {
            $favTag = $isHomeFav ? "Chance Dupla: {$favTeamName} ou Empate (1X)" : "Chance Dupla: {$favTeamName} ou Empate (X2)";
            $favMarketName = 'Chance Dupla / Dupla Hipótese';
            $favConfidence = round(($favUnbeatenPct * 0.6) + ((100 - $underdogWinPct) * 0.4));
            $favBadge = ($favStreak >= 3) ? "🔥 {$favStreak} jogos sem perder" : (($favUnbeatenPct >= 75) ? "🎯 {$favUnbeatenPct}% Invicto" : null);
        } else {
            $favTag = $isHomeFav ? "Chance Dupla: {$favTeamName} ou Empate (1X)" : "Chance Dupla: {$favTeamName} ou Empate (X2)";
            $favMarketName = 'Chance Dupla / Dupla Hipótese';
            $favConfidence = min(58, round(($favUnbeatenPct * 0.5) + ((100 - $underdogWinPct) * 0.5)));
            $favBadge = null;
        }

        if ($isLowSample) $favConfidence = round($favConfidence * 0.80);
        $favConfidence = min(96, max(30, $favConfidence));

        $results['favorito_vence'] = [
            'market_name' => $favMarketName,
            'market_tag' => $favTag,
            'confidence' => $favConfidence,
            'consistency_pct' => $isDirectWin ? $favWinPct : $favUnbeatenPct,
            'streak_badge' => $favBadge,
            'recent_form' => $favForm,
            'rating' => $this->getRatingLabel($favConfidence),
            'badge_color' => '#22c55e',
            'main_stat' => "{$favConfidence}% Convicção",
            'stat_summary' => [
                "{$favTeamName} ({$favRole}): {$favUnbeatenPct}% invicto ({$favWinPct}% vitórias)",
                "Adversário fora/casa: {$underdogLossPct}% derrotas / {$underdogWinPct}% vitórias",
                "Saldo médio de gols: " . sprintf('%+.2f', $favGoalDiff)
            ],
            'description' => "O **{$favTeamName}** apresenta solidez tática superior ({$favConfidence}% de probabilidade calculada). Como {$favRole}, mantém **{$favUnbeatenPct}% de invencibilidade** em seus domínios."
        ];

        // -------------------------------------------------------------
        // 12. FINALIZAÇÕES PRIMEIRO TEMPO (HT) - Sniper Edition (Over 7.5 / Over 8.5 / Over 9.5)
        // -------------------------------------------------------------
        $hHtShotsMade = (float)($hVenue['shots']['feitos']['avg_ht'] ?? 0);
        $hHtShotsCed  = (float)($hVenue['shots']['cedidos']['avg_ht'] ?? 0);
        $aHtShotsMade = (float)($aVenue['shots']['feitos']['avg_ht'] ?? 0);
        $aHtShotsCed  = (float)($aVenue['shots']['cedidos']['avg_ht'] ?? 0);

        $expHomeHtShots = round(($hHtShotsMade + $aHtShotsCed) / 2, 2);
        $expAwayHtShots = round(($aHtShotsMade + $hHtShotsCed) / 2, 2);
        $expHtShots     = round($expHomeHtShots + $expAwayHtShots, 2);

        $hHtShotsOver75 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0))) >= 8);
        $aHtShotsOver75 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0))) >= 8);

        $hHtShotsOver85 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0))) >= 9);
        $aHtShotsOver85 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0))) >= 9);

        $hHtShotsOver95 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0))) >= 10);
        $aHtShotsOver95 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0))) >= 10);

        if ($expHtShots >= 11.0 && $hHtShotsOver95['pct'] >= 80 && $aHtShotsOver95['pct'] >= 80) {
            $lineValShotsHt = 9.5;
            $selectedShotsHtCond = ['h' => $hHtShotsOver95, 'a' => $aHtShotsOver95, 'bench' => 11.0];
        } elseif ($expHtShots >= 9.8 && $hHtShotsOver85['pct'] >= 75 && $aHtShotsOver85['pct'] >= 75) {
            $lineValShotsHt = 8.5;
            $selectedShotsHtCond = ['h' => $hHtShotsOver85, 'a' => $aHtShotsOver85, 'bench' => 9.8];
        } else {
            $lineValShotsHt = 7.5;
            $selectedShotsHtCond = ['h' => $hHtShotsOver75, 'a' => $aHtShotsOver75, 'bench' => 8.8];
        }

        $shotsHtConsistency = round(($selectedShotsHtCond['h']['pct'] + $selectedShotsHtCond['a']['pct']) / 2);
        $shotsHtConfidence = round((($selectedShotsHtCond['h']['weighted_pct'] + $selectedShotsHtCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expHtShots / $selectedShotsHtCond['bench']) * 80) * 0.25));
        
        if ($expHtShots < 8.8 || $selectedShotsHtCond['h']['pct'] < 75 || $selectedShotsHtCond['a']['pct'] < 75) {
            $shotsHtConfidence = min(59, $shotsHtConfidence);
        }
        if ($isLowSample) $shotsHtConfidence = round($shotsHtConfidence * 0.85);
        $shotsHtConfidence = min(98, max(30, $shotsHtConfidence));

        $targetLineShotsHt = "Mais de {$lineValShotsHt} Finalizações 1ºT";
        $sHtStreak = max($selectedShotsHtCond['h']['streak'], $selectedShotsHtCond['a']['streak']);
        $sHtBadge = ($sHtStreak >= 3) ? "🔥 Linha batida no 1ºT em {$sHtStreak} jogos seguidos" : (($shotsHtConsistency >= 75) ? "🎯 Consistência {$shotsHtConsistency}%" : null);

        $results['finalizacoes_ht'] = [
            'market_name' => 'Finalizações Primeiro Tempo',
            'market_tag' => $targetLineShotsHt,
            'confidence' => $shotsHtConfidence,
            'consistency_pct' => $shotsHtConsistency,
            'streak_badge' => $sHtBadge,
            'recent_form' => $selectedShotsHtCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($shotsHtConfidence),
            'badge_color' => '#ec4899',
            'main_stat' => "Média {$expHtShots} Chutes 1ºT",
            'stat_summary' => [
                "Média esperada no 1ºT: {$expHtShots} finalizações ({$expHomeHtShots} mandante / {$expAwayHtShots} visitante)",
                "Mandante em casa no 1ºT: {$hHtShotsMade} feitos / {$hHtShotsCed} cedidos",
                "Visitante fora no 1ºT: {$aHtShotsMade} feitos / {$aHtShotsCed} cedidos",
                "Taxa da linha {$targetLineShotsHt}: {$shotsHtConsistency}% geral"
            ],
            'description' => "Projeção de **{$expHtShots} finalizações no 1º Tempo** com {$shotsHtConsistency}% de consistência histórica.",
            'line_config' => [
                'current_line' => $lineValShotsHt,
                'h_values' => array_values(array_map(fn($m) => ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0)), $hMatchesHome)),
                'a_values' => array_values(array_map(fn($m) => ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0)), $aMatchesAway)),
                'expected_value' => $expHtShots,
                'unit' => 'Finalizações 1ºT',
                'period_tag' => '1ºT'
            ]
        ];

        // -------------------------------------------------------------
        // 13. FINALIZAÇÕES SEGUNDO TEMPO (ST) - Sniper Edition (Over 8.5 / Over 9.5 / Over 10.5)
        // -------------------------------------------------------------
        $hStShotsMade = (float)($hVenue['shots']['feitos']['avg_st'] ?? 0);
        $hStShotsCed  = (float)($hVenue['shots']['cedidos']['avg_st'] ?? 0);
        $aStShotsMade = (float)($aVenue['shots']['feitos']['avg_st'] ?? 0);
        $aStShotsCed  = (float)($aVenue['shots']['cedidos']['avg_st'] ?? 0);

        $expHomeStShots = round(($hStShotsMade + $aStShotsCed) / 2, 2);
        $expAwayStShots = round(($aStShotsMade + $hStShotsCed) / 2, 2);
        $expStShots     = round($expHomeStShots + $expAwayStShots, 2);

        $hStShotsOver85 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $sHt = ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0));
            $sFt = ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0));
            return max(0, $sFt - $sHt) >= 9;
        });

        $aStShotsOver85 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $sHt = ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0));
            $sFt = ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0));
            return max(0, $sFt - $sHt) >= 9;
        });

        $hStShotsOver95 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $sHt = ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0));
            $sFt = ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0));
            return max(0, $sFt - $sHt) >= 10;
        });

        $aStShotsOver95 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $sHt = ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0));
            $sFt = ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0));
            return max(0, $sFt - $sHt) >= 10;
        });

        $hStShotsOver105 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $sHt = ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0));
            $sFt = ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0));
            return max(0, $sFt - $sHt) >= 11;
        });

        $aStShotsOver105 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $sHt = ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0));
            $sFt = ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0));
            return max(0, $sFt - $sHt) >= 11;
        });

        if ($expStShots >= 12.0 && $hStShotsOver105['pct'] >= 80 && $aStShotsOver105['pct'] >= 80) {
            $lineValShotsSt = 10.5;
            $selectedShotsStCond = ['h' => $hStShotsOver105, 'a' => $aStShotsOver105, 'bench' => 12.0];
        } elseif ($expStShots >= 10.8 && $hStShotsOver95['pct'] >= 75 && $aStShotsOver95['pct'] >= 75) {
            $lineValShotsSt = 9.5;
            $selectedShotsStCond = ['h' => $hStShotsOver95, 'a' => $aStShotsOver95, 'bench' => 10.8];
        } else {
            $lineValShotsSt = 8.5;
            $selectedShotsStCond = ['h' => $hStShotsOver85, 'a' => $aStShotsOver85, 'bench' => 9.8];
        }

        $shotsStConsistency = round(($selectedShotsStCond['h']['pct'] + $selectedShotsStCond['a']['pct']) / 2);
        $shotsStConfidence = round((($selectedShotsStCond['h']['weighted_pct'] + $selectedShotsStCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expStShots / $selectedShotsStCond['bench']) * 80) * 0.25));
        
        if ($expStShots < 9.8 || $selectedShotsStCond['h']['pct'] < 75 || $selectedShotsStCond['a']['pct'] < 75) {
            $shotsStConfidence = min(59, $shotsStConfidence);
        }
        if ($isLowSample) $shotsStConfidence = round($shotsStConfidence * 0.85);
        $shotsStConfidence = min(98, max(30, $shotsStConfidence));

        $targetLineShotsSt = "Mais de {$lineValShotsSt} Finalizações 2ºT";
        $sStStreak = max($selectedShotsStCond['h']['streak'], $selectedShotsStCond['a']['streak']);
        $sStBadge = ($sStStreak >= 3) ? "🔥 Linha batida no 2ºT em {$sStStreak} jogos seguidos" : (($shotsStConsistency >= 75) ? "🎯 Consistência {$shotsStConsistency}%" : null);

        $results['finalizacoes_st'] = [
            'market_name' => 'Finalizações Segundo Tempo',
            'market_tag' => $targetLineShotsSt,
            'confidence' => $shotsStConfidence,
            'consistency_pct' => $shotsStConsistency,
            'streak_badge' => $sStBadge,
            'recent_form' => $selectedShotsStCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($shotsStConfidence),
            'badge_color' => '#d946ef',
            'main_stat' => "Média {$expStShots} Chutes 2ºT",
            'stat_summary' => [
                "Média esperada no 2ºT: {$expStShots} finalizações ({$expHomeStShots} mandante / {$expAwayStShots} visitante)",
                "Mandante 2ºT: {$hStShotsMade} feitos / {$hStShotsCed} cedidos",
                "Visitante 2ºT: {$aStShotsMade} feitos / {$aStShotsCed} cedidos",
                "Taxa da linha {$targetLineShotsSt}: {$shotsStConsistency}% geral"
            ],
            'description' => "Projeção de **{$expStShots} chutes no 2º Tempo** com {$shotsStConsistency}% de consistência histórica.",
            'line_config' => [
                'current_line' => $lineValShotsSt,
                'h_values' => array_values(array_map(fn($m) => max(0, (((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0))) - (((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0)))), $hMatchesHome)),
                'a_values' => array_values(array_map(fn($m) => max(0, (((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0))) - (((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0)))), $aMatchesAway)),
                'expected_value' => $expStShots,
                'unit' => 'Finalizações 2ºT',
                'period_tag' => '2ºT'
            ]
        ];

        // -------------------------------------------------------------
        // 14. FINALIZAÇÕES TEMPO INTEGRAL (FT) - Sniper Edition (Over 17.5 / Over 18.5 / Over 20.5)
        // -------------------------------------------------------------
        $hFtShotsMade = (float)($hVenue['shots']['feitos']['avg_ft'] ?? 0);
        $hFtShotsCed  = (float)($hVenue['shots']['cedidos']['avg_ft'] ?? 0);
        $aFtShotsMade = (float)($aVenue['shots']['feitos']['avg_ft'] ?? 0);
        $aFtShotsCed  = (float)($aVenue['shots']['cedidos']['avg_ft'] ?? 0);

        $expHomeFtShots = round(($hFtShotsMade + $aFtShotsCed) / 2, 2);
        $expAwayFtShots = round(($aFtShotsMade + $hFtShotsCed) / 2, 2);
        $expFtShots     = round($expHomeFtShots + $expAwayFtShots, 2);

        $hFtShotsOver175 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0))) >= 18);
        $aFtShotsOver175 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0))) >= 18);

        $hFtShotsOver185 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0))) >= 19);
        $aFtShotsOver185 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0))) >= 19);

        $hFtShotsOver205 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => (((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0))) >= 21);
        $aFtShotsOver205 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => (((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0))) >= 21);

        if ($expFtShots >= 23.5 && $hFtShotsOver205['pct'] >= 80 && $aFtShotsOver205['pct'] >= 80) {
            $lineValShotsFt = 20.5;
            $selectedShotsFtCond = ['h' => $hFtShotsOver205, 'a' => $aFtShotsOver205, 'bench' => 23.5];
        } elseif ($expFtShots >= 21.0 && $hFtShotsOver185['pct'] >= 75 && $aFtShotsOver185['pct'] >= 75) {
            $lineValShotsFt = 18.5;
            $selectedShotsFtCond = ['h' => $hFtShotsOver185, 'a' => $aFtShotsOver185, 'bench' => 21.0];
        } else {
            $lineValShotsFt = 17.5;
            $selectedShotsFtCond = ['h' => $hFtShotsOver175, 'a' => $aFtShotsOver175, 'bench' => 19.5];
        }

        $shotsFtConsistency = round(($selectedShotsFtCond['h']['pct'] + $selectedShotsFtCond['a']['pct']) / 2);
        $shotsFtConfidence = round((($selectedShotsFtCond['h']['weighted_pct'] + $selectedShotsFtCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expFtShots / $selectedShotsFtCond['bench']) * 80) * 0.25));
        
        if ($expFtShots < 19.5 || $selectedShotsFtCond['h']['pct'] < 75 || $selectedShotsFtCond['a']['pct'] < 75) {
            $shotsFtConfidence = min(59, $shotsFtConfidence);
        }
        if ($isLowSample) $shotsFtConfidence = round($shotsFtConfidence * 0.85);
        $shotsFtConfidence = min(98, max(30, $shotsFtConfidence));

        $targetLineShotsFt = "Mais de {$lineValShotsFt} Finalizações FT";
        $sFtStreak = max($selectedShotsFtCond['h']['streak'], $selectedShotsFtCond['a']['streak']);
        $sFtBadge = ($sFtStreak >= 3) ? "🔥 Linha batida em {$sFtStreak} jogos seguidos" : (($shotsFtConsistency >= 75) ? "🎯 Consistência {$shotsFtConsistency}%" : null);

        $results['finalizacoes_ft'] = [
            'market_name' => 'Finalizações Tempo Integral',
            'market_tag' => $targetLineShotsFt,
            'confidence' => $shotsFtConfidence,
            'consistency_pct' => $shotsFtConsistency,
            'streak_badge' => $sFtBadge,
            'recent_form' => $selectedShotsFtCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($shotsFtConfidence),
            'badge_color' => '#f43f5e',
            'main_stat' => "Média {$expFtShots} Chutes FT",
            'stat_summary' => [
                "Média esperada na partida: {$expFtShots} finalizações ({$expHomeFtShots} mandante / {$expAwayFtShots} visitante)",
                "{$hName} em casa: {$hFtShotsMade} feitos / {$hFtShotsCed} cedidos",
                "{$aName} fora: {$aFtShotsMade} feitos / {$aFtShotsCed} cedidos",
                "Taxa da linha {$targetLineShotsFt}: {$shotsFtConsistency}% geral"
            ],
            'description' => "Projeção de **{$expFtShots} finalizações totais na partida** com {$shotsFtConsistency}% de consistência histórica.",
            'line_config' => [
                'current_line' => $lineValShotsFt,
                'h_values' => array_values(array_map(fn($m) => ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0)), $hMatchesHome)),
                'a_values' => array_values(array_map(fn($m) => ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0)), $aMatchesAway)),
                'expected_value' => $expFtShots,
                'unit' => 'Finalizações',
                'period_tag' => 'FT'
            ]
        ];

        // -------------------------------------------------------------
        // 15. FINALIZAÇÕES MANDANTE (FT) - Sniper Edition
        // -------------------------------------------------------------
        $hHomeShotsValues = array_map(fn($m) => (int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0), $hMatchesHome);
        $aAwayCedValues = array_map(fn($m) => (int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0), $aMatchesAway);

        $hFtOver95 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) >= 10);
        $aFtCedOver95 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) >= 10);

        $hFtOver115 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) >= 12);
        $aFtCedOver115 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) >= 12);

        $hFtOver135 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) >= 14);
        $aFtCedOver135 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) >= 14);

        if ($expHomeFtShots >= 14.5 && $hFtOver135['pct'] >= 80 && $aFtCedOver135['pct'] >= 80) {
            $lineValHomeShotsFt = 13.5;
            $selectedHomeShotsCond = ['h' => $hFtOver135, 'a' => $aFtCedOver135, 'bench' => 14.5];
        } elseif ($expHomeFtShots >= 12.5 && $hFtOver115['pct'] >= 75 && $aFtCedOver115['pct'] >= 75) {
            $lineValHomeShotsFt = 11.5;
            $selectedHomeShotsCond = ['h' => $hFtOver115, 'a' => $aFtCedOver115, 'bench' => 12.5];
        } else {
            $lineValHomeShotsFt = 9.5;
            $selectedHomeShotsCond = ['h' => $hFtOver95, 'a' => $aFtCedOver95, 'bench' => 11.0];
        }

        $homeShotsFtConsistency = round(($selectedHomeShotsCond['h']['pct'] + $selectedHomeShotsCond['a']['pct']) / 2);
        $homeShotsFtConfidence = round((($selectedHomeShotsCond['h']['weighted_pct'] + $selectedHomeShotsCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expHomeFtShots / $selectedHomeShotsCond['bench']) * 80) * 0.25));
        if ($expHomeFtShots < 11.5 || $selectedHomeShotsCond['h']['pct'] < 75 || $selectedHomeShotsCond['a']['pct'] < 75) {
            $homeShotsFtConfidence = min(59, $homeShotsFtConfidence);
        }
        if ($isLowSample) $homeShotsFtConfidence = round($homeShotsFtConfidence * 0.85);
        $homeShotsFtConfidence = min(98, max(30, $homeShotsFtConfidence));

        $targetLineHomeShotsFt = "{$hName}: Mais de {$lineValHomeShotsFt} Finalizações FT";
        $sHomeStreak = max($selectedHomeShotsCond['h']['streak'], $selectedHomeShotsCond['a']['streak']);
        $sHomeBadge = ($sHomeStreak >= 3) ? "🔥 {$hName} bateu a linha em {$sHomeStreak} jogos seguidos" : (($homeShotsFtConsistency >= 75) ? "🎯 Consistência {$homeShotsFtConsistency}%" : null);

        $results['finalizacoes_casa_ft'] = [
            'market_name' => 'Finalizações Mandante (FT)',
            'market_tag' => $targetLineHomeShotsFt,
            'confidence' => $homeShotsFtConfidence,
            'consistency_pct' => $homeShotsFtConsistency,
            'streak_badge' => $sHomeBadge,
            'recent_form' => $selectedHomeShotsCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($homeShotsFtConfidence),
            'badge_color' => '#f43f5e',
            'main_stat' => "Média {$expHomeFtShots} Chutes Mandante",
            'stat_summary' => [
                "Média esperada de chutes do {$hName}: {$expHomeFtShots} finalizações",
                "{$hName} em casa: média {$hFtShotsMade} chutes a favor",
                "{$aName} fora: cede em média {$aFtShotsCed} chutes ao mandante",
                "Taxa da linha {$targetLineHomeShotsFt}: {$homeShotsFtConsistency}% geral"
            ],
            'description' => "O **{$hName}** tem projeção de **{$expHomeFtShots} finalizações** jogando em casa, enfrentando o **{$aName}** que cede em média {$aFtShotsCed} finalizações fora.",
            'line_config' => [
                'current_line' => $lineValHomeShotsFt,
                'team_prefix' => "{$hName}: ",
                'h_values' => $hHomeShotsValues,
                'a_values' => $aAwayCedValues,
                'expected_value' => $expHomeFtShots,
                'benchmark' => $selectedHomeShotsCond['bench'] ?? 11.0,
                'unit' => 'Finalizações',
                'period_tag' => 'FT'
            ]
        ];

        // -------------------------------------------------------------
        // 16. FINALIZAÇÕES VISITANTE (FT) - Sniper Edition
        // -------------------------------------------------------------
        $aAwayShotsValues = array_map(fn($m) => (int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0), $aMatchesAway);
        $hHomeCedValues = array_map(fn($m) => (int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0), $hMatchesHome);

        $aFtOver75 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0)) >= 8);
        $hFtCedOver75 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0)) >= 8);

        $aFtOver95 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0)) >= 10);
        $hFtCedOver95 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0)) >= 10);

        $aFtOver115 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0)) >= 12);
        $hFtCedOver115 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0)) >= 12);

        if ($expAwayFtShots >= 13.5 && $aFtOver115['pct'] >= 80 && $hFtCedOver115['pct'] >= 80) {
            $lineValAwayShotsFt = 11.5;
            $selectedAwayShotsCond = ['h' => $aFtOver115, 'a' => $hFtCedOver115, 'bench' => 13.5];
        } elseif ($expAwayFtShots >= 11.5 && $aFtOver95['pct'] >= 75 && $hFtCedOver95['pct'] >= 75) {
            $lineValAwayShotsFt = 9.5;
            $selectedAwayShotsCond = ['h' => $aFtOver95, 'a' => $hFtCedOver95, 'bench' => 11.5];
        } else {
            $lineValAwayShotsFt = 7.5;
            $selectedAwayShotsCond = ['h' => $aFtOver75, 'a' => $hFtCedOver75, 'bench' => 10.0];
        }

        $awayShotsFtConsistency = round(($selectedAwayShotsCond['h']['pct'] + $selectedAwayShotsCond['a']['pct']) / 2);
        $awayShotsFtConfidence = round((($selectedAwayShotsCond['h']['weighted_pct'] + $selectedAwayShotsCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expAwayFtShots / $selectedAwayShotsCond['bench']) * 80) * 0.25));
        if ($expAwayFtShots < 10.0 || $selectedAwayShotsCond['h']['pct'] < 75 || $selectedAwayShotsCond['a']['pct'] < 75) {
            $awayShotsFtConfidence = min(59, $awayShotsFtConfidence);
        }
        if ($isLowSample) $awayShotsFtConfidence = round($awayShotsFtConfidence * 0.85);
        $awayShotsFtConfidence = min(98, max(30, $awayShotsFtConfidence));

        $targetLineAwayShotsFt = "{$aName}: Mais de {$lineValAwayShotsFt} Finalizações FT";
        $sAwayStreak = max($selectedAwayShotsCond['h']['streak'], $selectedAwayShotsCond['a']['streak']);
        $sAwayBadge = ($sAwayStreak >= 3) ? "🔥 {$aName} bateu a linha em {$sAwayStreak} jogos seguidos" : (($awayShotsFtConsistency >= 75) ? "🎯 Consistência {$awayShotsFtConsistency}%" : null);

        $results['finalizacoes_fora_ft'] = [
            'market_name' => 'Finalizações Visitante (FT)',
            'market_tag' => $targetLineAwayShotsFt,
            'confidence' => $awayShotsFtConfidence,
            'consistency_pct' => $awayShotsFtConsistency,
            'streak_badge' => $sAwayBadge,
            'recent_form' => $selectedAwayShotsCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($awayShotsFtConfidence),
            'badge_color' => '#ec4899',
            'main_stat' => "Média {$expAwayFtShots} Chutes Visitante",
            'stat_summary' => [
                "Média esperada de chutes do {$aName}: {$expAwayFtShots} finalizações",
                "{$aName} fora: média {$aFtShotsMade} chutes a favor",
                "{$hName} em casa: cede em média {$hFtShotsCed} chutes ao visitante",
                "Taxa da linha {$targetLineAwayShotsFt}: {$awayShotsFtConsistency}% geral"
            ],
            'description' => "O **{$aName}** tem projeção de **{$expAwayFtShots} finalizações** fora de casa, com o **{$hName}** cedendo em média {$hFtShotsCed} chutes em seus domínios.",
            'line_config' => [
                'current_line' => $lineValAwayShotsFt,
                'team_prefix' => "{$aName}: ",
                'h_values' => $aAwayShotsValues,
                'a_values' => $hHomeCedValues,
                'expected_value' => $expAwayFtShots,
                'benchmark' => $selectedAwayShotsCond['bench'] ?? 10.0,
                'unit' => 'Finalizações',
                'period_tag' => 'FT'
            ]
        ];

        // -------------------------------------------------------------
        // 17. FINALIZAÇÕES MANDANTE (1ºT) - Sniper Edition
        // -------------------------------------------------------------
        $hHomeHtShotsValues = array_map(fn($m) => (int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0), $hMatchesHome);
        $aAwayHtCedValues = array_map(fn($m) => (int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0), $aMatchesAway);

        $hHtOver35 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) >= 4);
        $aHtCedOver35 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) >= 4);

        $hHtOver45 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) >= 5);
        $aHtCedOver45 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) >= 5);

        if ($expHomeHtShots >= 5.8 && $hHtOver45['pct'] >= 80 && $aHtCedOver45['pct'] >= 80) {
            $lineValHomeHt = 4.5;
            $selectedHomeHtCond = ['h' => $hHtOver45, 'a' => $aHtCedOver45, 'bench' => 5.8];
        } else {
            $lineValHomeHt = 3.5;
            $selectedHomeHtCond = ['h' => $hHtOver35, 'a' => $aHtCedOver35, 'bench' => 4.8];
        }

        $homeHtConsistency = round(($selectedHomeHtCond['h']['pct'] + $selectedHomeHtCond['a']['pct']) / 2);
        $homeHtConfidence = round((($selectedHomeHtCond['h']['weighted_pct'] + $selectedHomeHtCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expHomeHtShots / $selectedHomeHtCond['bench']) * 80) * 0.25));
        if ($expHomeHtShots < 4.5 || $selectedHomeHtCond['h']['pct'] < 75 || $selectedHomeHtCond['a']['pct'] < 75) {
            $homeHtConfidence = min(59, $homeHtConfidence);
        }
        if ($isLowSample) $homeHtConfidence = round($homeHtConfidence * 0.85);
        $homeHtConfidence = min(98, max(30, $homeHtConfidence));

        $targetLineHomeHt = "{$hName}: Mais de {$lineValHomeHt} Finalizações 1ºT";
        $results['finalizacoes_casa_ht'] = [
            'market_name' => 'Finalizações Mandante (1ºT)',
            'market_tag' => $targetLineHomeHt,
            'confidence' => $homeHtConfidence,
            'consistency_pct' => $homeHtConsistency,
            'streak_badge' => null,
            'recent_form' => $selectedHomeHtCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($homeHtConfidence),
            'badge_color' => '#f43f5e',
            'main_stat' => "Média {$expHomeHtShots} Chutes 1ºT Mandante",
            'stat_summary' => [
                "Média esperada 1ºT ({$hName}): {$expHomeHtShots} chutes",
                "{$hName} em casa 1ºT: média {$hHtShotsMade} chutes",
                "{$aName} fora 1ºT: cede média {$aHtShotsCed} chutes",
                "Taxa da linha {$targetLineHomeHt}: {$homeHtConsistency}% geral"
            ],
            'description' => "O **{$hName}** mantém forte intensidade no 1º Tempo com projeção de **{$expHomeHtShots} finalizações**.",
            'line_config' => [
                'current_line' => $lineValHomeHt,
                'team_prefix' => "{$hName}: ",
                'h_values' => $hHomeHtShotsValues,
                'a_values' => $aAwayHtCedValues,
                'expected_value' => $expHomeHtShots,
                'benchmark' => $selectedHomeHtCond['bench'] ?? 4.8,
                'unit' => 'Finalizações',
                'period_tag' => '1ºT'
            ]
        ];

        // -------------------------------------------------------------
        // 18. FINALIZAÇÕES VISITANTE (1ºT) - Sniper Edition
        // -------------------------------------------------------------
        $aAwayHtShotsValues = array_map(fn($m) => (int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0), $aMatchesAway);
        $hHomeHtCedValues = array_map(fn($m) => (int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0), $hMatchesHome);

        $aHtOver25 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0)) >= 3);
        $hHtCedOver25 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0)) >= 3);

        $aHtOver35 = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0)) >= 4);
        $hHtCedOver35 = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0)) >= 4);

        if ($expAwayHtShots >= 4.8 && $aHtOver35['pct'] >= 80 && $hHtCedOver35['pct'] >= 80) {
            $lineValAwayHt = 3.5;
            $selectedAwayHtCond = ['h' => $aHtOver35, 'a' => $hHtCedOver35, 'bench' => 4.8];
        } else {
            $lineValAwayHt = 2.5;
            $selectedAwayHtCond = ['h' => $aHtOver25, 'a' => $hHtCedOver25, 'bench' => 3.8];
        }

        $awayHtConsistency = round(($selectedAwayHtCond['h']['pct'] + $selectedAwayHtCond['a']['pct']) / 2);
        $awayHtConfidence = round((($selectedAwayHtCond['h']['weighted_pct'] + $selectedAwayHtCond['a']['weighted_pct']) / 2) * 0.75 + (min(100, ($expAwayHtShots / $selectedAwayHtCond['bench']) * 80) * 0.25));
        if ($expAwayHtShots < 3.8 || $selectedAwayHtCond['h']['pct'] < 75 || $selectedAwayHtCond['a']['pct'] < 75) {
            $awayHtConfidence = min(59, $awayHtConfidence);
        }
        if ($isLowSample) $awayHtConfidence = round($awayHtConfidence * 0.85);
        $awayHtConfidence = min(98, max(30, $awayHtConfidence));

        $targetLineAwayHt = "{$aName}: Mais de {$lineValAwayHt} Finalizações 1ºT";
        $results['finalizacoes_fora_ht'] = [
            'market_name' => 'Finalizações Visitante (1ºT)',
            'market_tag' => $targetLineAwayHt,
            'confidence' => $awayHtConfidence,
            'consistency_pct' => $awayHtConsistency,
            'streak_badge' => null,
            'recent_form' => $selectedAwayHtCond['h']['recent_form'],
            'rating' => $this->getRatingLabel($awayHtConfidence),
            'badge_color' => '#ec4899',
            'main_stat' => "Média {$expAwayHtShots} Chutes 1ºT Visitante",
            'stat_summary' => [
                "Média esperada 1ºT ({$aName}): {$expAwayHtShots} chutes",
                "{$aName} fora 1ºT: média {$aHtShotsMade} chutes",
                "{$hName} em casa 1ºT: cede média {$hHtShotsCed} chutes",
                "Taxa da linha {$targetLineAwayHt}: {$awayHtConsistency}% geral"
            ],
            'description' => "O **{$aName}** tem projeção de **{$expAwayHtShots} finalizações no 1º Tempo** como visitante.",
            'line_config' => [
                'current_line' => $lineValAwayHt,
                'team_prefix' => "{$aName}: ",
                'h_values' => $aAwayHtShotsValues,
                'a_values' => $hHomeHtCedValues,
                'expected_value' => $expAwayHtShots,
                'benchmark' => $selectedAwayHtCond['bench'] ?? 3.8,
                'unit' => 'Finalizações',
                'period_tag' => '1ºT'
            ]
        ];

        return $results;
    }


    /**
     * Auxiliar que analisa uma condição em partidas calculando recência ponderada, consistência e sequências
     */
    private function analyzeConditionOnMatches(array $matches, callable $condition): array {
        if (empty($matches)) {
            return [
                'pct' => 50,
                'recent_pct' => 50,
                'weighted_pct' => 50,
                'recent_form' => [true, true, true, true, true],
                'streak' => 0
            ];
        }

        $total = count($matches);
        $hits = 0;
        $recentHits = 0;
        $recentLimit = min(5, $total);
        $recentForm = [];
        $currentStreak = 0;
        $countingStreak = true;

        foreach ($matches as $i => $m) {
            $isHit = (bool)$condition($m);
            if ($isHit) {
                $hits++;
            }

            if ($i < $recentLimit) {
                $recentForm[] = $isHit;
                if ($isHit) {
                    $recentHits++;
                }
            }

            if ($countingStreak) {
                if ($isHit) {
                    $currentStreak++;
                } else {
                    $countingStreak = false;
                }
            }
        }

        $pct = round(($hits / $total) * 100);
        $recentPct = round(($recentHits / $recentLimit) * 100);
        $weightedPct = round(($recentPct * 0.60) + ($pct * 0.40));

        return [
            'pct' => $pct,
            'recent_pct' => $recentPct,
            'weighted_pct' => $weightedPct,
            'recent_form' => $recentForm,
            'streak' => $currentStreak
        ];
    }

    /**
     * Realiza o backtest/auditoria de assertividade em partidas finalizadas
     */
    public function analyzeFinishedMatchesBacktest(
        string $market = 'all',
        string $dateRange = 'month',
        int $minConfidence = 70,
        int $tournamentId = 0,
        array $favoriteTeamIds = [],
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $whereSql = "status = 'finished' AND is_stats_incomplete = 0";
        $params = [];

        if (!empty($favoriteTeamIds)) {
            $placeholders = implode(',', array_fill(0, count($favoriteTeamIds), '?'));
            $whereSql .= " AND (home_team_id IN ($placeholders) OR away_team_id IN ($placeholders))";
            $params = array_merge($params, $favoriteTeamIds, $favoriteTeamIds);
        }

        if ($tournamentId > 0) {
            $whereSql .= " AND tournament_id = ?";
            $params[] = $tournamentId;
        }

        // Filtro personalizado por intervalo de datas (De > Até)
        if (!empty($dateFrom) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $whereSql .= " AND match_date >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
        if (!empty($dateTo) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $whereSql .= " AND match_date <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }

        // Caso não haja intervalo customizado, aplica dateRange padrão
        if (empty($dateFrom) && empty($dateTo)) {
            if ($dateRange === 'today') {
                $whereSql .= " AND match_date >= CURDATE()";
            } elseif ($dateRange === 'week') {
                $whereSql .= " AND match_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            } elseif ($dateRange === 'month') {
                $whereSql .= " AND match_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            } elseif ($dateRange === '3months') {
                $whereSql .= " AND match_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
            }
        }

        $stmt = $this->pdo->prepare("SELECT * FROM matches WHERE {$whereSql} ORDER BY match_date DESC LIMIT 500");
        $stmt->execute($params);
        $finishedMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $auditedPredictions = [];
        $marketStats = [];

        foreach ($finishedMatches as $match) {
            $homeId = (int)$match['home_team_id'];
            $awayId = (int)$match['away_team_id'];
            if (!$homeId || !$awayId) continue;

            $matchEventId = (int)($match['sofascore_event_id'] ?? 0);
            $matchTimestamp = (int)($match['start_timestamp'] ?? 0);

            // Históricos antes da partida
            $hVenue = $this->sync->getTeamVenueStats($homeId, 'home', true, $matchEventId, $matchTimestamp);
            $hAll   = $this->sync->getTeamVenueStats($homeId, 'all', true, $matchEventId, $matchTimestamp);
            $aVenue = $this->sync->getTeamVenueStats($awayId, 'away', true, $matchEventId, $matchTimestamp);
            $aAll   = $this->sync->getTeamVenueStats($awayId, 'all', true, $matchEventId, $matchTimestamp);
            $h2h    = $this->sync->getH2HMatches($homeId, $awayId, $matchEventId, $matchTimestamp);

            $evaluations = $this->evaluateMatchMarkets($match, $hVenue, $hAll, $aVenue, $aAll, $h2h);

            foreach ($evaluations as $mKey => $eval) {
                if ($market !== 'all' && $market !== $mKey) {
                    if ($market === 'cartoes' && str_starts_with($mKey, 'cartoes_')) {
                        // allow
                    } elseif ($market === 'cantos' && str_starts_with($mKey, 'cantos_')) {
                        // allow
                    } elseif ($market === 'gols' && str_starts_with($mKey, 'gols_')) {
                        // allow
                    } elseif ($market === 'finalizacoes' && str_starts_with($mKey, 'finalizacoes_')) {
                        // allow
                    } elseif (($market === 'finalizacoes_casa' || $market === 'finalizacoes_fora') && str_starts_with($mKey, $market . '_')) {
                        // allow
                    } else {
                        continue;
                    }
                }

                if ($eval['confidence'] < $minConfidence) {
                    continue;
                }

                // Validar se o palpite realmente BATEU (GREEN) ou FALHOU (RED)
                $isGreen = $this->checkMarketOutcome($mKey, $eval, $match);

                if (!isset($marketStats[$mKey])) {
                    $marketStats[$mKey] = [
                        'market_key' => $mKey,
                        'market_name' => $eval['market_name'],
                        'total' => 0,
                        'greens' => 0,
                        'reds' => 0,
                        'win_rate' => 0
                    ];
                }

                $marketStats[$mKey]['total']++;
                if ($isGreen) {
                    $marketStats[$mKey]['greens']++;
                } else {
                    $marketStats[$mKey]['reds']++;
                }

                $auditedPredictions[] = [
                    'event_id' => $match['sofascore_event_id'] ?: $match['id'],
                    'tournament_name' => $match['season_name'] ?: 'Futebol',
                    'match_date' => $match['match_date'],
                    'start_timestamp' => $match['start_timestamp'],
                    'home_team' => [
                        'id' => $homeId,
                        'name' => $match['home_team_name'],
                        'logo' => "api.php?action=get_image&type=team&id={$homeId}",
                        'score_ft' => $match['home_score_ft'],
                        'score_ht' => $match['home_score_ht'],
                        'corners_ht' => $match['home_corners_ht'],
                        'corners_ft' => $match['home_corners_ft'],
                    ],
                    'away_team' => [
                        'id' => $awayId,
                        'name' => $match['away_team_name'],
                        'logo' => "api.php?action=get_image&type=team&id={$awayId}",
                        'score_ft' => $match['away_score_ft'],
                        'score_ht' => $match['away_score_ht'],
                        'corners_ht' => $match['away_corners_ht'],
                        'corners_ft' => $match['away_corners_ft'],
                    ],
                    'market_key' => $mKey,
                    'market_name' => $eval['market_name'],
                    'market_tag' => $eval['market_tag'],
                    'confidence' => $eval['confidence'],
                    'streak_badge' => $eval['streak_badge'] ?? null,
                    'rating' => $eval['rating'],
                    'badge_color' => $eval['badge_color'],
                    'is_green' => $isGreen,
                    'actual_summary' => $this->getActualMatchSummary($mKey, $match)
                ];
            }
        }

        // Calcular win rates de mercado
        foreach ($marketStats as $mKey => &$ms) {
            $ms['win_rate'] = $ms['total'] > 0 ? round(($ms['greens'] / $ms['total']) * 100, 1) : 0;
        }
        unset($ms);

        usort($marketStats, fn($a, $b) => $b['win_rate'] <=> $a['win_rate']);

        $totalPredictions = count($auditedPredictions);
        $totalGreens = count(array_filter($auditedPredictions, fn($p) => $p['is_green']));
        $totalReds = $totalPredictions - $totalGreens;
        $globalWinRate = $totalPredictions > 0 ? round(($totalGreens / $totalPredictions) * 100, 1) : 0;

        return [
            'kpis' => [
                'total_predictions' => $totalPredictions,
                'total_greens' => $totalGreens,
                'total_reds' => $totalReds,
                'win_rate' => $globalWinRate
            ],
            'market_breakdown' => array_values($marketStats),
            'predictions' => $auditedPredictions
        ];
    }


    /**
     * Verifica se uma linha prevista em um mercado finalizou como GREEN (true) ou RED (false)
     */
    private function checkMarketOutcome(string $marketKey, array $eval, array $match): bool {
        $hScoreFt = (int)($match['home_score_ft'] ?? 0);
        $aScoreFt = (int)($match['away_score_ft'] ?? 0);
        $hScoreHt = (int)($match['home_score_ht'] ?? 0);
        $aScoreHt = (int)($match['away_score_ht'] ?? 0);
        
        $totalStGoals = max(0, $hScoreFt - $hScoreHt) + max(0, $aScoreFt - $aScoreHt);
        $totalFtGoals = $hScoreFt + $aScoreFt;
        $totalHtGoals = $hScoreHt + $aScoreHt;

        $hCornersHt = (int)($match['home_corners_ht'] ?? 0);
        $aCornersHt = (int)($match['away_corners_ht'] ?? 0);
        $hCornersFt = (int)($match['home_corners_ft'] ?? 0);
        $aCornersFt = (int)($match['away_corners_ft'] ?? 0);
        $totalHtCorners = $hCornersHt + $aCornersHt;
        $totalFtCorners = $hCornersFt + $aCornersFt;
        $totalStCorners = max(0, $totalFtCorners - $totalHtCorners);

        $hCardsHt = (int)($match['home_yellow_cards_ht'] ?? 0);
        $aCardsHt = (int)($match['away_yellow_cards_ht'] ?? 0);
        $hCardsFt = (int)($match['home_yellow_cards_ft'] ?? 0);
        $aCardsFt = (int)($match['away_yellow_cards_ft'] ?? 0);
        $totalHtCards = $hCardsHt + $aCardsHt;
        $totalFtCards = $hCardsFt + $aCardsFt;
        $totalStCards = max(0, $totalFtCards - $totalHtCards);

        $hShotsHt = (int)($match['home_shots_ht'] ?? $match['home_shots_on_target_ht'] ?? 0);
        $aShotsHt = (int)($match['away_shots_ht'] ?? $match['away_shots_on_target_ht'] ?? 0);
        $hShotsFt = (int)($match['home_shots_ft'] ?? $match['home_shots_on_target_ft'] ?? 0);
        $aShotsFt = (int)($match['away_shots_ft'] ?? $match['away_shots_on_target_ft'] ?? 0);
        $totalHtShots = $hShotsHt + $aShotsHt;
        $totalFtShots = $hShotsFt + $aShotsFt;
        $totalStShots = max(0, $totalFtShots - $totalHtShots);

        $tag = $eval['market_tag'] ?? '';

        switch ($marketKey) {
            case 'ambos_marcam':
                return $hScoreFt > 0 && $aScoreFt > 0;

            case 'cantos_ht':
                if (str_contains($tag, '4.5')) return $totalHtCorners >= 5;
                if (str_contains($tag, '3.5')) return $totalHtCorners >= 4;
                return $totalHtCorners >= 3;

            case 'cantos_st':
                if (str_contains($tag, '4.5')) return $totalStCorners >= 5;
                if (str_contains($tag, '3.5')) return $totalStCorners >= 4;
                return $totalStCorners >= 3;

            case 'cantos_ft':
                if (str_contains($tag, '10.5')) return $totalFtCorners >= 11;
                if (str_contains($tag, '9.5')) return $totalFtCorners >= 10;
                if (str_contains($tag, '8.5')) return $totalFtCorners >= 9;
                if (str_contains($tag, '7.5')) return $totalFtCorners >= 8;
                return $totalFtCorners >= 7; // Over 6.5

            case 'gols_ht':
                $targetLine = str_contains($tag, '1.5') ? 2 : 1;
                return $totalHtGoals >= $targetLine;

            case 'gols_st':
                $targetLine = str_contains($tag, '1.5') ? 2 : 1;
                return $totalStGoals >= $targetLine;

            case 'gols_ft':
                $targetLine = str_contains($tag, '2.5') ? 3 : 2;
                return $totalFtGoals >= $targetLine;

            case 'cartoes_ht':
                $targetLine = str_contains($tag, '1.5') ? 2 : 1;
                return $totalHtCards >= $targetLine;

            case 'cartoes_st':
                $targetLine = str_contains($tag, '2.5') ? 3 : 2;
                return $totalStCards >= $targetLine;

            case 'cartoes_ft':
                if (str_contains($tag, '5.5')) return $totalFtCards >= 6;
                if (str_contains($tag, '4.5')) return $totalFtCards >= 5;
                if (str_contains($tag, '3.5')) return $totalFtCards >= 4;
                return $totalFtCards >= 3;

            case 'finalizacoes_ht':
                if (str_contains($tag, '11.5')) return $totalHtShots >= 12;
                if (str_contains($tag, '9.5')) return $totalHtShots >= 10;
                if (str_contains($tag, '8.5')) return $totalHtShots >= 9;
                return $totalHtShots >= 8; // Over 7.5

            case 'finalizacoes_st':
                if (str_contains($tag, '12.5')) return $totalStShots >= 13;
                if (str_contains($tag, '10.5')) return $totalStShots >= 11;
                if (str_contains($tag, '9.5')) return $totalStShots >= 10;
                return $totalStShots >= 9; // Over 8.5

            case 'finalizacoes_ft':
                if (str_contains($tag, '23.5')) return $totalFtShots >= 24;
                if (str_contains($tag, '20.5')) return $totalFtShots >= 21;
                if (str_contains($tag, '18.5')) return $totalFtShots >= 19;
                return $totalFtShots >= 18; // Over 17.5

            case 'finalizacoes_casa_ft':
                if (str_contains($tag, '13.5')) return $hShotsFt >= 14;
                if (str_contains($tag, '11.5')) return $hShotsFt >= 12;
                return $hShotsFt >= 10; // Over 9.5

            case 'finalizacoes_casa_ht':
                if (str_contains($tag, '4.5')) return $hShotsHt >= 5;
                return $hShotsHt >= 4; // Over 3.5

            case 'finalizacoes_fora_ft':
                if (str_contains($tag, '12.5')) return $aShotsFt >= 13;
                if (str_contains($tag, '10.5')) return $aShotsFt >= 11;
                return $aShotsFt >= 9; // Over 8.5

            case 'finalizacoes_fora_ht':
                if (str_contains($tag, '3.5')) return $aShotsHt >= 4;
                return $aShotsHt >= 3; // Over 2.5

            case 'favorito_vence':
                if (str_contains($tag, 'Chance Dupla')) {
                    if (str_contains($tag, '(1X)')) {
                        return $hScoreFt >= $aScoreFt;
                    } else {
                        return $aScoreFt >= $hScoreFt;
                    }
                } else {
                    if (str_contains($tag, '(Mandante)')) {
                        return $hScoreFt > $aScoreFt;
                    } else {
                        return $aScoreFt > $hScoreFt;
                    }
                }

            default:
                return false;
        }
    }

    /**
     * Retorna o resumo amigável do resultado real da partida para exibição
     */
    private function getActualMatchSummary(string $marketKey, array $match): string {
        $hFt = (int)($match['home_score_ft'] ?? 0);
        $aFt = (int)($match['away_score_ft'] ?? 0);
        $hHt = (int)($match['home_score_ht'] ?? 0);
        $aHt = (int)($match['away_score_ht'] ?? 0);

        $hCHt = (int)($match['home_corners_ht'] ?? 0);
        $aCHt = (int)($match['away_corners_ht'] ?? 0);
        $hCFt = (int)($match['home_corners_ft'] ?? 0);
        $aCFt = (int)($match['away_corners_ft'] ?? 0);

        $hYHt = (int)($match['home_yellow_cards_ht'] ?? 0);
        $aYHt = (int)($match['away_yellow_cards_ht'] ?? 0);
        $hYFt = (int)($match['home_yellow_cards_ft'] ?? 0);
        $aYFt = (int)($match['away_yellow_cards_ft'] ?? 0);

        $hSHt = (int)($match['home_shots_ht'] ?? $match['home_shots_on_target_ht'] ?? 0);
        $aSHt = (int)($match['away_shots_ht'] ?? $match['away_shots_on_target_ht'] ?? 0);
        $hSFt = (int)($match['home_shots_ft'] ?? $match['home_shots_on_target_ft'] ?? 0);
        $aSFt = (int)($match['away_shots_ft'] ?? $match['away_shots_on_target_ft'] ?? 0);

        switch ($marketKey) {
            case 'ambos_marcam':
                return "Placar Final: {$hFt} x {$aFt}";
            case 'cantos_ht':
                return "Cantos 1ºT: " . ($hCHt + $aCHt) . " ({$hCHt}-{$aCHt})";
            case 'cantos_st':
                $stCorners = max(0, ($hCFt + $aCFt) - ($hCHt + $aCHt));
                return "Cantos 2ºT: {$stCorners}";
            case 'cantos_ft':
                return "Cantos FT: " . ($hCFt + $aCFt) . " ({$hCFt}-{$aCFt})";
            case 'gols_ht':
                return "Gols 1ºT: " . ($hHt + $aHt);
            case 'gols_st':
                return "Gols 2ºT: " . max(0, ($hFt + $aFt) - ($hHt + $aHt));
            case 'gols_ft':
                return "Placar Final: {$hFt} x {$aFt} (" . ($hFt + $aFt) . " Gols)";
            case 'cartoes_ht':
                return "Cartões 1ºT: " . ($hYHt + $aYHt);
            case 'cartoes_st':
                return "Cartões 2ºT: " . max(0, ($hYFt + $aYFt) - ($hYHt + $aYHt));
            case 'cartoes_ft':
                return "Cartões FT: " . ($hYFt + $aYFt);
            case 'finalizacoes_ht':
                return "Finalizações 1ºT: " . ($hSHt + $aSHt) . " ({$hSHt}-{$aSHt})";
            case 'finalizacoes_st':
                $stShots = max(0, ($hSFt + $aSFt) - ($hSHt + $aSHt));
                return "Finalizações 2ºT: {$stShots}";
            case 'finalizacoes_ft':
                return "Finalizações FT: " . ($hSFt + $aSFt) . " ({$hSFt}-{$aSFt})";
            case 'finalizacoes_casa_ft':
                return "Chutes Mandante FT: {$hSFt}";
            case 'finalizacoes_casa_ht':
                return "Chutes Mandante 1ºT: {$hSHt}";
            case 'finalizacoes_fora_ft':
                return "Chutes Visitante FT: {$aSFt}";
            case 'finalizacoes_fora_ht':
                return "Chutes Visitante 1ºT: {$aSHt}";
            case 'favorito_vence':
                return "Placar Final: {$hFt} x {$aFt}";
            default:
                return "Placar: {$hFt} x {$aFt}";
        }
    }

    private function getRatingLabel(int $confidence): string {
        if ($confidence >= 85) return 'Excelente (Oportunidade de Ouro)';
        if ($confidence >= 75) return 'Muito Alta';
        if ($confidence >= 65) return 'Alta';
        return 'Moderada';
    }
}
