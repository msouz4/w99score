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
                LIMIT 50
            ");
            $stmt->execute();
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $matches;
    }

    /**
     * Analisa todas as partidas para um mercado específico ou todos
     * 
     * Mercados suportados:
     * - all (todos)
     * - ambos_marcam (Ambos Marcam)
     * - cantos_ht (Cantos Primeiro Tempo)
     * - cantos_st (Cantos Segundo Tempo)
     * - cantos_ft (Cantos Tempo Integral)
     * - gols_ht (Gols Primeiro Tempo)
     * - gols_st (Gols Segundo Tempo)
     * - gols_ft (Gols Tempo Integral)
     * - cartoes_ht (Cartões Primeiro Tempo)
     * - cartoes_st (Cartões Segundo Tempo)
     * - cartoes_ft (Cartões Tempo Integral)
     * - favorito_vence (Favorito Vence)
     */
    public function analyzeOpportunities(string $market = 'all', ?string $date = null, int $minConfidence = 55, array $favoriteTeamIds = []): array {
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

            // Mínimo de histórico para análise confiável
            $totalHomeSample = count($homeStatsAll['matches'] ?? []);
            $totalAwaySample = count($awayStatsAll['matches'] ?? []);
            $homeVenueSample = count($homeStatsVenue['matches'] ?? []);
            $awayVenueSample = count($awayStatsVenue['matches'] ?? []);
            $isLowSample     = ($homeVenueSample < 5 || $awayVenueSample < 5);

            if ($totalHomeSample === 0 && $totalAwaySample === 0) {
                continue;
            }

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
                    } else {
                        continue;
                    }
                }


                if ($eval['confidence'] >= $minConfidence) {
                    $opportunities[] = array_merge([
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
                    ], $eval);
                }
            }
        }

        // Ordenar por score de confiança decrescente
        usort($opportunities, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return $opportunities;
    }

    /**
     * Avalia todos os mercados para um jogo específico usando recência ponderada e filtro de consistência
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

        // -------------------------------------------------------------
        // 1. AMBOS MARCAM (BTTS)
        // -------------------------------------------------------------
        $hScored = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['home_score_ft'] ?? 0)) > 0);
        $hConceded = $this->analyzeConditionOnMatches($hMatchesHome, fn($m) => ((int)($m['away_score_ft'] ?? 0)) > 0);
        $aScored = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['away_score_ft'] ?? 0)) > 0);
        $aConceded = $this->analyzeConditionOnMatches($aMatchesAway, fn($m) => ((int)($m['home_score_ft'] ?? 0)) > 0);

        $probHomeScores = ($hScored['weighted_pct'] * 0.6) + ($aConceded['weighted_pct'] * 0.4);
        $probAwayScores = ($aScored['weighted_pct'] * 0.6) + ($hConceded['weighted_pct'] * 0.4);

        $bttsConfidence = round(($probHomeScores + $probAwayScores) / 2);
        $consistencyPct = round(($hScored['pct'] + $aScored['pct'] + $hConceded['pct'] + $aConceded['pct']) / 4);

        if (!empty($h2h)) {
            $h2hBttsCount = 0;
            foreach ($h2h as $hm) {
                if ((int)$hm['home_score_ft'] > 0 && (int)$hm['away_score_ft'] > 0) $h2hBttsCount++;
            }
            $h2hBttsPct = round(($h2hBttsCount / count($h2h)) * 100);
            $bttsConfidence = round(($bttsConfidence * 0.75) + ($h2hBttsPct * 0.25));
        }
        $bttsConfidence = min(98, max(30, $bttsConfidence));

        $hAvgGolsFeitosHome = $hVenue['goals']['feitos']['avg_ft'] ?? 0;
        $aAvgGolsFeitosAway = $aVenue['goals']['feitos']['avg_ft'] ?? 0;

        $bttsStreak = max($hScored['streak'], $aScored['streak']);
        $bttsBadge = ($bttsStreak >= 3) ? "🔥 Sequência de {$bttsStreak} jogos marcando" : (($consistencyPct >= 75) ? "🎯 Consistência {$consistencyPct}%" : null);

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
                "{$hName} marca em casa: {$hScored['pct']}% (Últimos 5: {$hScored['recent_pct']}%)",
                "{$aName} marca fora: {$aScored['pct']}% (Últimos 5: {$aScored['recent_pct']}%)",
                "{$hName} sofreu gols em casa: {$hConceded['pct']}%",
                "{$aName} sofreu gols fora: {$aConceded['pct']}%"
            ],
            'description' => "O **{$hName}** marcou em {$hScored['pct']}% dos seus jogos em casa ({$hScored['recent_pct']}% nos últimos 5). O **{$aName}** balançou as redes em {$aScored['pct']}% como visitante. O cruzamento entre força ofensiva e fragilidade defensiva recente indica alta probabilidade para Ambos Marcam."
        ];

        // -------------------------------------------------------------
        // 2. CANTOS PRIMEIRO TEMPO (HT)
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

        $hHtOver45 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            return (((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0))) >= 5;
        });

        $aHtOver45 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            return (((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0))) >= 5;
        });

        $htConsistency = round(($hHtOver45['pct'] + $aHtOver45['pct']) / 2);
        $cornersHtConfidence = round((($hHtOver45['weighted_pct'] + $aHtOver45['weighted_pct']) / 2) * 0.7 + (min(100, ($expHtCorners / 5.2) * 80) * 0.3));
        $cornersHtConfidence = min(98, max(30, $cornersHtConfidence));

        $targetLineHt = ($expHtCorners >= 5.2) ? 'Mais de 4.5 Cantos HT' : 'Mais de 3.5 Cantos HT';
        $htStreak = max($hHtOver45['streak'], $aHtOver45['streak']);
        $htBadge = ($htStreak >= 3) ? "🔥 5+ cantos no 1ºT em {$htStreak} jogos seguidos" : (($htConsistency >= 75) ? "🎯 Consistência {$htConsistency}%" : null);

        $results['cantos_ht'] = [
            'market_name' => 'Cantos Primeiro Tempo',
            'market_tag' => $targetLineHt,
            'confidence' => $cornersHtConfidence,
            'consistency_pct' => $htConsistency,
            'streak_badge' => $htBadge,
            'recent_form' => $hHtOver45['recent_form'],
            'rating' => $this->getRatingLabel($cornersHtConfidence),
            'badge_color' => '#8b5cf6',
            'main_stat' => "Média {$expHtCorners} Cantos HT",
            'stat_summary' => [
                "Média esperada no 1ºT: {$expHtCorners} escanteios",
                "{$hName} em casa: {$hHtCornersMade} feitos / {$hHtCornersCed} cedidos (1ºT)",
                "{$aName} fora: {$aHtCornersMade} feitos / {$aHtCornersCed} cedidos (1ºT)",
                "Taxa de 5+ cantos 1ºT: {$hHtOver45['pct']}% mandante / {$aHtOver45['pct']}% visitante"
            ],
            'description' => "Volume esperado de **{$expHtCorners} escanteios no 1º Tempo**. O **{$hName}** gera {$hHtCornersMade} e cede {$hHtCornersCed} cantos no 1ºT em seu estádio. O **{$aName}** fora de casa sustenta {$aHtOver45['pct']}% de jogos com volume elevado no HT.",
            'line_config' => [
                'current_line' => ($expHtCorners >= 5.2 ? 4.5 : 3.5),
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Cantos',
                'period_tag' => '1ºT',
                'expected_value' => $expHtCorners,
                'benchmark' => 5.2,
                'weight_pct' => 0.7,
                'weight_exp' => 0.3,
                'h_values' => $hHtCornersValues,
                'a_values' => $aHtCornersValues,
            ]
        ];

        // -------------------------------------------------------------
        // 3. CANTOS SEGUNDO TEMPO (ST)
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

        $hStOver55 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $cHt = ((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0));
            $cFt = ((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0));
            return max(0, $cFt - $cHt) >= 6;
        });

        $aStOver55 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $cHt = ((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0));
            $cFt = ((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0));
            return max(0, $cFt - $cHt) >= 6;
        });

        $stConsistency = round(($hStOver55['pct'] + $aStOver55['pct']) / 2);
        $cornersStConfidence = round((($hStOver55['weighted_pct'] + $aStOver55['weighted_pct']) / 2) * 0.7 + (min(100, ($expStCorners / 5.8) * 80) * 0.3));
        $cornersStConfidence = min(98, max(30, $cornersStConfidence));

        $targetLineSt = ($expStCorners >= 5.5) ? 'Mais de 5.5 Cantos 2ºT' : 'Mais de 4.5 Cantos 2ºT';
        $stStreak = max($hStOver55['streak'], $aStOver55['streak']);
        $stBadge = ($stStreak >= 3) ? "🔥 6+ cantos no 2ºT em {$stStreak} jogos seguidos" : (($stConsistency >= 75) ? "🎯 Consistência {$stConsistency}%" : null);

        $results['cantos_st'] = [
            'market_name' => 'Cantos Segundo Tempo',
            'market_tag' => $targetLineSt,
            'confidence' => $cornersStConfidence,
            'consistency_pct' => $stConsistency,
            'streak_badge' => $stBadge,
            'recent_form' => $hStOver55['recent_form'],
            'rating' => $this->getRatingLabel($cornersStConfidence),
            'badge_color' => '#a855f7',
            'main_stat' => "Média {$expStCorners} Cantos 2ºT",
            'stat_summary' => [
                "Média esperada no 2ºT: {$expStCorners} escanteios",
                "{$hName} em casa no 2ºT: {$hStCornersMade} feitos / {$hStCornersCed} cedidos",
                "{$aName} fora no 2ºT: {$aStCornersMade} feitos / {$aStCornersCed} cedidos",
                "Taxa de 6+ cantos no 2ºT: {$hStOver55['pct']}% mandante / {$aStOver55['pct']}% visitante"
            ],
            'description' => "Projeção de **{$expStCorners} escanteios na etapa complementar**. Ambos os times aceleram o ritmo nos 45 minutos finais, resultando em média de {$hStCornersMade} cantos feitos pelo mandante e {$aStCornersCed} cedidos pelo visitante.",
            'line_config' => [
                'current_line' => ($expStCorners >= 5.5 ? 5.5 : 4.5),
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Cantos',
                'period_tag' => '2ºT',
                'expected_value' => $expStCorners,
                'benchmark' => 5.8,
                'weight_pct' => 0.7,
                'weight_exp' => 0.3,
                'h_values' => $hStCornersValues,
                'a_values' => $aStCornersValues,
            ]
        ];

        // -------------------------------------------------------------
        // 4. CANTOS TEMPO INTEGRAL (FT)
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

        $hFtOver95 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $cFt = ((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0));
            return $cFt >= 10;
        });

        $aFtOver95 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $cFt = ((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0));
            return $cFt >= 10;
        });

        $ftConsistency = round(($hFtOver95['pct'] + $aFtOver95['pct']) / 2);
        $cornersFtConfidence = round((($hFtOver95['weighted_pct'] + $aFtOver95['weighted_pct']) / 2) * 0.7 + (min(100, ($expFtCorners / 10.5) * 80) * 0.3));
        $cornersFtConfidence = min(98, max(30, $cornersFtConfidence));

        $targetLineFt = ($expFtCorners >= 10.5) ? 'Mais de 10.5 Escanteios' : 'Mais de 9.5 Escanteios';
        $ftStreak = max($hFtOver95['streak'], $aFtOver95['streak']);
        $ftBadge = ($ftStreak >= 3) ? "🔥 10+ cantos em {$ftStreak} jogos seguidos" : (($ftConsistency >= 75) ? "🎯 Consistência {$ftConsistency}%" : null);

        $results['cantos_ft'] = [
            'market_name' => 'Cantos Tempo Integral',
            'market_tag' => $targetLineFt,
            'confidence' => $cornersFtConfidence,
            'consistency_pct' => $ftConsistency,
            'streak_badge' => $ftBadge,
            'recent_form' => $hFtOver95['recent_form'],
            'rating' => $this->getRatingLabel($cornersFtConfidence),
            'badge_color' => '#3b82f6',
            'main_stat' => "Média {$expFtCorners} Cantos FT",
            'stat_summary' => [
                "Média combinada FT: {$expFtCorners} escanteios",
                "{$hName} em casa: {$hFtCornersMade} feitos / {$hFtCornersCed} cedidos (Total: {$hFtCornersAvg})",
                "{$aName} fora: {$aFtCornersMade} feitos / {$aFtCornersCed} cedidos (Total: {$aFtCornersAvg})",
                "Taxa de 10+ cantos: {$hFtOver95['pct']}% mandante / {$aFtOver95['pct']}% visitante"
            ],
            'description' => "Expectativa de **{$expFtCorners} escanteios totais** na partida. O **{$hName}** sustenta média de {$hFtCornersAvg} cantos em casa, e o **{$aName}** apresenta média de {$aFtCornersAvg} fora de casa, oferecendo forte solidez de volume.",
            'line_config' => [
                'current_line' => ($expFtCorners >= 10.5 ? 10.5 : 9.5),
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Escanteios',
                'period_tag' => 'FT',
                'expected_value' => $expFtCorners,
                'benchmark' => 10.5,
                'weight_pct' => 0.7,
                'weight_exp' => 0.3,
                'h_values' => $hFtCornersValues,
                'a_values' => $aFtCornersValues,
            ]
        ];

        // -------------------------------------------------------------
        // 5. GOLS PRIMEIRO TEMPO (HT)
        // -------------------------------------------------------------
        $hHtGoalsAvg = (float)($hVenue['goals']['total']['avg_ht'] ?? 0);
        $aHtGoalsAvg = (float)($aVenue['goals']['total']['avg_ht'] ?? 0);
        $expHtGoals = round(($hHtGoalsAvg + $aHtGoalsAvg) / 2, 2);

        $hHtGoalsValues = array_map(fn($m) => ((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0)), $hMatchesHome);
        $aHtGoalsValues = array_map(fn($m) => ((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0)), $aMatchesAway);

        $hHtGoalsOver05 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $gHt = ((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0));
            return $gHt >= 1;
        });

        $aHtGoalsOver05 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $gHt = ((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0));
            return $gHt >= 1;
        });

        $goalsHtConsistency = round(($hHtGoalsOver05['pct'] + $aHtGoalsOver05['pct']) / 2);
        $goalsHtConfidence = round((($hHtGoalsOver05['weighted_pct'] + $aHtGoalsOver05['weighted_pct']) / 2) * 0.75 + (min(100, ($expHtGoals / 1.3) * 80) * 0.25));
        $goalsHtConfidence = min(98, max(30, $goalsHtConfidence));

        $targetLineGoalsHt = ($expHtGoals >= 1.4) ? 'Mais de 1.5 Gols no 1ºT' : 'Mais de 0.5 Gols no 1ºT';
        $gHtStreak = max($hHtGoalsOver05['streak'], $aHtGoalsOver05['streak']);
        $gHtBadge = ($gHtStreak >= 3) ? "🔥 Gol no 1ºT em {$gHtStreak} jogos seguidos" : (($goalsHtConsistency >= 80) ? "🎯 Consistência {$goalsHtConsistency}%" : null);

        $results['gols_ht'] = [
            'market_name' => 'Gols Primeiro Tempo',
            'market_tag' => $targetLineGoalsHt,
            'confidence' => $goalsHtConfidence,
            'consistency_pct' => $goalsHtConsistency,
            'streak_badge' => $gHtBadge,
            'recent_form' => $hHtGoalsOver05['recent_form'],
            'rating' => $this->getRatingLabel($goalsHtConfidence),
            'badge_color' => '#06b6d4',
            'main_stat' => "{$goalsHtConfidence}% Taxa Gol 1ºT",
            'stat_summary' => [
                "Média de gols no 1ºT: {$expHtGoals} gols",
                "{$hName} em casa: {$hHtGoalsOver05['pct']}% de jogos com gol no 1ºT",
                "{$aName} fora: {$aHtGoalsOver05['pct']}% de jogos com gol no 1ºT",
                "Média de gols 1ºT: {$hHtGoalsAvg} mandante / {$aHtGoalsAvg} visitante"
            ],
            'description' => "Em **{$hHtGoalsOver05['pct']}%** das partidas do **{$hName}** em casa e **{$aHtGoalsOver05['pct']}%** do **{$aName}** fora de casa ocorreu pelo menos 1 gol no primeiro tempo, indicando padrão dinâmico desde os minutos iniciais.",
            'line_config' => [
                'current_line' => ($expHtGoals >= 1.4 ? 1.5 : 0.5),
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Gols',
                'period_tag' => '1ºT',
                'expected_value' => $expHtGoals,
                'benchmark' => 1.3,
                'weight_pct' => 0.75,
                'weight_exp' => 0.25,
                'h_values' => $hHtGoalsValues,
                'a_values' => $aHtGoalsValues,
            ]
        ];

        // -------------------------------------------------------------
        // 6. GOLS SEGUNDO TEMPO (ST)
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

        $goalsStConsistency = round(($hStGoalsOver05['pct'] + $aStGoalsOver05['pct']) / 2);
        $goalsStConfidence = round((($hStGoalsOver05['weighted_pct'] + $aStGoalsOver05['weighted_pct']) / 2) * 0.75 + (min(100, ($expStGoals / 1.5) * 80) * 0.25));
        $goalsStConfidence = min(98, max(30, $goalsStConfidence));

        $targetLineGoalsSt = ($expStGoals >= 1.6) ? 'Mais de 1.5 Gols no 2ºT' : 'Mais de 0.5 Gols no 2ºT';
        $gStStreak = max($hStGoalsOver05['streak'], $aStGoalsOver05['streak']);
        $gStBadge = ($gStStreak >= 3) ? "🔥 Gol no 2ºT em {$gStStreak} jogos seguidos" : (($goalsStConsistency >= 80) ? "🎯 Consistência {$goalsStConsistency}%" : null);

        $results['gols_st'] = [
            'market_name' => 'Gols Segundo Tempo',
            'market_tag' => $targetLineGoalsSt,
            'confidence' => $goalsStConfidence,
            'consistency_pct' => $goalsStConsistency,
            'streak_badge' => $gStBadge,
            'recent_form' => $hStGoalsOver05['recent_form'],
            'rating' => $this->getRatingLabel($goalsStConfidence),
            'badge_color' => '#14b8a6',
            'main_stat' => "Média {$expStGoals} Gols 2ºT",
            'stat_summary' => [
                "Média de gols no 2ºT: {$expStGoals} gols",
                "{$hName} em casa: {$hStGoalsOver05['pct']}% de jogos com gol no 2ºT",
                "{$aName} fora: {$aStGoalsOver05['pct']}% de jogos com gol no 2ºT",
                "Gols 2ºT Feitos/Cedidos: Mandante {$hVenue['goals']['feitos']['avg_st']} | Visitante {$aVenue['goals']['cedidos']['avg_st']}"
            ],
            'description' => "Projeção de **{$expStGoals} gols na segunda etapa**. O mandante registrou gols no 2ºT em {$hStGoalsOver05['pct']}% dos jogos em casa e a defesa visitante cedeu em {$aStGoalsOver05['pct']}% das suas atuações.",
            'line_config' => [
                'current_line' => ($expStGoals >= 1.6 ? 1.5 : 0.5),
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Gols',
                'period_tag' => '2ºT',
                'expected_value' => $expStGoals,
                'benchmark' => 1.5,
                'weight_pct' => 0.75,
                'weight_exp' => 0.25,
                'h_values' => $hStGoalsValues,
                'a_values' => $aStGoalsValues,
            ]
        ];

        // -------------------------------------------------------------
        // 7. GOLS TEMPO INTEGRAL (FT)
        // -------------------------------------------------------------
        $hFtGoalsAvg = (float)($hVenue['goals']['total']['avg_ft'] ?? 0);
        $aFtGoalsAvg = (float)($aVenue['goals']['total']['avg_ft'] ?? 0);
        $expFtGoals = round(($hFtGoalsAvg + $aFtGoalsAvg) / 2, 2);

        $hFtGoalsValues = array_map(fn($m) => ((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0)), $hMatchesHome);
        $aFtGoalsValues = array_map(fn($m) => ((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0)), $aMatchesAway);

        $hFtOver25 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $gFt = ((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0));
            return $gFt >= 3;
        });

        $aFtOver25 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $gFt = ((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0));
            return $gFt >= 3;
        });

        $goalsFtConsistency = round(($hFtOver25['pct'] + $aFtOver25['pct']) / 2);
        $goalsFtConfidence = round((($hFtOver25['weighted_pct'] + $aFtOver25['weighted_pct']) / 2) * 0.7 + (min(100, ($expFtGoals / 2.7) * 80) * 0.3));
        $goalsFtConfidence = min(98, max(30, $goalsFtConfidence));

        $targetLineGoalsFt = ($expFtGoals >= 2.6) ? 'Mais de 2.5 Gols FT' : 'Mais de 1.5 Gols FT';
        $gFtStreak = max($hFtOver25['streak'], $aFtOver25['streak']);
        $gFtBadge = ($gFtStreak >= 3) ? "🔥 Over Gols em {$gFtStreak} jogos seguidos" : (($goalsFtConsistency >= 75) ? "🎯 Consistência {$goalsFtConsistency}%" : null);

        $results['gols_ft'] = [
            'market_name' => 'Gols Tempo Integral',
            'market_tag' => $targetLineGoalsFt,
            'confidence' => $goalsFtConfidence,
            'consistency_pct' => $goalsFtConsistency,
            'streak_badge' => $gFtBadge,
            'recent_form' => $hFtOver25['recent_form'],
            'rating' => $this->getRatingLabel($goalsFtConfidence),
            'badge_color' => '#f59e0b',
            'main_stat' => "Média {$expFtGoals} Gols/Jogo",
            'stat_summary' => [
                "Média combinada FT: {$expFtGoals} gols por jogo",
                "{$hName} em casa: média {$hFtGoalsAvg} gols ({$hFtOver25['pct']}% Over 2.5)",
                "{$aName} fora: média {$aFtGoalsAvg} gols ({$aFtOver25['pct']}% Over 2.5)",
                "Recência ponderada: {$hFtOver25['recent_pct']}% casa / {$aFtOver25['recent_pct']}% fora"
            ],
            'description' => "Partidas com média combinada de **{$expFtGoals} gols totais**. O **{$hName}** tem ataque eficiente jogando em seu estádio e o **{$aName}** cede espaço defensivo como visitante.",
            'line_config' => [
                'current_line' => ($expFtGoals >= 2.6 ? 2.5 : 1.5),
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Gols',
                'period_tag' => 'FT',
                'expected_value' => $expFtGoals,
                'benchmark' => 2.7,
                'weight_pct' => 0.7,
                'weight_exp' => 0.3,
                'h_values' => $hFtGoalsValues,
                'a_values' => $aFtGoalsValues,
            ]
        ];

        // -------------------------------------------------------------
        // 8. CARTÕES PRIMEIRO TEMPO (HT)
        // -------------------------------------------------------------
        $hHtCardsAvg = (float)($hVenue['yellow_cards']['total']['avg_ht'] ?? 0);
        $aHtCardsAvg = (float)($aVenue['yellow_cards']['total']['avg_ht'] ?? 0);
        $expHtCards = round(($hHtCardsAvg + $aHtCardsAvg) / 2, 2);

        $hHtCardsValues = array_map(fn($m) => ((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0)), $hMatchesHome);
        $aHtCardsValues = array_map(fn($m) => ((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0)), $aMatchesAway);

        $hHtCardsOver15 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $ht = ((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0));
            return $ht >= 2;
        });

        $aHtCardsOver15 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $ht = ((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0));
            return $ht >= 2;
        });

        $cardsHtConsistency = round(($hHtCardsOver15['pct'] + $aHtCardsOver15['pct']) / 2);
        $cardsHtConfidence = round((($hHtCardsOver15['weighted_pct'] + $aHtCardsOver15['weighted_pct']) / 2) * 0.7 + (min(100, ($expHtCards / 2.0) * 80) * 0.3));
        $cardsHtConfidence = min(98, max(30, $cardsHtConfidence));

        $targetLineCardsHt = ($expHtCards >= 2.0) ? 'Mais de 2.5 Cartões HT' : (($expHtCards >= 1.2) ? 'Mais de 1.5 Cartões HT' : 'Mais de 0.5 Cartões HT');
        $cHtStreak = max($hHtCardsOver15['streak'], $aHtCardsOver15['streak']);
        $cHtBadge = ($cHtStreak >= 3) ? "🔥 Cartão no 1ºT em {$cHtStreak} jogos seguidos" : (($cardsHtConsistency >= 75) ? "🎯 Consistência {$cardsHtConsistency}%" : null);

        $results['cartoes_ht'] = [
            'market_name' => 'Cartões Primeiro Tempo',
            'market_tag' => $targetLineCardsHt,
            'confidence' => $cardsHtConfidence,
            'consistency_pct' => $cardsHtConsistency,
            'streak_badge' => $cHtBadge,
            'recent_form' => $hHtCardsOver15['recent_form'],
            'rating' => $this->getRatingLabel($cardsHtConfidence),
            'badge_color' => '#eab308',
            'main_stat' => "Média {$expHtCards} Cartões HT",
            'stat_summary' => [
                "Média esperada no 1ºT: {$expHtCards} cartões",
                "Mandante em casa no 1ºT: {$hVenue['yellow_cards']['feitos']['avg_ht']} recebidos / {$hVenue['yellow_cards']['cedidos']['avg_ht']} provocados",
                "Visitante fora no 1ºT: {$aVenue['yellow_cards']['feitos']['avg_ht']} recebidos / {$aVenue['yellow_cards']['cedidos']['avg_ht']} provocados",
                "Taxa de 2+ cartões 1ºT: {$hHtCardsOver15['pct']}% mandante / {$aHtCardsOver15['pct']}% visitante"
            ],
            'description' => "Média combinada de **{$expHtCards} cartões na etapa inicial**. Ambas as equipes apresentam número elevado de faltas e cartões acumulados no 1º Tempo.",
            'line_config' => [
                'current_line' => ($expHtCards >= 2.0 ? 2.5 : ($expHtCards >= 1.2 ? 1.5 : 0.5)),
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Cartões',
                'period_tag' => '1ºT',
                'expected_value' => $expHtCards,
                'benchmark' => 2.0,
                'weight_pct' => 0.7,
                'weight_exp' => 0.3,
                'h_values' => $hHtCardsValues,
                'a_values' => $aHtCardsValues,
            ]
        ];

        // -------------------------------------------------------------
        // 9. CARTÕES SEGUNDO TEMPO (ST)
        // -------------------------------------------------------------
        $hStCardsAvg = (float)($hVenue['yellow_cards']['total']['avg_st'] ?? 0);
        $aStCardsAvg = (float)($aVenue['yellow_cards']['total']['avg_st'] ?? 0);
        $expStCards = round(($hStCardsAvg + $aStCardsAvg) / 2, 2);

        $hStCardsValues = array_map(fn($m) => max(0, (((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0))) - (((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0)))), $hMatchesHome);
        $aStCardsValues = array_map(fn($m) => max(0, (((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0))) - (((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0)))), $aMatchesAway);

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

        $cardsStConsistency = round(($hStCardsOver25['pct'] + $aStCardsOver25['pct']) / 2);
        $cardsStConfidence = round((($hStCardsOver25['weighted_pct'] + $aStCardsOver25['weighted_pct']) / 2) * 0.7 + (min(100, ($expStCards / 2.8) * 80) * 0.3));
        $cardsStConfidence = min(98, max(30, $cardsStConfidence));

        $targetLineCardsSt = ($expStCards >= 2.6) ? 'Mais de 2.5 Cartões 2ºT' : 'Mais de 1.5 Cartões 2ºT';
        $cStStreak = max($hStCardsOver25['streak'], $aStCardsOver25['streak']);
        $cStBadge = ($cStStreak >= 3) ? "🔥 Cartão no 2ºT em {$cStStreak} jogos seguidos" : (($cardsStConsistency >= 75) ? "🎯 Consistência {$cardsStConsistency}%" : null);

        $results['cartoes_st'] = [
            'market_name' => 'Cartões Segundo Tempo',
            'market_tag' => $targetLineCardsSt,
            'confidence' => $cardsStConfidence,
            'consistency_pct' => $cardsStConsistency,
            'streak_badge' => $cStBadge,
            'recent_form' => $hStCardsOver25['recent_form'],
            'rating' => $this->getRatingLabel($cardsStConfidence),
            'badge_color' => '#f97316',
            'main_stat' => "Média {$expStCorners} Cartões 2ºT",
            'stat_summary' => [
                "Média esperada no 2ºT: {$expStCards} cartões",
                "Mandante 2ºT: {$hVenue['yellow_cards']['feitos']['avg_st']} recebidos / {$hVenue['yellow_cards']['cedidos']['avg_st']} provocados",
                "Visitante 2ºT: {$aVenue['yellow_cards']['feitos']['avg_st']} recebidos / {$aVenue['yellow_cards']['cedidos']['avg_st']} provocados",
                "Taxa de 3+ cartões 2ºT: {$hStCardsOver25['pct']}% mandante / {$aStCardsOver25['pct']}% visitante"
            ],
            'description' => "Projeção de **{$expStCards} cartões no 2º Tempo**. O clima de reta final de jogo gera maior atrito com {$hStCardsOver25['pct']}% de partidas com 3+ cartões no 2ºT para o mandante.",
            'line_config' => [
                'current_line' => ($expStCards >= 2.6 ? 2.5 : 1.5),
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Cartões',
                'period_tag' => '2ºT',
                'expected_value' => $expStCards,
                'benchmark' => 2.8,
                'weight_pct' => 0.7,
                'weight_exp' => 0.3,
                'h_values' => $hStCardsValues,
                'a_values' => $aStCardsValues,
            ]
        ];

        // -------------------------------------------------------------
        // 10. CARTÕES TEMPO INTEGRAL (FT)
        // -------------------------------------------------------------
        $hFtCardsAvg = (float)($hVenue['yellow_cards']['total']['avg_ft'] ?? 0);
        $aFtCardsAvg = (float)($aVenue['yellow_cards']['total']['avg_ft'] ?? 0);
        $expFtCards = round(($hFtCardsAvg + $aFtCardsAvg) / 2, 2);

        $hFtCardsValues = array_map(fn($m) => ((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0)), $hMatchesHome);
        $aFtCardsValues = array_map(fn($m) => ((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0)), $aMatchesAway);

        $hFtCardsOver45 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $cFt = ((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0));
            return $cFt >= 5;
        });

        $aFtCardsOver45 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $cFt = ((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0));
            return $cFt >= 5;
        });

        $cardsFtConsistency = round(($hFtCardsOver45['pct'] + $aFtCardsOver45['pct']) / 2);
        $cardsFtConfidence = round((($hFtCardsOver45['weighted_pct'] + $aFtCardsOver45['weighted_pct']) / 2) * 0.7 + (min(100, ($expFtCards / 5.2) * 80) * 0.3));
        $cardsFtConfidence = min(98, max(30, $cardsFtConfidence));

        $targetLineCardsFt = ($expFtCards >= 5.5) ? 'Mais de 5.5 Cartões' : (($expFtCards >= 4.3) ? 'Mais de 4.5 Cartões' : 'Mais de 3.5 Cartões');
        $cFtStreak = max($hFtCardsOver45['streak'], $aFtCardsOver45['streak']);
        $cFtBadge = ($cFtStreak >= 3) ? "🔥 5+ cartões em {$cFtStreak} jogos seguidos" : (($cardsFtConsistency >= 75) ? "🎯 Consistência {$cardsFtConsistency}%" : null);

        $results['cartoes_ft'] = [
            'market_name' => 'Cartões Tempo Integral',
            'market_tag' => $targetLineCardsFt,
            'confidence' => $cardsFtConfidence,
            'consistency_pct' => $cardsFtConsistency,
            'streak_badge' => $cFtBadge,
            'recent_form' => $hFtCardsOver45['recent_form'],
            'rating' => $this->getRatingLabel($cardsFtConfidence),
            'badge_color' => '#eab308',
            'main_stat' => "Média {$expFtCards} Cartões FT",
            'stat_summary' => [
                "Média combinada FT: {$expFtCards} cartões por jogo",
                "Mandante em casa: {$hVenue['yellow_cards']['feitos']['avg_ft']} recebidos / {$hVenue['yellow_cards']['cedidos']['avg_ft']} provocados",
                "Visitante fora: {$aVenue['yellow_cards']['feitos']['avg_ft']} recebidos / {$aVenue['yellow_cards']['cedidos']['avg_ft']} provocados",
                "Taxa de 5+ cartões: {$hFtCardsOver45['pct']}% casa / {$aFtCardsOver45['pct']}% fora"
            ],
            'description' => "Projeção de **{$expFtCards} cartões no jogo**. O **{$hName}** apresenta média de {$hFtCardsAvg} advertências em jogos em casa, e o **{$aFtCardsAvg}** tem média de {$aFtCardsAvg} fora.",
            'line_config' => [
                'current_line' => ($expFtCards >= 5.5 ? 5.5 : ($expFtCards >= 4.3 ? 4.5 : 3.5)),
                'step' => 1.0,
                'min_line' => 0.5,
                'unit' => 'Cartões',
                'period_tag' => 'FT',
                'expected_value' => $expFtCards,
                'benchmark' => 5.2,
                'weight_pct' => 0.7,
                'weight_exp' => 0.3,
                'h_values' => $hFtCardsValues,
                'a_values' => $aFtCardsValues,
            ]
        ];

        // -------------------------------------------------------------
        // 11. FAVORITO VENCE (Moneyline)
        // -------------------------------------------------------------
        $hHomeWinCond = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            return ((int)($m['home_score_ft'] ?? 0)) > ((int)($m['away_score_ft'] ?? 0));
        });

        $aAwayWinCond = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            return ((int)($m['away_score_ft'] ?? 0)) > ((int)($m['home_score_ft'] ?? 0));
        });

        $aAwayLossCond = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            return ((int)($m['home_score_ft'] ?? 0)) > ((int)($m['away_score_ft'] ?? 0));
        });

        $hHomeLossCond = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            return ((int)($m['away_score_ft'] ?? 0)) > ((int)($m['home_score_ft'] ?? 0));
        });

        $hWinPct = $hHomeWinCond['pct'];
        $aWinPct = $aAwayWinCond['pct'];
        $aLossPct = $aAwayLossCond['pct'];

        $hPower = ($hWinPct * 0.6) + ($aLossPct * 0.4) + (($hVenue['goals']['feitos']['avg_ft'] - $hVenue['goals']['cedidos']['avg_ft']) * 10);
        $aPower = ($aWinPct * 0.6) + ($hHomeLossCond['pct'] * 0.4) + (($aVenue['goals']['feitos']['avg_ft'] - $aVenue['goals']['cedidos']['avg_ft']) * 10);

        $isHomeFav = $hPower >= $aPower;
        $favTeamName = $isHomeFav ? $hName : $aName;
        $favRole = $isHomeFav ? 'Mandante' : 'Visitante';
        $favWinPct = $isHomeFav ? $hWinPct : $aWinPct;
        $underdogLossPct = $isHomeFav ? $aLossPct : $hHomeLossCond['pct'];
        $favForm = $isHomeFav ? $hHomeWinCond['recent_form'] : $aAwayWinCond['recent_form'];

        $favConfidence = round(($favWinPct * 0.6) + ($underdogLossPct * 0.4));
        $favConfidence = min(96, max(45, $favConfidence));
        $favStreak = $isHomeFav ? $hHomeWinCond['streak'] : $aAwayWinCond['streak'];

        $favBadge = ($favStreak >= 3) ? "🔥 Sequência de {$favStreak} vitórias" : (($favWinPct >= 70) ? "🎯 Consistência {$favWinPct}% Vitórias" : null);

        $results['favorito_vence'] = [
            'market_name' => 'Favorito Vence',
            'market_tag' => "Vitória: {$favTeamName} ({$favRole})",
            'confidence' => $favConfidence,
            'consistency_pct' => $favWinPct,
            'streak_badge' => $favBadge,
            'recent_form' => $favForm,
            'rating' => $this->getRatingLabel($favConfidence),
            'badge_color' => '#22c55e',
            'main_stat' => "{$favConfidence}% Favoritismo",
            'stat_summary' => [
                "{$favTeamName} ({$favRole}): {$favWinPct}% vitórias no retrospecto",
                "Adversário: {$underdogLossPct}% derrotas",
                "Recência ponderada: {$hHomeWinCond['recent_pct']}% casa / {$aAwayWinCond['recent_pct']}% fora"
            ],
            'description' => "O **{$favTeamName}** entra como favorito ({$favConfidence}% de probabilidade calculada). Jogando como {$favRole}, a equipe sustenta **{$favWinPct}% de vitórias** no estádio, enquanto o adversário sofreu derrotas em **{$underdogLossPct}%** das suas atuações."
        ];

        // -------------------------------------------------------------
        // 12. FINALIZAÇÕES PRIMEIRO TEMPO (HT)
        // -------------------------------------------------------------
        $hHtShotsMade = (float)($hVenue['shots']['feitos']['avg_ht'] ?? 0);
        $hHtShotsCed  = (float)($hVenue['shots']['cedidos']['avg_ht'] ?? 0);
        $aHtShotsMade = (float)($aVenue['shots']['feitos']['avg_ht'] ?? 0);
        $aHtShotsCed  = (float)($aVenue['shots']['cedidos']['avg_ht'] ?? 0);

        $expHomeHtShots = round(($hHtShotsMade + $aHtShotsCed) / 2, 2);
        $expAwayHtShots = round(($aHtShotsMade + $hHtShotsCed) / 2, 2);
        $expHtShots     = round($expHomeHtShots + $expAwayHtShots, 2);

        $hHtShotsOver10 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $ht = ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0));
            return $ht >= 10;
        });

        $aHtShotsOver10 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $ht = ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0));
            return $ht >= 10;
        });

        $shotsHtConsistency = round(($hHtShotsOver10['pct'] + $aHtShotsOver10['pct']) / 2);
        $shotsHtConfidence = round((($hHtShotsOver10['weighted_pct'] + $aHtShotsOver10['weighted_pct']) / 2) * 0.7 + (min(100, ($expHtShots / 11.5) * 80) * 0.3));
        $shotsHtConfidence = min(98, max(30, $shotsHtConfidence));

        $lineValShotsHt = ($expHtShots >= 11.5) ? 11.5 : 9.5;
        $targetLineShotsHt = 'Mais de ' . $lineValShotsHt . ' Finalizações HT';
        $sHtStreak = max($hHtShotsOver10['streak'], $aHtShotsOver10['streak']);
        $sHtBadge = ($sHtStreak >= 3) ? "🔥 10+ finalizações no 1ºT em {$sHtStreak} jogos seguidos" : (($shotsHtConsistency >= 75) ? "🎯 Consistência {$shotsHtConsistency}%" : null);

        $results['finalizacoes_ht'] = [
            'market_name' => 'Finalizações Primeiro Tempo',
            'market_tag' => $targetLineShotsHt,
            'confidence' => $shotsHtConfidence,
            'consistency_pct' => $shotsHtConsistency,
            'streak_badge' => $sHtBadge,
            'recent_form' => $hHtShotsOver10['recent_form'],
            'rating' => $this->getRatingLabel($shotsHtConfidence),
            'badge_color' => '#ec4899',
            'main_stat' => "Média {$expHtShots} Chutes HT",
            'stat_summary' => [
                "Média esperada no 1ºT: {$expHtShots} finalizações ({$expHomeHtShots} mandante / {$expAwayHtShots} visitante)",
                "Mandante em casa no 1ºT: {$hHtShotsMade} feitos / {$hHtShotsCed} cedidos",
                "Visitante fora no 1ºT: {$aHtShotsMade} feitos / {$aHtShotsCed} cedidos",
                "Taxa de 10+ chutes 1ºT: {$hHtShotsOver10['pct']}% mandante / {$aHtShotsOver10['pct']}% visitante"
            ],
            'description' => "Projeção de **{$expHtShots} finalizações na etapa inicial** ({$expHomeHtShots} esperadas do **{$hName}** e {$expAwayHtShots} do **{$aName}**). O mandante faz {$hHtShotsMade} e cede {$hHtShotsCed} no 1ºT em seu estádio.",
            'line_config' => [
                'current_line' => $lineValShotsHt,
                'benchmark' => 11.5,
                'weight_pct' => 0.7,
                'weight_exp' => 0.3,
                'h_values' => array_values(array_map(fn($m) => ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0)), $hMatchesHome)),
                'a_values' => array_values(array_map(fn($m) => ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0)), $aMatchesAway)),
                'expected_value' => $expHtShots,
                'unit' => 'Finalizações HT',
                'period_tag' => 'HT'
            ]
        ];

        // -------------------------------------------------------------
        // 13. FINALIZAÇÕES SEGUNDO TEMPO (ST)
        // -------------------------------------------------------------
        $hStShotsMade = (float)($hVenue['shots']['feitos']['avg_st'] ?? 0);
        $hStShotsCed  = (float)($hVenue['shots']['cedidos']['avg_st'] ?? 0);
        $aStShotsMade = (float)($aVenue['shots']['feitos']['avg_st'] ?? 0);
        $aStShotsCed  = (float)($aVenue['shots']['cedidos']['avg_st'] ?? 0);

        $expHomeStShots = round(($hStShotsMade + $aStShotsCed) / 2, 2);
        $expAwayStShots = round(($aStShotsMade + $hStShotsCed) / 2, 2);
        $expStShots     = round($expHomeStShots + $expAwayStShots, 2);

        $hStShotsOver11 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $sHt = ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0));
            $sFt = ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0));
            return max(0, $sFt - $sHt) >= 11;
        });

        $aStShotsOver11 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $sHt = ((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0));
            $sFt = ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0));
            return max(0, $sFt - $sHt) >= 11;
        });

        $shotsStConsistency = round(($hStShotsOver11['pct'] + $aStShotsOver11['pct']) / 2);
        $shotsStConfidence = round((($hStShotsOver11['weighted_pct'] + $aStShotsOver11['weighted_pct']) / 2) * 0.7 + (min(100, ($expStShots / 12.5) * 80) * 0.3));
        $shotsStConfidence = min(98, max(30, $shotsStConfidence));

        $lineValShotsSt = ($expStShots >= 12.5) ? 12.5 : 10.5;
        $targetLineShotsSt = 'Mais de ' . $lineValShotsSt . ' Finalizações 2ºT';
        $sStStreak = max($hStShotsOver11['streak'], $aStShotsOver11['streak']);
        $sStBadge = ($sStStreak >= 3) ? "🔥 11+ finalizações no 2ºT em {$sStStreak} jogos seguidos" : (($shotsStConsistency >= 75) ? "🎯 Consistência {$shotsStConsistency}%" : null);

        $results['finalizacoes_st'] = [
            'market_name' => 'Finalizações Segundo Tempo',
            'market_tag' => $targetLineShotsSt,
            'confidence' => $shotsStConfidence,
            'consistency_pct' => $shotsStConsistency,
            'streak_badge' => $sStBadge,
            'recent_form' => $hStShotsOver11['recent_form'],
            'rating' => $this->getRatingLabel($shotsStConfidence),
            'badge_color' => '#d946ef',
            'main_stat' => "Média {$expStShots} Chutes 2ºT",
            'stat_summary' => [
                "Média esperada no 2ºT: {$expStShots} finalizações ({$expHomeStShots} mandante / {$expAwayStShots} visitante)",
                "Mandante 2ºT: {$hStShotsMade} feitos / {$hStShotsCed} cedidos",
                "Visitante 2ºT: {$aStShotsMade} feitos / {$aStShotsCed} cedidos",
                "Taxa de 11+ chutes no 2ºT: {$hStShotsOver11['pct']}% mandante / {$aStShotsOver11['pct']}% visitante"
            ],
            'description' => "Volume projetado de **{$expStShots} chutes no segundo tempo** ({$expHomeStShots} esperados do **{$hName}** e {$expAwayStShots} do **{$aName}**). As equipes aceleram o ritmo na etapa final.",
            'line_config' => [
                'current_line' => $lineValShotsSt,
                'benchmark' => 12.5,
                'weight_pct' => 0.7,
                'weight_exp' => 0.3,
                'h_values' => array_values(array_map(fn($m) => max(0, (((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0))) - (((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0)))), $hMatchesHome)),
                'a_values' => array_values(array_map(fn($m) => max(0, (((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0))) - (((int)($m['home_shots_ht'] ?? $m['home_shots_on_target_ht'] ?? 0)) + ((int)($m['away_shots_ht'] ?? $m['away_shots_on_target_ht'] ?? 0)))), $aMatchesAway)),
                'expected_value' => $expStShots,
                'unit' => 'Finalizações 2ºT',
                'period_tag' => 'ST'
            ]
        ];

        // -------------------------------------------------------------
        // 14. FINALIZAÇÕES TEMPO INTEGRAL (FT)
        // -------------------------------------------------------------
        $hFtShotsMade = (float)($hVenue['shots']['feitos']['avg_ft'] ?? 0);
        $hFtShotsCed  = (float)($hVenue['shots']['cedidos']['avg_ft'] ?? 0);
        $aFtShotsMade = (float)($aVenue['shots']['feitos']['avg_ft'] ?? 0);
        $aFtShotsCed  = (float)($aVenue['shots']['cedidos']['avg_ft'] ?? 0);

        $expHomeFtShots = round(($hFtShotsMade + $aFtShotsCed) / 2, 2);
        $expAwayFtShots = round(($aFtShotsMade + $hFtShotsCed) / 2, 2);
        $expFtShots     = round($expHomeFtShots + $expAwayFtShots, 2);

        $hFtShotsOver22 = $this->analyzeConditionOnMatches($hMatchesHome, function($m) {
            $sFt = ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0));
            return $sFt >= 23;
        });

        $aFtShotsOver22 = $this->analyzeConditionOnMatches($aMatchesAway, function($m) {
            $sFt = ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0));
            return $sFt >= 23;
        });

        $shotsFtConsistency = round(($hFtShotsOver22['pct'] + $aFtShotsOver22['pct']) / 2);
        $shotsFtConfidence = round((($hFtShotsOver22['weighted_pct'] + $aFtShotsOver22['weighted_pct']) / 2) * 0.7 + (min(100, ($expFtShots / 24.0) * 80) * 0.3));
        $shotsFtConfidence = min(98, max(30, $shotsFtConfidence));

        $lineValShotsFt = ($expFtShots >= 24.0) ? 23.5 : 20.5;
        $targetLineShotsFt = 'Mais de ' . $lineValShotsFt . ' Finalizações';
        $sFtStreak = max($hFtShotsOver22['streak'], $aFtShotsOver22['streak']);
        $sFtBadge = ($sFtStreak >= 3) ? "🔥 23+ finalizações em {$sFtStreak} jogos seguidos" : (($shotsFtConsistency >= 75) ? "🎯 Consistência {$shotsFtConsistency}%" : null);

        $results['finalizacoes_ft'] = [
            'market_name' => 'Finalizações Tempo Integral',
            'market_tag' => $targetLineShotsFt,
            'confidence' => $shotsFtConfidence,
            'consistency_pct' => $shotsFtConsistency,
            'streak_badge' => $sFtBadge,
            'recent_form' => $hFtShotsOver22['recent_form'],
            'rating' => $this->getRatingLabel($shotsFtConfidence),
            'badge_color' => '#f43f5e',
            'main_stat' => "Média {$expFtShots} Chutes FT",
            'stat_summary' => [
                "Média esperada na partida: {$expFtShots} finalizações ({$expHomeFtShots} mandante / {$expAwayFtShots} visitante)",
                "{$hName} em casa: {$hFtShotsMade} feitos / {$hFtShotsCed} cedidos",
                "{$aName} fora: {$aFtShotsMade} feitos / {$aFtShotsCed} cedidos",
                "Taxa de 23+ finalizações: {$hFtShotsOver22['pct']}% casa / {$aFtShotsOver22['pct']}% fora"
            ],
            'description' => "Projeção de **{$expFtShots} finalizações totais na partida** ({$expHomeFtShots} esperadas do **{$hName}** e {$expAwayFtShots} do **{$aName}**). O mandante registra {$hFtShotsMade} feitos e {$hFtShotsCed} cedidos em casa, enquanto o visitante faz {$aFtShotsMade} e cede {$aFtShotsCed} fora.",
            'line_config' => [
                'current_line' => $lineValShotsFt,
                'benchmark' => 24.0,
                'weight_pct' => 0.7,
                'weight_exp' => 0.3,
                'h_values' => array_values(array_map(fn($m) => ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0)), $hMatchesHome)),
                'a_values' => array_values(array_map(fn($m) => ((int)($m['home_shots_ft'] ?? $m['home_shots_on_target_ft'] ?? 0)) + ((int)($m['away_shots_ft'] ?? $m['away_shots_on_target_ft'] ?? 0)), $aMatchesAway)),
                'expected_value' => $expFtShots,
                'unit' => 'Finalizações',
                'period_tag' => 'FT'
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

        $totalCount = count($matches);
        $results = [];
        foreach ($matches as $m) {
            $results[] = (bool)$condition($m);
        }

        $overallHitCount = count(array_filter($results));
        $overallPct = round(($overallHitCount / $totalCount) * 100);

        $recentResults = array_slice($results, 0, 5);
        $recentCount = count($recentResults);
        $recentHitCount = count(array_filter($recentResults));
        $recentPct = $recentCount > 0 ? round(($recentHitCount / $recentCount) * 100) : $overallPct;

        if ($totalCount > 5) {
            $olderResults = array_slice($results, 5);
            $olderPct = round((count(array_filter($olderResults)) / count($olderResults)) * 100);
            $weightedPct = round(($recentPct * 0.65) + ($olderPct * 0.35));
        } else {
            $weightedPct = $recentPct;
        }

        $streak = 0;
        foreach ($results as $res) {
            if ($res) {
                $streak++;
            } else {
                break;
            }
        }

        return [
            'pct' => $overallPct,
            'recent_pct' => $recentPct,
            'weighted_pct' => $weightedPct,
            'recent_form' => array_reverse($recentResults),
            'streak' => $streak
        ];
    }

    /**
     * Realiza o backtest/auditoria de assertividade em partidas finalizadas
     */
    public function analyzeFinishedMatchesBacktest(
        string $market = 'all',
        string $dateRange = 'month',
        int $minConfidence = 80,
        int $tournamentId = 0,
        array $favoriteTeamIds = []
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

        if ($dateRange === '7days') {
            $whereSql .= " AND match_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } elseif ($dateRange === '30days' || $dateRange === 'month') {
            $whereSql .= " AND match_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM matches 
            WHERE {$whereSql}
            ORDER BY start_timestamp DESC
            LIMIT 200
        ");
        $stmt->execute($params);
        $finishedMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $auditedPredictions = [];
        $marketStats = [];

        foreach ($finishedMatches as $match) {
            $homeId = (int)$match['home_team_id'];
            $awayId = (int)$match['away_team_id'];
            if (!$homeId || !$awayId) continue;

            $matchEventId = (int)$match['sofascore_event_id'];
            $matchTimestamp = (int)($match['start_timestamp'] ?? 0);

            $homeStatsVenue = $this->sync->getTeamVenueStats($homeId, 'home', true, $matchEventId, $matchTimestamp);
            $homeStatsAll   = $this->sync->getTeamVenueStats($homeId, 'all', true, $matchEventId, $matchTimestamp);
            $awayStatsVenue = $this->sync->getTeamVenueStats($awayId, 'away', true, $matchEventId, $matchTimestamp);
            $awayStatsAll   = $this->sync->getTeamVenueStats($awayId, 'all', true, $matchEventId, $matchTimestamp);
            $h2hMatches     = $this->sync->getH2HMatches($homeId, $awayId, $matchEventId, $matchTimestamp);

            // Mínimo de histórico para análise confiável (EXATAMENTE IGUAL a analyzeOpportunities)
            $totalHomeSample = count($homeStatsAll['matches'] ?? []);
            $totalAwaySample = count($awayStatsAll['matches'] ?? []);
            $homeVenueSample = count($homeStatsVenue['matches'] ?? []);
            $awayVenueSample = count($awayStatsVenue['matches'] ?? []);
            $isLowSample     = ($homeVenueSample < 5 || $awayVenueSample < 5);

            if ($totalHomeSample === 0 && $totalAwaySample === 0) {
                continue;
            }

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

        switch ($marketKey) {
            case 'ambos_marcam':
                return $hScoreFt > 0 && $aScoreFt > 0;

            case 'cantos_ht':
                $targetLine = str_contains($eval['market_tag'], '4.5') ? 5 : 4;
                return $totalHtCorners >= $targetLine;

            case 'cantos_st':
                $targetLine = str_contains($eval['market_tag'], '5.5') ? 6 : 5;
                return $totalStCorners >= $targetLine;

            case 'cantos_ft':
                $targetLine = str_contains($eval['market_tag'], '10.5') ? 11 : 10;
                return $totalFtCorners >= $targetLine;

            case 'gols_ht':
                $targetLine = str_contains($eval['market_tag'], '1.5') ? 2 : 1;
                return $totalHtGoals >= $targetLine;

            case 'gols_st':
                $targetLine = str_contains($eval['market_tag'], '1.5') ? 2 : 1;
                return $totalStGoals >= $targetLine;

            case 'gols_ft':
                $targetLine = str_contains($eval['market_tag'], '2.5') ? 3 : 2;
                return $totalFtGoals >= $targetLine;

            case 'cartoes_ht':
                $targetLine = str_contains($eval['market_tag'], '2.5') ? 3 : (str_contains($eval['market_tag'], '1.5') ? 2 : 1);
                return $totalHtCards >= $targetLine;

            case 'cartoes_st':
                $targetLine = str_contains($eval['market_tag'], '2.5') ? 3 : 2;
                return $totalStCards >= $targetLine;

            case 'cartoes_ft':
                $targetLine = str_contains($eval['market_tag'], '5.5') ? 6 : (str_contains($eval['market_tag'], '4.5') ? 5 : 4);
                return $totalFtCards >= $targetLine;

            case 'finalizacoes_ht':
                $targetLine = str_contains($eval['market_tag'], '11.5') ? 12 : 10;
                return $totalHtShots >= $targetLine;

            case 'finalizacoes_st':
                $targetLine = str_contains($eval['market_tag'], '12.5') ? 13 : 11;
                return $totalStShots >= $targetLine;

            case 'finalizacoes_ft':
                $targetLine = str_contains($eval['market_tag'], '23.5') ? 24 : 21;
                return $totalFtShots >= $targetLine;

            case 'favorito_vence':
                if (str_contains($eval['market_tag'], '(Mandante)')) {
                    return $hScoreFt > $aScoreFt;
                } else {
                    return $aScoreFt > $hScoreFt;
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


