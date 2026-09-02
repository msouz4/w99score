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
    public function analyzeOpportunities(string $market = 'all', ?string $date = null, int $minConfidence = 55): array {
        $matches = $this->getTargetMatches($date);
        $opportunities = [];

        foreach ($matches as $match) {
            $homeId = (int)$match['home_team_id'];
            $awayId = (int)$match['away_team_id'];

            if (!$homeId || !$awayId) continue;

            $homeStatsVenue = $this->sync->getTeamVenueStats($homeId, 'home');
            $homeStatsAll   = $this->sync->getTeamVenueStats($homeId, 'all');
            $awayStatsVenue = $this->sync->getTeamVenueStats($awayId, 'away');
            $awayStatsAll   = $this->sync->getTeamVenueStats($awayId, 'all');
            $h2hMatches     = $this->sync->getH2HMatches($homeId, $awayId, (int)$match['sofascore_event_id']);

            // Mínimo de histórico para análise confiável
            $totalHomeSample = count($homeStatsAll['matches'] ?? []);
            $totalAwaySample = count($awayStatsAll['matches'] ?? []);
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
     * Avalia todos os 8 mercados para um jogo específico
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
        $hMatchesAll  = $hAll['matches'] ?? [];
        $aMatchesAll  = $aAll['matches'] ?? [];

        $hCountHome = count($hMatchesHome);
        $aCountAway = count($aMatchesAway);

        // -------------------------------------------------------------
        // 1. AMBOS MARCAM (BTTS)
        // -------------------------------------------------------------
        $hScoredHomePct = $this->calculateOccurrencePct($hMatchesHome, function($m) {
            return (int)($m['home_score_ft'] ?? 0) > 0;
        }, $hCountHome);

        $hConcededHomePct = $this->calculateOccurrencePct($hMatchesHome, function($m) {
            return (int)($m['away_score_ft'] ?? 0) > 0;
        }, $hCountHome);

        $aScoredAwayPct = $this->calculateOccurrencePct($aMatchesAway, function($m) {
            return (int)($m['away_score_ft'] ?? 0) > 0;
        }, $aCountAway);

        $aConcededAwayPct = $this->calculateOccurrencePct($aMatchesAway, function($m) {
            return (int)($m['home_score_ft'] ?? 0) > 0;
        }, $aCountAway);

        // Probabilidade estimada do mandante marcar em casa
        $probHomeScores = ($hScoredHomePct * 0.6) + ($aConcededAwayPct * 0.4);
        // Probabilidade estimada do visitante marcar fora
        $probAwayScores = ($aScoredAwayPct * 0.6) + ($hConcededHomePct * 0.4);

        $bttsConfidence = round(($probHomeScores + $probAwayScores) / 2);
        // Ajuste H2H se disponível
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

        $results['ambos_marcam'] = [
            'market_name' => 'Ambos Marcam',
            'market_tag' => 'Ambos Marcam: SIM',
            'confidence' => $bttsConfidence,
            'rating' => $this->getRatingLabel($bttsConfidence),
            'badge_color' => '#10b981',
            'main_stat' => "{$bttsConfidence}% Probabilidade",
            'stat_summary' => [
                "{$hName} marca em casa: {$hScoredHomePct}% (Média {$hAvgGolsFeitosHome})",
                "{$aName} marca fora: {$aScoredAwayPct}% (Média {$aAvgGolsFeitosAway})",
                "{$hName} sofreu gols em casa: {$hConcededHomePct}%",
                "{$aName} sofreu gols fora: {$aConcededAwayPct}%"
            ],
            'description' => "O **{$hName}** marcou em {$hScoredHomePct}% dos seus jogos como mandante (média de {$hAvgGolsFeitosHome} gols/jogo) e sofreu gols em {$hConcededHomePct}%. Já o **{$aName}** balançou as redes em {$aScoredAwayPct}% das partidas como visitante. O cruzamento de força ofensiva mandante e fragilidade defensiva visitante indica uma grande probabilidade de ambas as equipes marcarem."
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

        $hHtOver45Pct = $this->calculateOccurrencePct($hMatchesHome, function($m) {
            $ht = ((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0));
            return $ht >= 5;
        }, $hCountHome);

        $aHtOver45Pct = $this->calculateOccurrencePct($aMatchesAway, function($m) {
            $ht = ((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0));
            return $ht >= 5;
        }, $aCountAway);

        $cornersHtConfidence = round((($hHtOver45Pct + $aHtOver45Pct) / 2) * 0.7 + (min(100, ($expHtCorners / 5.2) * 80) * 0.3));
        $cornersHtConfidence = min(98, max(30, $cornersHtConfidence));

        $targetLineHt = ($expHtCorners >= 5.2) ? 'Mais de 4.5 Cantos HT' : 'Mais de 3.5 Cantos HT';

        $results['cantos_ht'] = [
            'market_name' => 'Cantos Primeiro Tempo',
            'market_tag' => $targetLineHt,
            'confidence' => $cornersHtConfidence,
            'rating' => $this->getRatingLabel($cornersHtConfidence),
            'badge_color' => '#8b5cf6',
            'main_stat' => "Média {$expHtCorners} Cantos HT",
            'stat_summary' => [
                "Média esperada no 1ºT: {$expHtCorners} escanteios",
                "{$hName} em casa: {$hHtCornersMade} feitos / {$hHtCornersCed} cedidos (1ºT)",
                "{$aName} fora: {$aHtCornersMade} feitos / {$aHtCornersCed} cedidos (1ºT)",
                "Jogos com 5+ cantos no 1ºT: {$hHtOver45Pct}% mandante / {$aHtOver45Pct}% visitante"
            ],
            'description' => "Média combinada de **{$expHtCorners} escanteios no 1º Tempo**. O **{$hName}** tem alta intensidade inicial gerando {$hHtCornersMade} e cedendo {$hHtCornersCed} cantos no 1ºT em casa. O **{$aName}** como visitante soma {$aHtCornersMade} feitos e {$aHtCornersCed} cedidos na etapa inicial, mantendo {$aHtOver45Pct}% de jogos com volume elevado de cantos no HT."
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

        $hStOver55Pct = $this->calculateOccurrencePct($hMatchesHome, function($m) {
            $cHt = ((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0));
            $cFt = ((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0));
            return max(0, $cFt - $cHt) >= 6;
        }, $hCountHome);

        $aStOver55Pct = $this->calculateOccurrencePct($aMatchesAway, function($m) {
            $cHt = ((int)($m['home_corners_ht'] ?? 0)) + ((int)($m['away_corners_ht'] ?? 0));
            $cFt = ((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0));
            return max(0, $cFt - $cHt) >= 6;
        }, $aCountAway);

        $cornersStConfidence = round((($hStOver55Pct + $aStOver55Pct) / 2) * 0.7 + (min(100, ($expStCorners / 5.8) * 80) * 0.3));
        $cornersStConfidence = min(98, max(30, $cornersStConfidence));

        $targetLineSt = ($expStCorners >= 5.5) ? 'Mais de 5.5 Cantos 2ºT' : 'Mais de 4.5 Cantos 2ºT';

        $results['cantos_st'] = [
            'market_name' => 'Cantos Segundo Tempo',
            'market_tag' => $targetLineSt,
            'confidence' => $cornersStConfidence,
            'rating' => $this->getRatingLabel($cornersStConfidence),
            'badge_color' => '#a855f7',
            'main_stat' => "Média {$expStCorners} Cantos 2ºT",
            'stat_summary' => [
                "Média esperada no 2ºT: {$expStCorners} escanteios",
                "{$hName} em casa no 2ºT: {$hStCornersMade} feitos / {$hStCornersCed} cedidos",
                "{$aName} fora no 2ºT: {$aStCornersMade} feitos / {$aStCornersCed} cedidos",
                "Jogos com 6+ cantos no 2ºT: {$hStOver55Pct}% mandante / {$aStOver55Pct}% visitante"
            ],
            'description' => "Expectativa de **{$expStCorners} escanteios na etapa complementar**. Ambos os times aceleram o jogo no 2º Tempo na busca por resultado, resultando em média de {$hStCornersMade} cantos feitos pelo mandante e {$aStCornersCed} cedidos pelo visitante nos 45 minutos finais."
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

        $hFtOver95Pct = $this->calculateOccurrencePct($hMatchesHome, function($m) {
            $cFt = ((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0));
            return $cFt >= 10;
        }, $hCountHome);

        $aFtOver95Pct = $this->calculateOccurrencePct($aMatchesAway, function($m) {
            $cFt = ((int)($m['home_corners_ft'] ?? 0)) + ((int)($m['away_corners_ft'] ?? 0));
            return $cFt >= 10;
        }, $aCountAway);

        $cornersFtConfidence = round((($hFtOver95Pct + $aFtOver95Pct) / 2) * 0.7 + (min(100, ($expFtCorners / 10.5) * 80) * 0.3));
        $cornersFtConfidence = min(98, max(30, $cornersFtConfidence));

        $targetLineFt = ($expFtCorners >= 10.5) ? 'Mais de 10.5 Escanteios' : 'Mais de 9.5 Escanteios';

        $results['cantos_ft'] = [
            'market_name' => 'Cantos Tempo Integral',
            'market_tag' => $targetLineFt,
            'confidence' => $cornersFtConfidence,
            'rating' => $this->getRatingLabel($cornersFtConfidence),
            'badge_color' => '#3b82f6',
            'main_stat' => "Média {$expFtCorners} Cantos FT",
            'stat_summary' => [
                "Média combinada FT: {$expFtCorners} escanteios",
                "{$hName} em casa: {$hFtCornersMade} feitos / {$hFtCornersCed} cedidos (Total: {$hFtCornersAvg})",
                "{$aName} fora: {$aFtCornersMade} feitos / {$aFtCornersCed} cedidos (Total: {$aFtCornersAvg})",
                "Taxa de Mais de 9.5 Cantos: {$hFtOver95Pct}% casa / {$aFtOver95Pct}% fora"
            ],
            'description' => "Projeção de **{$expFtCorners} escanteios totais** na partida. O **{$hName}** atinge média de {$hFtCornersAvg} cantos em jogos em casa ({$hFtOver95Pct}% com 10+ cantos), e o **{$aName}** sustenta média de {$aFtCornersAvg} cantos fora ({$aFtOver95Pct}% com 10+ cantos), configurando excelente oportunidade no mercado de escanteios."
        ];

        // -------------------------------------------------------------
        // 5. GOLS PRIMEIRO TEMPO (HT)
        // -------------------------------------------------------------
        $hHtGoalsAvg = (float)($hVenue['goals']['total']['avg_ht'] ?? 0);
        $aHtGoalsAvg = (float)($aVenue['goals']['total']['avg_ht'] ?? 0);
        $expHtGoals = round(($hHtGoalsAvg + $aHtGoalsAvg) / 2, 2);

        $hHtGoalsOver05Pct = $this->calculateOccurrencePct($hMatchesHome, function($m) {
            $gHt = ((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0));
            return $gHt >= 1;
        }, $hCountHome);

        $aHtGoalsOver05Pct = $this->calculateOccurrencePct($aMatchesAway, function($m) {
            $gHt = ((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0));
            return $gHt >= 1;
        }, $aCountAway);

        $goalsHtConfidence = round((($hHtGoalsOver05Pct + $aHtGoalsOver05Pct) / 2) * 0.75 + (min(100, ($expHtGoals / 1.3) * 80) * 0.25));
        $goalsHtConfidence = min(98, max(30, $goalsHtConfidence));

        $targetLineGoalsHt = ($expHtGoals >= 1.4) ? 'Mais de 1.5 Gols no 1ºT' : 'Mais de 0.5 Gols no 1ºT';

        $results['gols_ht'] = [
            'market_name' => 'Gols Primeiro Tempo',
            'market_tag' => $targetLineGoalsHt,
            'confidence' => $goalsHtConfidence,
            'rating' => $this->getRatingLabel($goalsHtConfidence),
            'badge_color' => '#06b6d4',
            'main_stat' => "{$goalsHtConfidence}% Taxa Gol 1ºT",
            'stat_summary' => [
                "Média de gols no 1ºT: {$expHtGoals} gols",
                "{$hName} em casa: {$hHtGoalsOver05Pct}% de jogos com gol no 1ºT",
                "{$aName} fora: {$aHtGoalsOver05Pct}% de jogos com gol no 1ºT",
                "Média de gols 1ºT: {$hHtGoalsAvg} mandante / {$aHtGoalsAvg} visitante"
            ],
            'description' => "Em **{$hHtGoalsOver05Pct}%** das partidas do **{$hName}** em seu estádio e **{$aHtGoalsOver05Pct}%** dos jogos do **{$aName}** como visitante houve pelo menos 1 gol antes do intervalo. A média esperada de {$expHtGoals} gols na etapa inicial traz enorme valor para o mercado de Gol no 1º Tempo."
        ];

        // -------------------------------------------------------------
        // 6. GOLS SEGUNDO TEMPO (ST)
        // -------------------------------------------------------------
        $hStGoalsAvg = (float)($hVenue['goals']['total']['avg_st'] ?? 0);
        $aStGoalsAvg = (float)($aVenue['goals']['total']['avg_st'] ?? 0);
        $expStGoals = round(($hStGoalsAvg + $aStGoalsAvg) / 2, 2);

        $hStGoalsOver05Pct = $this->calculateOccurrencePct($hMatchesHome, function($m) {
            $gHt = ((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0));
            $gFt = ((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0));
            return max(0, $gFt - $gHt) >= 1;
        }, $hCountHome);

        $aStGoalsOver05Pct = $this->calculateOccurrencePct($aMatchesAway, function($m) {
            $gHt = ((int)($m['home_score_ht'] ?? 0)) + ((int)($m['away_score_ht'] ?? 0));
            $gFt = ((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0));
            return max(0, $gFt - $gHt) >= 1;
        }, $aCountAway);

        $goalsStConfidence = round((($hStGoalsOver05Pct + $aStGoalsOver05Pct) / 2) * 0.75 + (min(100, ($expStGoals / 1.5) * 80) * 0.25));
        $goalsStConfidence = min(98, max(30, $goalsStConfidence));

        $targetLineGoalsSt = ($expStGoals >= 1.6) ? 'Mais de 1.5 Gols no 2ºT' : 'Mais de 0.5 Gols no 2ºT';

        $results['gols_st'] = [
            'market_name' => 'Gols Segundo Tempo',
            'market_tag' => $targetLineGoalsSt,
            'confidence' => $goalsStConfidence,
            'rating' => $this->getRatingLabel($goalsStConfidence),
            'badge_color' => '#14b8a6',
            'main_stat' => "Média {$expStGoals} Gols 2ºT",
            'stat_summary' => [
                "Média de gols no 2ºT: {$expStGoals} gols",
                "{$hName} em casa: {$hStGoalsOver05Pct}% de jogos com gol no 2ºT",
                "{$aName} fora: {$aStGoalsOver05Pct}% de jogos com gol no 2ºT",
                "Gols 2ºT Feitos/Cedidos: Mandante {$hVenue['goals']['feitos']['avg_st']} feitos | Visitante {$aVenue['goals']['cedidos']['avg_st']} cedidos"
            ],
            'description' => "O segundo tempo destas equipes costuma ser o período mais decisivo e aberto: média de **{$expStGoals} gols na 2ª etapa**. O mandante registrou gols no 2ºT em {$hStGoalsOver05Pct}% dos jogos em casa e a defesa visitante cedeu em {$aStGoalsOver05Pct}% das suas atuações fora."
        ];

        // -------------------------------------------------------------
        // 7. GOLS TEMPO INTEGRAL (FT)
        // -------------------------------------------------------------
        $hFtGoalsAvg = (float)($hVenue['goals']['total']['avg_ft'] ?? 0);
        $aFtGoalsAvg = (float)($aVenue['goals']['total']['avg_ft'] ?? 0);
        $expFtGoals = round(($hFtGoalsAvg + $aFtGoalsAvg) / 2, 2);

        $hFtOver25Pct = $this->calculateOccurrencePct($hMatchesHome, function($m) {
            $gFt = ((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0));
            return $gFt >= 3;
        }, $hCountHome);

        $aFtOver25Pct = $this->calculateOccurrencePct($aMatchesAway, function($m) {
            $gFt = ((int)($m['home_score_ft'] ?? 0)) + ((int)($m['away_score_ft'] ?? 0));
            return $gFt >= 3;
        }, $aCountAway);

        $goalsFtConfidence = round((($hFtOver25Pct + $aFtOver25Pct) / 2) * 0.7 + (min(100, ($expFtGoals / 2.7) * 80) * 0.3));
        $goalsFtConfidence = min(98, max(30, $goalsFtConfidence));

        $targetLineGoalsFt = ($expFtGoals >= 2.6) ? 'Mais de 2.5 Gols FT' : 'Mais de 1.5 Gols FT';

        $over15Pct = $this->calculateOccurrencePct($hMatchesHome, function($m) {
            return ((int)($m['home_score_ft'] ?? 0) + (int)($m['away_score_ft'] ?? 0)) >= 2;
        }, $hCountHome);

        $results['gols_ft'] = [
            'market_name' => 'Gols Tempo Integral',
            'market_tag' => $targetLineGoalsFt,
            'confidence' => $goalsFtConfidence,
            'rating' => $this->getRatingLabel($goalsFtConfidence),
            'badge_color' => '#f59e0b',
            'main_stat' => "Média {$expFtGoals} Gols/Jogo",
            'stat_summary' => [
                "Média combinada FT: {$expFtGoals} gols por jogo",
                "{$hName} em casa: média {$hFtGoalsAvg} gols ({$hFtOver25Pct}% Over 2.5)",
                "{$aName} fora: média {$aFtGoalsAvg} gols ({$aFtOver25Pct}% Over 2.5)",
                "Pelo menos 2 gols (Over 1.5): {$over15Pct}%"
            ],
            'description' => "Partidas com média combinada de **{$expFtGoals} gols totais**. O **{$hName}** tem ataque eficiente jogando em casa (média {$hVenue['goals']['feitos']['avg_ft']} gols marcados) e o **{$aName}** fora de casa apresenta média de {$aVenue['goals']['cedidos']['avg_ft']} gols sofridos, com {$hFtOver25Pct}% de jogos batendo Mais de 2.5 gols."
        ];

        // -------------------------------------------------------------
        // 8. CARTÕES PRIMEIRO TEMPO (HT)
        // -------------------------------------------------------------
        $hHtCardsAvg = (float)($hVenue['yellow_cards']['total']['avg_ht'] ?? 0);
        $aHtCardsAvg = (float)($aVenue['yellow_cards']['total']['avg_ht'] ?? 0);
        $expHtCards = round(($hHtCardsAvg + $aHtCardsAvg) / 2, 2);

        $hHtCardsMade = (float)($hVenue['yellow_cards']['feitos']['avg_ht'] ?? 0);
        $hHtCardsCed  = (float)($hVenue['yellow_cards']['cedidos']['avg_ht'] ?? 0);
        $aHtCardsMade = (float)($aVenue['yellow_cards']['feitos']['avg_ht'] ?? 0);
        $aHtCardsCed  = (float)($aVenue['yellow_cards']['cedidos']['avg_ht'] ?? 0);

        $hHtCardsOver15Pct = $this->calculateOccurrencePct($hMatchesHome, function($m) {
            $ht = ((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0));
            return $ht >= 2;
        }, $hCountHome);

        $aHtCardsOver15Pct = $this->calculateOccurrencePct($aMatchesAway, function($m) {
            $ht = ((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0));
            return $ht >= 2;
        }, $aCountAway);

        $cardsHtConfidence = round((($hHtCardsOver15Pct + $aHtCardsOver15Pct) / 2) * 0.7 + (min(100, ($expHtCards / 2.0) * 80) * 0.3));
        $cardsHtConfidence = min(98, max(30, $cardsHtConfidence));

        $targetLineCardsHt = ($expHtCards >= 2.0) ? 'Mais de 2.5 Cartões HT' : (($expHtCards >= 1.2) ? 'Mais de 1.5 Cartões HT' : 'Mais de 0.5 Cartões HT');

        $results['cartoes_ht'] = [
            'market_name' => 'Cartões Primeiro Tempo',
            'market_tag' => $targetLineCardsHt,
            'confidence' => $cardsHtConfidence,
            'rating' => $this->getRatingLabel($cardsHtConfidence),
            'badge_color' => '#eab308',
            'main_stat' => "Média {$expHtCards} Cartões HT",
            'stat_summary' => [
                "Média esperada no 1ºT: {$expHtCards} cartões",
                "{$hName} em casa: {$hHtCardsMade} recebidos / {$hHtCardsCed} provocados (1ºT)",
                "{$aName} fora: {$aHtCardsMade} recebidos / {$aHtCardsCed} provocados (1ºT)",
                "Jogos com 2+ cartões no 1ºT: {$hHtCardsOver15Pct}% mandante / {$aHtCardsOver15Pct}% visitante"
            ],
            'description' => "Média combinada de **{$expHtCards} cartões no 1º Tempo**. O **{$hName}** tem média de {$hHtCardsMade} cartões recebidos e {$hHtCardsCed} provocados no 1ºT em casa, enquanto o **{$aName}** como visitante mantém média de {$aHtCardsMade} recebidos na etapa inicial ({$aHtCardsOver15Pct}% com 2+ cartões no HT)."
        ];

        // -------------------------------------------------------------
        // 9. CARTÕES SEGUNDO TEMPO (ST)
        // -------------------------------------------------------------
        $hStCardsAvg = (float)($hVenue['yellow_cards']['total']['avg_st'] ?? 0);
        $aStCardsAvg = (float)($aVenue['yellow_cards']['total']['avg_st'] ?? 0);
        $expStCards = round(($hStCardsAvg + $aStCardsAvg) / 2, 2);

        $hStCardsMade = (float)($hVenue['yellow_cards']['feitos']['avg_st'] ?? 0);
        $hStCardsCed  = (float)($hVenue['yellow_cards']['cedidos']['avg_st'] ?? 0);
        $aStCardsMade = (float)($aVenue['yellow_cards']['feitos']['avg_st'] ?? 0);
        $aStCardsCed  = (float)($aVenue['yellow_cards']['cedidos']['avg_st'] ?? 0);

        $hStCardsOver25Pct = $this->calculateOccurrencePct($hMatchesHome, function($m) {
            $cHt = ((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0));
            $cFt = ((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0));
            return max(0, $cFt - $cHt) >= 3;
        }, $hCountHome);

        $aStCardsOver25Pct = $this->calculateOccurrencePct($aMatchesAway, function($m) {
            $cHt = ((int)($m['home_yellow_cards_ht'] ?? 0)) + ((int)($m['away_yellow_cards_ht'] ?? 0));
            $cFt = ((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0));
            return max(0, $cFt - $cHt) >= 3;
        }, $aCountAway);

        $cardsStConfidence = round((($hStCardsOver25Pct + $aStCardsOver25Pct) / 2) * 0.7 + (min(100, ($expStCards / 2.8) * 80) * 0.3));
        $cardsStConfidence = min(98, max(30, $cardsStConfidence));

        $targetLineCardsSt = ($expStCards >= 2.6) ? 'Mais de 2.5 Cartões 2ºT' : 'Mais de 1.5 Cartões 2ºT';

        $results['cartoes_st'] = [
            'market_name' => 'Cartões Segundo Tempo',
            'market_tag' => $targetLineCardsSt,
            'confidence' => $cardsStConfidence,
            'rating' => $this->getRatingLabel($cardsStConfidence),
            'badge_color' => '#f97316',
            'main_stat' => "Média {$expStCards} Cartões 2ºT",
            'stat_summary' => [
                "Média esperada no 2ºT: {$expStCards} cartões",
                "{$hName} em casa no 2ºT: {$hStCardsMade} recebidos / {$hStCardsCed} provocados",
                "{$aName} fora no 2ºT: {$aStCardsMade} recebidos / {$aStCardsCed} provocados",
                "Jogos com 3+ cartões no 2ºT: {$hStCardsOver25Pct}% mandante / {$aStCardsOver25Pct}% visitante"
            ],
            'description' => "Projeção de **{$expStCards} cartões na segunda etapa**. O segundo tempo tende a ser mais acirrado e com maior número de faltas, registrando {$hStCardsOver25Pct}% de partidas com 3 ou mais cartões no 2ºT para o mandante e {$aStCardsOver25Pct}% para o visitante."
        ];

        // -------------------------------------------------------------
        // 10. CARTÕES TEMPO INTEGRAL (FT)
        // -------------------------------------------------------------
        $hFtCardsAvg = (float)($hVenue['yellow_cards']['total']['avg_ft'] ?? 0);
        $aFtCardsAvg = (float)($aVenue['yellow_cards']['total']['avg_ft'] ?? 0);
        $expFtCards = round(($hFtCardsAvg + $aFtCardsAvg) / 2, 2);

        $hFtCardsMade = (float)($hVenue['yellow_cards']['feitos']['avg_ft'] ?? 0);
        $hFtCardsCed  = (float)($hVenue['yellow_cards']['cedidos']['avg_ft'] ?? 0);
        $aFtCardsMade = (float)($aVenue['yellow_cards']['feitos']['avg_ft'] ?? 0);
        $aFtCardsCed  = (float)($aVenue['yellow_cards']['cedidos']['avg_ft'] ?? 0);

        $hFtCardsOver45Pct = $this->calculateOccurrencePct($hMatchesHome, function($m) {
            $cFt = ((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0));
            return $cFt >= 5;
        }, $hCountHome);

        $aFtCardsOver45Pct = $this->calculateOccurrencePct($aMatchesAway, function($m) {
            $cFt = ((int)($m['home_yellow_cards_ft'] ?? 0)) + ((int)($m['away_yellow_cards_ft'] ?? 0));
            return $cFt >= 5;
        }, $aCountAway);

        $cardsFtConfidence = round((($hFtCardsOver45Pct + $aFtCardsOver45Pct) / 2) * 0.7 + (min(100, ($expFtCards / 5.2) * 80) * 0.3));
        $cardsFtConfidence = min(98, max(30, $cardsFtConfidence));

        $targetLineCardsFt = ($expFtCards >= 5.5) ? 'Mais de 5.5 Cartões' : (($expFtCards >= 4.3) ? 'Mais de 4.5 Cartões' : 'Mais de 3.5 Cartões');

        $results['cartoes_ft'] = [
            'market_name' => 'Cartões Tempo Integral',
            'market_tag' => $targetLineCardsFt,
            'confidence' => $cardsFtConfidence,
            'rating' => $this->getRatingLabel($cardsFtConfidence),
            'badge_color' => '#eab308',
            'main_stat' => "Média {$expFtCards} Cartões FT",
            'stat_summary' => [
                "Média combinada FT: {$expFtCards} cartões por jogo",
                "{$hName} em casa: {$hFtCardsMade} recebidos / {$hFtCardsCed} provocados (Total: {$hFtCardsAvg})",
                "{$aName} fora: {$aFtCardsMade} recebidos / {$aFtCardsCed} provocados (Total: {$aFtCardsAvg})",
                "Taxa de 5+ cartões na partida: {$hFtCardsOver45Pct}% casa / {$aFtCardsOver45Pct}% fora"
            ],
            'description' => "Projeção de **{$expFtCards} cartões totais** no confronto. O **{$hName}** apresenta média de {$hFtCardsAvg} cartões em seus jogos em casa ({$hFtCardsOver45Pct}% com 5+ advertências), enquanto o **{$aName}** tem média de {$aFtCardsAvg} cartões fora ({$aFtCardsOver45Pct}% com 5+ advertências), indicando forte tendência para o mercado de cartões."
        ];

        // -------------------------------------------------------------
        // 11. FAVORITO VENCE (Moneyline)
        // -------------------------------------------------------------
        $hHomeWins = 0; $hHomeDraws = 0; $hHomeLosses = 0;
        foreach ($hMatchesHome as $m) {
            $hs = (int)($m['home_score_ft'] ?? 0);
            $as = (int)($m['away_score_ft'] ?? 0);
            if ($hs > $as) $hHomeWins++;
            elseif ($hs === $as) $hHomeDraws++;
            else $hHomeLosses++;
        }
        $hWinPct = $hCountHome > 0 ? round(($hHomeWins / $hCountHome) * 100) : 50;

        $aAwayWins = 0; $aAwayDraws = 0; $aAwayLosses = 0;
        foreach ($aMatchesAway as $m) {
            $hs = (int)($m['home_score_ft'] ?? 0);
            $as = (int)($m['away_score_ft'] ?? 0);
            if ($as > $hs) $aAwayWins++;
            elseif ($as === $hs) $aAwayDraws++;
            else $aAwayLosses++;
        }
        $aWinPct = $aCountAway > 0 ? round(($aAwayWins / $aCountAway) * 100) : 30;
        $aLossPct = $aCountAway > 0 ? round(($aAwayLosses / $aCountAway) * 100) : 50;

        // Calcular se o mandante ou visitante é favorito
        $hPower = ($hWinPct * 0.6) + ($aLossPct * 0.4) + (($hVenue['goals']['feitos']['avg_ft'] - $hVenue['goals']['cedidos']['avg_ft']) * 10);
        $aPower = ($aWinPct * 0.6) + (($hCountHome > 0 ? round(($hHomeLosses / $hCountHome) * 100) : 30) * 0.4) + (($aVenue['goals']['feitos']['avg_ft'] - $aVenue['goals']['cedidos']['avg_ft']) * 10);

        $isHomeFav = $hPower >= $aPower;
        $favTeamName = $isHomeFav ? $hName : $aName;
        $favRole = $isHomeFav ? 'Mandante' : 'Visitante';
        $favWinPct = $isHomeFav ? $hWinPct : $aWinPct;
        $underdogLossPct = $isHomeFav ? $aLossPct : ($hCountHome > 0 ? round(($hHomeLosses / $hCountHome) * 100) : 40);

        $favConfidence = round(($favWinPct * 0.6) + ($underdogLossPct * 0.4));
        $favConfidence = min(96, max(45, $favConfidence));

        $favTag = "Vitória: {$favTeamName} ({$favRole})";

        $underdogRole = $isHomeFav ? 'Fora' : 'Casa';

        $results['favorito_vence'] = [
            'market_name' => 'Favorito Vence',
            'market_tag' => $favTag,
            'confidence' => $favConfidence,
            'rating' => $this->getRatingLabel($favConfidence),
            'badge_color' => '#22c55e',
            'main_stat' => "{$favConfidence}% Favoritismo",
            'stat_summary' => [
                "{$favTeamName} ({$favRole}): {$favWinPct}% vitórias no retrospecto",
                "Adversário ({$underdogRole}): {$underdogLossPct}% derrotas",
                "Aproveitamento Mandante: {$hHomeWins}V / {$hHomeDraws}E / {$hHomeLosses}D",
                "Aproveitamento Visitante: {$aAwayWins}V / {$aAwayDraws}E / {$aAwayLosses}D"
            ],
            'description' => "O **{$favTeamName}** entra como amplo favorito ({$favConfidence}% de probabilidade calculada). Jogando como {$favRole}, a equipe sustenta **{$favWinPct}% de taxa de vitórias** com saldo positivo consistente, enquanto o adversário sofreu derrotas em **{$underdogLossPct}%** das suas partidas nestas condições de estádio."
        ];

        return $results;
    }

    private function calculateOccurrencePct(array $matches, callable $condition, int $totalCount): int {
        if ($totalCount === 0 || empty($matches)) return 50;
        $count = 0;
        foreach ($matches as $m) {
            if ($condition($m)) $count++;
        }
        return round(($count / $totalCount) * 100);
    }

    private function getRatingLabel(int $confidence): string {
        if ($confidence >= 85) return 'Excelente (Oportunidade de Ouro)';
        if ($confidence >= 75) return 'Muito Alta';
        if ($confidence >= 65) return 'Alta';
        return 'Moderada';
    }
}
