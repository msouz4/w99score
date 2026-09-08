<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/SofascoreApi.php';

class SyncService {
    private PDO $pdo;
    private SofascoreApi $api;

    public function __construct() {
        $this->pdo = getPDOConnection();
        $this->api = new SofascoreApi();
        $this->initSchema();
    }

    private function initSchema(): void {
        $sqlPath = __DIR__ . '/schema.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $this->pdo->exec($sql);
        }
    }

    public function toggleFavorite(int $tournamentId, string $name, string $categoryName = '', string $logoUrl = ''): array {
        $stmt = $this->pdo->prepare("SELECT id FROM favorite_leagues WHERE tournament_id = ?");
        $stmt->execute([$tournamentId]);
        $fav = $stmt->fetch();

        if ($fav) {
            $del = $this->pdo->prepare("DELETE FROM favorite_leagues WHERE tournament_id = ?");
            $del->execute([$tournamentId]);
            return ['is_favorite' => false, 'message' => 'Liga removida dos favoritos'];
        } else {
            $ins = $this->pdo->prepare("INSERT INTO favorite_leagues (tournament_id, name, category_name, logo_url) VALUES (?, ?, ?, ?)");
            $ins->execute([$tournamentId, $name, $categoryName, $logoUrl]);
            return ['is_favorite' => true, 'message' => 'Liga adicionada aos favoritos'];
        }
    }

    public function getFavoriteLeagues(): array {
        $stmt = $this->pdo->query("SELECT * FROM favorite_leagues ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isFavorite(int $tournamentId): bool {
        $stmt = $this->pdo->prepare("SELECT id FROM favorite_leagues WHERE tournament_id = ?");
        $stmt->execute([$tournamentId]);
        return (bool)$stmt->fetch();
    }

    public function syncMatch(array $evt, int $seasonId, string $seasonName = '', bool $forceResync = false): array {
        $eventId = (int)$evt['id'];
        $tournamentId = (int)($evt['tournament']['uniqueTournament']['id'] ?? $evt['tournament']['id'] ?? 0);
        $round = isset($evt['roundInfo']['round']) ? (string)$evt['roundInfo']['round'] : null;
        $startTimestamp = (int)($evt['startTimestamp'] ?? 0);
        $matchDate = $startTimestamp ? date('Y-m-d H:i:s', $startTimestamp) : null;
        $statusType = $evt['status']['type'] ?? 'notstarted';

        $homeTeamId = (int)($evt['homeTeam']['id'] ?? 0);
        $homeTeamName = $evt['homeTeam']['name'] ?? 'Casa';
        $homeTeamLogo = "https://api.sofascore.app/api/v1/team/{$homeTeamId}/image";

        $awayTeamId = (int)($evt['awayTeam']['id'] ?? 0);
        $awayTeamName = $evt['awayTeam']['name'] ?? 'Fora';
        $awayTeamLogo = "https://api.sofascore.app/api/v1/team/{$awayTeamId}/image";

        // Verificação inteligente de cache:
        // Se a partida não está em andamento ('inprogress') e o status no banco é idêntico ao da API,
        // significa que a partida já foi gravada e não mudou de estado. Pula chamadas de rede e gravações repetidas.
        if (!$forceResync) {
            $checkStmt = $this->pdo->prepare("SELECT status, is_stats_incomplete FROM matches WHERE sofascore_event_id = ?");
            $checkStmt->execute([$eventId]);
            $existing = $checkStmt->fetch();

            if ($existing && $statusType !== 'inprogress' && $existing['status'] === $statusType) {
                return [
                    'event_id' => $eventId,
                    'status' => $statusType,
                    'is_stats_incomplete' => (int)$existing['is_stats_incomplete'],
                    'skipped' => true,
                    'home_team' => $homeTeamName,
                    'away_team' => $awayTeamName
                ];
            }
        }

        $homeScoreHt = isset($evt['homeScore']['period1']) ? (int)$evt['homeScore']['period1'] : null;
        $awayScoreHt = isset($evt['awayScore']['period1']) ? (int)$evt['awayScore']['period1'] : null;
        $homeScoreFt = isset($evt['homeScore']['current']) ? (int)$evt['homeScore']['current'] : null;
        $awayScoreFt = isset($evt['awayScore']['current']) ? (int)$evt['awayScore']['current'] : null;

        $homeCornersHt = $awayCornersHt = $homeCornersFt = $awayCornersFt = null;
        $homeYellowHt = $awayYellowHt = $homeYellowFt = $awayYellowFt = null;
        $homeShotsOnTargetHt = $awayShotsOnTargetHt = $homeShotsOnTargetFt = $awayShotsOnTargetFt = null;

        $isStatsIncomplete = 0;
        $incompleteReason = null;

        if ($statusType === 'finished' || $statusType === 'inprogress') {
            $statsData = $this->api->getEventStatistics($eventId);

            if (!empty($statsData)) {
                foreach ($statsData as $periodData) {
                    $period = $periodData['period'] ?? '';
                    $groups = $periodData['groups'] ?? [];

                    foreach ($groups as $group) {
                        $items = $group['statisticsItems'] ?? [];
                        foreach ($items as $item) {
                            $key = $item['key'] ?? '';
                            $hVal = isset($item['homeValue']) ? (int)$item['homeValue'] : (isset($item['home']) ? (int)$item['home'] : null);
                            $aVal = isset($item['awayValue']) ? (int)$item['awayValue'] : (isset($item['away']) ? (int)$item['away'] : null);

                            if ($period === 'ALL') {
                                if ($key === 'cornerKicks') { $homeCornersFt = $hVal; $awayCornersFt = $aVal; }
                                if ($key === 'yellowCards') { $homeYellowFt = $hVal; $awayYellowFt = $aVal; }
                                if ($key === 'shotsOnGoal') { $homeShotsOnTargetFt = $hVal; $awayShotsOnTargetFt = $aVal; }
                            }
                            if ($period === '1ST') {
                                if ($key === 'cornerKicks') { $homeCornersHt = $hVal; $awayCornersHt = $aVal; }
                                if ($key === 'yellowCards') { $homeYellowHt = $hVal; $awayYellowHt = $aVal; }
                                if ($key === 'shotsOnGoal') { $homeShotsOnTargetHt = $hVal; $awayShotsOnTargetHt = $aVal; }
                            }
                        }
                    }
                }
            }

            if ($statusType === 'finished') {
                if (
                    $homeCornersFt === null || $homeYellowFt === null || $homeShotsOnTargetFt === null ||
                    $homeCornersHt === null || $homeYellowHt === null || $homeShotsOnTargetHt === null ||
                    $homeScoreHt === null || $homeScoreFt === null
                ) {
                    $isStatsIncomplete = 1;
                    $incompleteReason = "Estatísticas parciais ou ausentes para HT/FT na API do Sofascore";
                }
            }
        }

        $sql = "INSERT INTO matches (
                    sofascore_event_id, tournament_id, season_id, season_name, round, start_timestamp, match_date, status,
                    home_team_id, home_team_name, home_team_logo, away_team_id, away_team_name, away_team_logo,
                    home_score_ht, away_score_ht, home_score_ft, away_score_ft,
                    home_corners_ht, away_corners_ht, home_corners_ft, away_corners_ft,
                    home_yellow_cards_ht, away_yellow_cards_ht, home_yellow_cards_ft, away_yellow_cards_ft,
                    home_shots_on_target_ht, away_shots_on_target_ht, home_shots_on_target_ft, away_shots_on_target_ft,
                    is_stats_incomplete, incomplete_reason
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?
                ) ON DUPLICATE KEY UPDATE
                    season_id = VALUES(season_id),
                    season_name = VALUES(season_name),
                    round = VALUES(round),
                    start_timestamp = VALUES(start_timestamp),
                    match_date = VALUES(match_date),
                    status = VALUES(status),
                    home_score_ht = VALUES(home_score_ht),
                    away_score_ht = VALUES(away_score_ht),
                    home_score_ft = VALUES(home_score_ft),
                    away_score_ft = VALUES(away_score_ft),
                    home_corners_ht = VALUES(home_corners_ht),
                    away_corners_ht = VALUES(away_corners_ht),
                    home_corners_ft = VALUES(home_corners_ft),
                    away_corners_ft = VALUES(away_corners_ft),
                    home_yellow_cards_ht = VALUES(home_yellow_cards_ht),
                    away_yellow_cards_ht = VALUES(away_yellow_cards_ht),
                    home_yellow_cards_ft = VALUES(home_yellow_cards_ft),
                    away_yellow_cards_ft = VALUES(away_yellow_cards_ft),
                    home_shots_on_target_ht = VALUES(home_shots_on_target_ht),
                    away_shots_on_target_ht = VALUES(away_shots_on_target_ht),
                    home_shots_on_target_ft = VALUES(home_shots_on_target_ft),
                    away_shots_on_target_ft = VALUES(away_shots_on_target_ft),
                    is_stats_incomplete = VALUES(is_stats_incomplete),
                    incomplete_reason = VALUES(incomplete_reason),
                    last_synced_at = CURRENT_TIMESTAMP";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $eventId, $tournamentId, $seasonId, $seasonName, $round, $startTimestamp, $matchDate, $statusType,
            $homeTeamId, $homeTeamName, $homeTeamLogo, $awayTeamId, $awayTeamName, $awayTeamLogo,
            $homeScoreHt, $awayScoreHt, $homeScoreFt, $awayScoreFt,
            $homeCornersHt, $awayCornersHt, $homeCornersFt, $awayCornersFt,
            $homeYellowHt, $awayYellowHt, $homeYellowFt, $awayYellowFt,
            $homeShotsOnTargetHt, $awayShotsOnTargetHt, $homeShotsOnTargetFt, $awayShotsOnTargetFt,
            $isStatsIncomplete, $incompleteReason
        ]);

        return [
            'event_id' => $eventId,
            'status' => $statusType,
            'is_stats_incomplete' => $isStatsIncomplete,
            'home_team' => $homeTeamName,
            'away_team' => $awayTeamName
        ];
    }

    public function getDbMatches(int $tournamentId = 0, int $seasonId = 0, bool $onlyValidStats = false): array {
        $where = [];
        $params = [];

        if ($tournamentId > 0) {
            $where[] = "tournament_id = ?";
            $params[] = $tournamentId;
        }

        if ($seasonId > 0) {
            $where[] = "season_id = ?";
            $params[] = $seasonId;
        }

        if ($onlyValidStats) {
            $where[] = "is_stats_incomplete = 0";
        }

        $sql = "SELECT * FROM matches";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY start_timestamp ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcula estatísticas acumuladas de um time especificando filtro de local ('all', 'home', 'away')
     */
    public function getTeamVenueStats(int $teamId, string $venue = 'all'): array {
        $sql = "SELECT * FROM matches WHERE status = 'finished' AND is_stats_incomplete = 0 AND ";
        $params = [];

        if ($venue === 'home') {
            $sql .= "home_team_id = ?";
            $params[] = $teamId;
        } elseif ($venue === 'away') {
            $sql .= "away_team_id = ?";
            $params[] = $teamId;
        } else {
            $sql .= "(home_team_id = ? OR away_team_id = ?)";
            $params[] = $teamId;
            $params[] = $teamId;
        }

        $sql .= " ORDER BY start_timestamp DESC LIMIT 30";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalMatches = count($matches);
        $emptyCategory = [
            'feitos' => ['ht' => 0, 'st' => 0, 'ft' => 0, 'avg_ht' => 0, 'avg_st' => 0, 'avg_ft' => 0],
            'cedidos' => ['ht' => 0, 'st' => 0, 'ft' => 0, 'avg_ht' => 0, 'avg_st' => 0, 'avg_ft' => 0],
            'total' => ['ht' => 0, 'st' => 0, 'ft' => 0, 'avg_ht' => 0, 'avg_st' => 0, 'avg_ft' => 0],
            'avg_ht' => 0, 'avg_st' => 0, 'avg_ft' => 0
        ];

        if ($totalMatches === 0) {
            return [
                'matches_count' => 0,
                'venue' => $venue,
                'goals' => $emptyCategory,
                'corners' => $emptyCategory,
                'yellow_cards' => $emptyCategory,
                'shots_on_target' => $emptyCategory,
                'matches' => []
            ];
        }

        $gHtFeitos = $gStFeitos = $gFtFeitos = 0;
        $gHtCedidos = $gStCedidos = $gFtCedidos = 0;

        $cHtFeitos = $cStFeitos = $cFtFeitos = 0;
        $cHtCedidos = $cStCedidos = $cFtCedidos = 0;

        $yHtFeitos = $yStFeitos = $yFtFeitos = 0;
        $yHtCedidos = $yStCedidos = $yFtCedidos = 0;

        $sHtFeitos = $sStFeitos = $sFtFeitos = 0;
        $sHtCedidos = $sStCedidos = $sFtCedidos = 0;

        foreach ($matches as $m) {
            $isHome = ((int)$m['home_team_id'] === $teamId);

            // Gols
            $gHtF = $isHome ? (int)($m['home_score_ht'] ?? 0) : (int)($m['away_score_ht'] ?? 0);
            $gFtF = $isHome ? (int)($m['home_score_ft'] ?? 0) : (int)($m['away_score_ft'] ?? 0);
            $gStF = max(0, $gFtF - $gHtF);

            $gHtC = $isHome ? (int)($m['away_score_ht'] ?? 0) : (int)($m['home_score_ht'] ?? 0);
            $gFtC = $isHome ? (int)($m['away_score_ft'] ?? 0) : (int)($m['home_score_ft'] ?? 0);
            $gStC = max(0, $gFtC - $gHtC);

            $gHtFeitos += $gHtF; $gStFeitos += $gStF; $gFtFeitos += $gFtF;
            $gHtCedidos += $gHtC; $gStCedidos += $gStC; $gFtCedidos += $gFtC;

            // Escanteios
            $cHtF = $isHome ? (int)($m['home_corners_ht'] ?? 0) : (int)($m['away_corners_ht'] ?? 0);
            $cFtF = $isHome ? (int)($m['home_corners_ft'] ?? 0) : (int)($m['away_corners_ft'] ?? 0);
            $cStF = max(0, $cFtF - $cHtF);

            $cHtC = $isHome ? (int)($m['away_corners_ht'] ?? 0) : (int)($m['home_corners_ht'] ?? 0);
            $cFtC = $isHome ? (int)($m['away_corners_ft'] ?? 0) : (int)($m['home_corners_ft'] ?? 0);
            $cStC = max(0, $cFtC - $cHtC);

            $cHtFeitos += $cHtF; $cStFeitos += $cStF; $cFtFeitos += $cFtF;
            $cHtCedidos += $cHtC; $cStCedidos += $cStC; $cFtCedidos += $cFtC;

            // Cartões Amarelos
            $yHtF = $isHome ? (int)($m['home_yellow_cards_ht'] ?? 0) : (int)($m['away_yellow_cards_ht'] ?? 0);
            $yFtF = $isHome ? (int)($m['home_yellow_cards_ft'] ?? 0) : (int)($m['away_yellow_cards_ft'] ?? 0);
            $yStF = max(0, $yFtF - $yHtF);

            $yHtC = $isHome ? (int)($m['away_yellow_cards_ht'] ?? 0) : (int)($m['home_yellow_cards_ht'] ?? 0);
            $yFtC = $isHome ? (int)($m['away_yellow_cards_ft'] ?? 0) : (int)($m['home_yellow_cards_ft'] ?? 0);
            $yStC = max(0, $yFtC - $yHtC);

            $yHtFeitos += $yHtF; $yStFeitos += $yStF; $yFtFeitos += $yFtF;
            $yHtCedidos += $yHtC; $yStCedidos += $yStC; $yFtCedidos += $yFtC;

            // Chutes a Gol
            $sHtF = $isHome ? (int)($m['home_shots_on_target_ht'] ?? 0) : (int)($m['away_shots_on_target_ht'] ?? 0);
            $sFtF = $isHome ? (int)($m['home_shots_on_target_ft'] ?? 0) : (int)($m['away_shots_on_target_ft'] ?? 0);
            $sStF = max(0, $sFtF - $sHtF);

            $sHtC = $isHome ? (int)($m['away_shots_on_target_ht'] ?? 0) : (int)($m['home_shots_on_target_ht'] ?? 0);
            $sFtC = $isHome ? (int)($m['away_shots_on_target_ft'] ?? 0) : (int)($m['home_shots_on_target_ft'] ?? 0);
            $sStC = max(0, $sFtC - $sHtC);

            $sHtFeitos += $sHtF; $sStFeitos += $sStF; $sFtFeitos += $sFtF;
            $sHtCedidos += $sHtC; $sStCedidos += $sStC; $sFtCedidos += $sFtC;
        }

        $buildCat = function($hF, $sF, $fF, $hC, $sC, $fC) use ($totalMatches) {
            $hT = $hF + $hC; $sT = $sF + $sC; $fT = $fF + $fC;
            return [
                'feitos' => [
                    'ht' => $hF, 'st' => $sF, 'ft' => $fF,
                    'avg_ht' => round($hF / $totalMatches, 2),
                    'avg_st' => round($sF / $totalMatches, 2),
                    'avg_ft' => round($fF / $totalMatches, 2)
                ],
                'cedidos' => [
                    'ht' => $hC, 'st' => $sC, 'ft' => $fC,
                    'avg_ht' => round($hC / $totalMatches, 2),
                    'avg_st' => round($sC / $totalMatches, 2),
                    'avg_ft' => round($fC / $totalMatches, 2)
                ],
                'total' => [
                    'ht' => $hT, 'st' => $sT, 'ft' => $fT,
                    'avg_ht' => round($hT / $totalMatches, 2),
                    'avg_st' => round($sT / $totalMatches, 2),
                    'avg_ft' => round($fT / $totalMatches, 2)
                ],
                'avg_ht' => round($hF / $totalMatches, 2),
                'avg_st' => round($sF / $totalMatches, 2),
                'avg_ft' => round($fF / $totalMatches, 2)
            ];
        };

        return [
            'matches_count' => $totalMatches,
            'venue' => $venue,
            'goals' => $buildCat($gHtFeitos, $gStFeitos, $gFtFeitos, $gHtCedidos, $gStCedidos, $gFtCedidos),
            'corners' => $buildCat($cHtFeitos, $cStFeitos, $cFtFeitos, $cHtCedidos, $cStCedidos, $cFtCedidos),
            'yellow_cards' => $buildCat($yHtFeitos, $yStFeitos, $yFtFeitos, $yHtCedidos, $yStCedidos, $yFtCedidos),
            'shots_on_target' => $buildCat($sHtFeitos, $sStFeitos, $sFtFeitos, $sHtCedidos, $sStCedidos, $sFtCedidos),
            'matches' => $matches
        ];
    }

    public function getTeamOverallStats(int $teamId): array {
        return $this->getTeamVenueStats($teamId, 'all');
    }

    public function getH2HMatches(int $homeTeamId, int $awayTeamId, int $excludeEventId = 0): array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM matches 
            WHERE ((home_team_id = ? AND away_team_id = ?) OR (home_team_id = ? AND away_team_id = ?))
              AND status = 'finished'
              AND is_stats_incomplete = 0
              AND sofascore_event_id != ?
            ORDER BY start_timestamp DESC
        ");
        $stmt->execute([$homeTeamId, $awayTeamId, $awayTeamId, $homeTeamId, $excludeEventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
