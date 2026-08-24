<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/SofascoreApi.php';
require_once __DIR__ . '/SyncService.php';

$action = $_GET['action'] ?? '';
$api = new SofascoreApi();
$sync = new SyncService();

try {
    switch ($action) {
        case 'get_leagues':
            $leagues = $api->getFeaturedTournaments();
            foreach ($leagues as &$league) {
                $league['is_favorite'] = $sync->isFavorite((int)$league['id']);
            }
            echo json_encode(['success' => true, 'data' => $leagues]);
            break;

        case 'toggle_favorite':
            $tournamentId = (int)($_POST['tournament_id'] ?? $_GET['tournament_id'] ?? 0);
            $name = $_POST['name'] ?? $_GET['name'] ?? '';
            $categoryName = $_POST['category_name'] ?? $_GET['category_name'] ?? '';
            $logoUrl = $_POST['logo_url'] ?? $_GET['logo_url'] ?? '';

            if (!$tournamentId) {
                echo json_encode(['success' => false, 'error' => 'tournament_id é obrigatório']);
                exit;
            }

            $res = $sync->toggleFavorite($tournamentId, $name, $categoryName, $logoUrl);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'get_favorites':
            $favorites = $sync->getFavoriteLeagues();
            echo json_encode(['success' => true, 'data' => $favorites]);
            break;

        case 'get_seasons':
            $tournamentId = (int)($_GET['tournament_id'] ?? 0);
            if (!$tournamentId) {
                echo json_encode(['success' => false, 'error' => 'tournament_id é obrigatório']);
                exit;
            }
            $seasons = $api->getTournamentSeasons($tournamentId);
            echo json_encode(['success' => true, 'data' => $seasons]);
            break;

        case 'get_rounds':
            $tournamentId = (int)($_GET['tournament_id'] ?? 0);
            $seasonId = (int)($_GET['season_id'] ?? 0);
            if (!$tournamentId || !$seasonId) {
                echo json_encode(['success' => false, 'error' => 'tournament_id e season_id são obrigatórios']);
                exit;
            }
            $rounds = $api->getSeasonRounds($tournamentId, $seasonId);
            echo json_encode(['success' => true, 'data' => $rounds]);
            break;

        case 'get_matches':
            $tournamentId = (int)($_GET['tournament_id'] ?? 0);
            $seasonId = (int)($_GET['season_id'] ?? 0);
            $round = isset($_GET['round']) && $_GET['round'] !== 'all' ? (int)$_GET['round'] : null;

            if (!$tournamentId || !$seasonId) {
                echo json_encode(['success' => false, 'error' => 'tournament_id e season_id são obrigatórios']);
                exit;
            }

            if ($round !== null && $round > 0) {
                $events = $api->getRoundEvents($tournamentId, $seasonId, $round);
            } else {
                $events = $api->getAllSeasonEvents($tournamentId, $seasonId);
            }

            echo json_encode(['success' => true, 'count' => count($events), 'data' => $events]);
            break;

        case 'sync_single_match':
            $rawInput = file_get_contents('php://input');
            $inputData = json_decode($rawInput, true) ?: $_POST;

            $evt = $inputData['event'] ?? null;
            $seasonId = (int)($inputData['season_id'] ?? 0);
            $seasonName = $inputData['season_name'] ?? '';

            if (!$evt || !$seasonId) {
                echo json_encode(['success' => false, 'error' => 'Evento e season_id são obrigatórios']);
                exit;
            }

            $synced = $sync->syncMatch($evt, $seasonId, $seasonName);
            echo json_encode(['success' => true, 'data' => $synced]);
            break;

        case 'get_db_matches':
            $tournamentId = (int)($_GET['tournament_id'] ?? 0);
            $seasonId = (int)($_GET['season_id'] ?? 0);
            $onlyValid = isset($_GET['only_valid']) && $_GET['only_valid'] === '1';

            $dbMatches = $sync->getDbMatches($tournamentId, $seasonId, $onlyValid);
            echo json_encode(['success' => true, 'count' => count($dbMatches), 'data' => $dbMatches]);
            break;

        case 'get_upcoming_matches':
            $date = $_GET['date'] ?? date('Y-m-d');
            $events = $api->getScheduledEvents($date);
            
            if (empty($events)) {
                $stmt = $sync->getDbMatches(0, 0, false);
                $events = array_filter($stmt, function($m) {
                    return $m['status'] === 'notstarted' || $m['status'] === 'inprogress';
                });
            }

            echo json_encode(['success' => true, 'count' => count($events), 'data' => array_values($events)]);
            break;

        case 'get_h2h_data':
            $eventId = (int)($_GET['event_id'] ?? 0);
            $homeTeamId = (int)($_GET['home_team_id'] ?? 0);
            $awayTeamId = (int)($_GET['away_team_id'] ?? 0);

            $apiH2H = [];
            if ($eventId > 0) {
                $apiH2H = $api->getEventH2H($eventId);
            }

            $dbH2H = [];
            if ($homeTeamId > 0 && $awayTeamId > 0) {
                $dbH2H = $sync->getH2HMatches($homeTeamId, $awayTeamId, $eventId);
            }

            echo json_encode(['success' => true, 'api_h2h' => $apiH2H, 'db_h2h' => $dbH2H]);
            break;

        case 'get_team_stats':
            $homeTeamId = (int)($_GET['home_team_id'] ?? 0);
            $awayTeamId = (int)($_GET['away_team_id'] ?? 0);

            $homeStats = $homeTeamId ? $sync->getTeamOverallStats($homeTeamId) : [];
            $awayStats = $awayTeamId ? $sync->getTeamOverallStats($awayTeamId) : [];

            echo json_encode(['success' => true, 'home_stats' => $homeStats, 'away_stats' => $awayStats]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
