<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/SyncService.php';
require_once __DIR__ . '/auth.php';

$action = $_GET['action'] ?? '';
$sync = new SyncService();

/**
 * Valida se a requisição possui uma sessão de usuário autenticado OU uma chave X-API-Key válida
 */
function validateApiKey(): void {
    if (isAuthenticated()) {
        return; // Usuário autenticado na interface Web
    }

    $expectedKey = trim(getAppEnv('INGEST_API_KEY', 'w99_sec_99a8b7c6d5e4f321'));
    $providedKey = '';

    // 1. Verificar em $_SERVER (padrão em proxies Apache / Nginx / FastCGI)
    if (!empty($_SERVER['HTTP_X_API_KEY'])) {
        $providedKey = trim($_SERVER['HTTP_X_API_KEY']);
    }

    // 2. Verificar getallheaders() de forma case-insensitive
    if (!$providedKey && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'X-API-Key') === 0 || strcasecmp($name, 'X_API_KEY') === 0) {
                $providedKey = trim($value);
                break;
            }
        }
        if (!$providedKey && isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                $providedKey = trim($matches[1]);
            }
        }
    }

    // 3. Verificar Authorization Bearer em $_SERVER
    if (!$providedKey && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $providedKey = trim($matches[1]);
        }
    }
    
    // 4. Fallback para parâmetro GET / POST api_key
    if (!$providedKey && isset($_REQUEST['api_key'])) {
        $providedKey = trim($_REQUEST['api_key']);
    }
    
    if (empty($providedKey) || !hash_equals($expectedKey, $providedKey)) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Acesso não autorizado. Faça login ou forneça uma chave X-API-Key válida.'
        ]);
        exit;
    }
}


/**
 * Retorna o diretório para armazenamento de escudos com fallback seguro
 */
function getLogosDirectory(): string {
    $primary = __DIR__ . '/uploads/logos';
    if (!is_dir($primary)) {
        @mkdir($primary, 0777, true);
    }
    if (is_dir($primary) && is_writable($primary)) {
        // Se a pasta primária agora tem permissão e havia arquivos no fallback /tmp, migra automaticamente
        $fallback = sys_get_temp_dir() . '/w99score_logos';
        if (is_dir($fallback)) {
            $files = @glob("{$fallback}/*.png");
            if ($files) {
                foreach ($files as $f) {
                    $dest = $primary . '/' . basename($f);
                    if (!file_exists($dest)) {
                        @rename($f, $dest);
                    }
                }
            }
        }
        return $primary;
    }

    // Fallback: se o Apache (www-data) ainda não tiver permissão para escrever em src/uploads
    $fallback = sys_get_temp_dir() . '/w99score_logos';
    if (!is_dir($fallback)) {
        @mkdir($fallback, 0777, true);
    }
    return (is_dir($fallback) && is_writable($fallback)) ? $fallback : $primary;
}

/**
 * Localiza o arquivo de imagem do escudo em uploads ou no diretório fallback
 */
function findLogoFile(string $type, int $id): ?string {
    $primary = __DIR__ . "/uploads/logos/{$type}_{$id}.png";
    if (file_exists($primary)) {
        return $primary;
    }

    $tempFile = sys_get_temp_dir() . "/w99score_logos/{$type}_{$id}.png";
    if (file_exists($tempFile)) {
        $targetDir = __DIR__ . '/uploads/logos';
        if (is_dir($targetDir) && is_writable($targetDir)) {
            if (@rename($tempFile, $primary) || (@copy($tempFile, $primary) && @unlink($tempFile))) {
                return $primary;
            }
        }
        return $tempFile;
    }
    return null;
}

try {
    if (isAuthenticated()) {
        if (!validateUserSession()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Sessão inválida. O usuário não foi encontrado no sistema.']);
            exit;
        }
        logUserAccess(true);
    }

    switch ($action) {
        // ==========================================
        // 1. ENDPOINTS DE INGESTÃO (COLETOR LOCAL -> VPS)
        // ==========================================
        case 'ingest_matches':
            validateApiKey();
            $rawInput = file_get_contents('php://input');
            $inputData = json_decode($rawInput, true);

            if (!$inputData) {
                echo json_encode(['success' => false, 'error' => 'JSON inválido no corpo da requisição']);
                exit;
            }

            $matches = $inputData['matches'] ?? $inputData['events'] ?? [];
            if (!empty($inputData['match'])) {
                $matches = [$inputData['match']];
            }

            if (empty($matches) && is_array($inputData) && isset($inputData[0])) {
                $matches = $inputData;
            }

            if (empty($matches)) {
                echo json_encode(['success' => false, 'error' => 'Nenhuma partida fornecida para ingestão']);
                exit;
            }

            $forceResync = !empty($inputData['force_resync']) || !empty($_GET['force_resync']);
            $result = $sync->ingestMatchesBatch($matches, $forceResync);

            echo json_encode([
                'success' => true,
                'message' => "Ingestão concluída com sucesso",
                'data' => $result
            ]);
            break;

        case 'ingest_favorites':
            validateApiKey();
            $rawInput = file_get_contents('php://input');
            $inputData = json_decode($rawInput, true) ?: [];
            $favorites = $inputData['favorites'] ?? $inputData;

            if (!is_array($favorites)) {
                echo json_encode(['success' => false, 'error' => 'Lista de favoritos inválida']);
                exit;
            }

            $count = 0;
            foreach ($favorites as $fav) {
                $tournamentId = (int)($fav['tournament_id'] ?? $fav['id'] ?? 0);
                $name = $fav['name'] ?? '';
                $cat = $fav['category_name'] ?? ($fav['category']['name'] ?? '');
                $logo = $fav['logo_url'] ?? '';

                if ($tournamentId && $name) {
                    if (!$sync->isFavorite($tournamentId)) {
                        $sync->toggleFavorite($tournamentId, $name, $cat, $logo);
                    }
                    $count++;
                }
            }

            echo json_encode([
                'success' => true,
                'message' => "Favoritos atualizados com sucesso",
                'processed' => $count
            ]);
            break;

        case 'ingest_status':
            $status = $sync->getSystemStatus();
            echo json_encode([
                'success' => true,
                'data' => $status
            ]);
            break;

        case 'check_existing_matches':
            validateApiKey();
            $rawInput = file_get_contents('php://input');
            $inputData = json_decode($rawInput, true) ?: [];

            $eventIds = $inputData['event_ids'] ?? [];
            if (is_string($eventIds)) {
                $eventIds = array_filter(array_map('intval', explode(',', $eventIds)));
            } elseif (is_array($eventIds)) {
                $eventIds = array_filter(array_map('intval', $eventIds));
            } elseif (!empty($_GET['ids'])) {
                $eventIds = array_filter(array_map('intval', explode(',', (string)$_GET['ids'])));
            }

            $tournamentId = (int)($inputData['tournament_id'] ?? $_GET['tournament_id'] ?? 0);
            $seasonId = (int)($inputData['season_id'] ?? $_GET['season_id'] ?? 0);
            $date = $inputData['date'] ?? $_GET['date'] ?? '';

            $matchesStatus = $sync->getSyncedMatchesStatus($eventIds, $tournamentId, $seasonId, $date);

            echo json_encode([
                'success' => true,
                'matches' => $matchesStatus
            ]);
            break;

        case 'check_existing_logos':
            $type = $_GET['type'] ?? 'team';
            $idsParam = $_GET['ids'] ?? '';
            $ids = array_filter(array_map('intval', explode(',', (string)$idsParam)));
            
            // Força tentativa de migração do temp para uploads/logos se tiver permissão
            getLogosDirectory();

            $existing = [];
            foreach ($ids as $id) {
                if (findLogoFile($type, $id) !== null) {
                    $existing[] = $id;
                }
            }
            echo json_encode(['success' => true, 'existing' => $existing]);
            break;

        case 'migrate_logos':
            $target = __DIR__ . '/uploads/logos';
            $temp = sys_get_temp_dir() . '/w99score_logos';
            $moved = 0;
            if (is_dir($target) && is_writable($target) && is_dir($temp)) {
                $files = @glob("{$temp}/*.png");
                if ($files) {
                    foreach ($files as $f) {
                        $dest = $target . '/' . basename($f);
                        if (@rename($f, $dest) || (@copy($f, $dest) && @unlink($f))) {
                            $moved++;
                        }
                    }
                }
            }
            $inUploads = @glob("{$target}/*.png") ?: [];
            $inTemp = @glob("{$temp}/*.png") ?: [];
            echo json_encode([
                'success' => true,
                'target_writable' => is_dir($target) && is_writable($target),
                'moved' => $moved,
                'files_in_uploads' => count($inUploads),
                'files_in_temp' => count($inTemp)
            ]);
            break;

        case 'ingest_logo':
            validateApiKey();
            $rawInput = file_get_contents('php://input');
            $inputData = json_decode($rawInput, true) ?: [];
            
            $type = $inputData['type'] ?? $_POST['type'] ?? 'team';
            $id = (int)($inputData['id'] ?? $_POST['id'] ?? 0);
            $imageBase64 = $inputData['image_base64'] ?? $_POST['image_base64'] ?? '';
            
            if ($id <= 0 || empty($imageBase64)) {
                echo json_encode(['success' => false, 'error' => 'id e image_base64 são obrigatórios']);
                exit;
            }

            $imgBinary = base64_decode($imageBase64);
            if (!$imgBinary || strlen($imgBinary) < 10) {
                echo json_encode(['success' => false, 'error' => 'Dados binários da imagem inválidos']);
                exit;
            }

            $logoDir = getLogosDirectory();
            $filePath = "{$logoDir}/{$type}_{$id}.png";
            $saved = @file_put_contents($filePath, $imgBinary);

            echo json_encode([
                'success' => (bool)$saved,
                'message' => $saved ? "Escudo salvo com sucesso" : "Falha ao gravar arquivo no disco",
                'type' => $type,
                'id' => $id,
                'path' => $filePath,
                'bytes' => (int)$saved
            ]);
            break;

        // ==========================================
        // 2. ENDPOINTS LOCAIS DO SISTEMA / ANÁLISE (100% OFFLINE)
        // ==========================================
        case 'get_image':
            $type = $_GET['type'] ?? 'team';
            $id = (int)($_GET['id'] ?? 0);

            // Verifica se existe imagem em cache local no diretório uploads ou fallback
            $localImg = findLogoFile($type, $id);
            if ($localImg && file_exists($localImg)) {
                header_remove('Content-Type');
                header('Content-Type: image/png');
                header('Cache-Control: public, max-age=604800');
                readfile($localImg);
                exit;
            }

            // Fallback elegante com SVG moderno gerado localmente (ZERO chamadas externas)
            header_remove('Content-Type');
            header('Content-Type: image/svg+xml; charset=utf-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            $bg = ($type === 'tournament') ? '#4f46e5' : '#0ea5e9';
            $iconText = ($type === 'tournament') ? '🏆' : '⚽';
            
            echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">
    <rect width="64" height="64" rx="14" fill="{$bg}" fill-opacity="0.2" stroke="{$bg}" stroke-width="2"/>
    <text x="32" y="38" font-size="26" text-anchor="middle" dominant-baseline="middle">{$iconText}</text>
</svg>
SVG;
            exit;

        case 'get_leagues':
            // Lista estática de ligas de referência
            $defaultLeagues = [
                ['id' => 325,   'name' => 'Brasileirão Betano', 'category' => ['name' => 'Brasil', 'flag' => 'brazil']],
                ['id' => 390,   'name' => 'Brasileirão Série B', 'category' => ['name' => 'Brasil', 'flag' => 'brazil']],
                ['id' => 1281,  'name' => 'Brasileirão Série C', 'category' => ['name' => 'Brasil', 'flag' => 'brazil']],
                ['id' => 10326, 'name' => 'Brasileirão Série D', 'category' => ['name' => 'Brasil', 'flag' => 'brazil']],
                ['id' => 373,   'name' => 'Copa Betano do Brasil', 'category' => ['name' => 'Brasil', 'flag' => 'brazil']],
                ['id' => 384,   'name' => 'Copa CONMEBOL Libertadores', 'category' => ['name' => 'América do Sul', 'flag' => 'south-america']],
                ['id' => 480,   'name' => 'Copa CONMEBOL Sudamericana', 'category' => ['name' => 'América do Sul', 'flag' => 'south-america']],
                ['id' => 155,   'name' => 'Liga Profesional Argentina', 'category' => ['name' => 'Argentina', 'flag' => 'argentina']],
                ['id' => 7,     'name' => 'Liga dos Campeões da UEFA', 'category' => ['name' => 'Europa', 'flag' => 'europe']],
                ['id' => 679,   'name' => 'UEFA Liga Europa', 'category' => ['name' => 'Europa', 'flag' => 'europe']],
                ['id' => 17015, 'name' => 'UEFA Conference League', 'category' => ['name' => 'Europa', 'flag' => 'europe']],
                ['id' => 465,   'name' => 'UEFA Super Cup', 'category' => ['name' => 'Europa', 'flag' => 'europe']],
                ['id' => 10783, 'name' => 'UEFA Nations League', 'category' => ['name' => 'Europa', 'flag' => 'europe']],
                ['id' => 17,    'name' => 'Premier League 2026/2027', 'category' => ['name' => 'Inglaterra', 'flag' => 'england']],
                ['id' => 18,    'name' => 'Championship', 'category' => ['name' => 'Inglaterra', 'flag' => 'england']],
                ['id' => 19,    'name' => 'FA Cup', 'category' => ['name' => 'Inglaterra', 'flag' => 'england']],
                ['id' => 21,    'name' => 'EFL Cup', 'category' => ['name' => 'Inglaterra', 'flag' => 'england']],
                ['id' => 346,   'name' => 'Community Shield', 'category' => ['name' => 'Inglaterra', 'flag' => 'england']],
                ['id' => 8,     'name' => 'LaLiga', 'category' => ['name' => 'Espanha', 'flag' => 'spain']],
                ['id' => 54,    'name' => 'LaLiga 2', 'category' => ['name' => 'Espanha', 'flag' => 'spain']],
                ['id' => 329,   'name' => 'Copa del Rey', 'category' => ['name' => 'Espanha', 'flag' => 'spain']],
                ['id' => 213,   'name' => 'Supercopa de Espana', 'category' => ['name' => 'Espanha', 'flag' => 'spain']],
                ['id' => 23,    'name' => 'Serie A', 'category' => ['name' => 'Itália', 'flag' => 'italy']],
                ['id' => 53,    'name' => 'Serie B', 'category' => ['name' => 'Itália', 'flag' => 'italy']],
                ['id' => 328,   'name' => 'Coppa Italia', 'category' => ['name' => 'Itália', 'flag' => 'italy']],
                ['id' => 341,   'name' => 'Supercoppa Italiana', 'category' => ['name' => 'Itália', 'flag' => 'italy']],
                ['id' => 35,    'name' => 'Bundesliga 2025/2026', 'category' => ['name' => 'Alemanha', 'flag' => 'germany']],
                ['id' => 44,    'name' => '2. Bundesliga', 'category' => ['name' => 'Alemanha', 'flag' => 'germany']],
                ['id' => 217,   'name' => 'DFB Pokal', 'category' => ['name' => 'Alemanha', 'flag' => 'germany']],
                ['id' => 799,   'name' => 'Franz Beckenbauer Supercup', 'category' => ['name' => 'Alemanha', 'flag' => 'germany']],
                ['id' => 34,    'name' => 'Ligue 1', 'category' => ['name' => 'França', 'flag' => 'france']],
                ['id' => 182,   'name' => 'Ligue 2', 'category' => ['name' => 'França', 'flag' => 'france']],
                ['id' => 339,   'name' => 'Trophée des Champions', 'category' => ['name' => 'França', 'flag' => 'france']],
                ['id' => 238,   'name' => 'Liga Portugal Betclic', 'category' => ['name' => 'Portugal', 'flag' => 'portugal']],
                ['id' => 239,   'name' => 'Liga Portugal 2', 'category' => ['name' => 'Portugal', 'flag' => 'portugal']],
                ['id' => 327,   'name' => 'Taça da Liga', 'category' => ['name' => 'Portugal', 'flag' => 'portugal']],
                ['id' => 345,   'name' => 'Supertaça', 'category' => ['name' => 'Portugal', 'flag' => 'portugal']],
                ['id' => 37,    'name' => 'VriendenLoterij Eredivisie', 'category' => ['name' => 'Holanda', 'flag' => 'netherlands']],
                ['id' => 131,   'name' => 'Eerste Divisie', 'category' => ['name' => 'Holanda', 'flag' => 'netherlands']],
                ['id' => 38,    'name' => 'Pro League', 'category' => ['name' => 'Bélgica', 'flag' => 'belgium']],
                ['id' => 326,   'name' => 'Belgian Cup', 'category' => ['name' => 'Bélgica', 'flag' => 'belgium']],
                ['id' => 338,   'name' => 'Belgian Super Cup', 'category' => ['name' => 'Bélgica', 'flag' => 'belgium']],
                ['id' => 36,    'name' => 'Scottish Premiership', 'category' => ['name' => 'Escócia', 'flag' => 'scotland']],
                ['id' => 332,   'name' => 'Scottish League Cup', 'category' => ['name' => 'Escócia', 'flag' => 'scotland']],
                ['id' => 52,    'name' => 'Trendyol Süper Lig', 'category' => ['name' => 'Turquia', 'flag' => 'turkey']],
                ['id' => 96,    'name' => 'Türkiye Kupası', 'category' => ['name' => 'Turquia', 'flag' => 'turkey']],
                ['id' => 505,   'name' => 'TFF Süper Kupa', 'category' => ['name' => 'Turquia', 'flag' => 'turkey']],
                ['id' => 20,    'name' => 'Eliteserien', 'category' => ['name' => 'Noruega', 'flag' => 'norway']],
                ['id' => 29,    'name' => 'Norwegian Football Cup', 'category' => ['name' => 'Noruega', 'flag' => 'norway']],
                ['id' => 39,    'name' => 'Danish Superliga', 'category' => ['name' => 'Dinamarca', 'flag' => 'denmark']],
                ['id' => 76,    'name' => 'Oddset Pokalen', 'category' => ['name' => 'Dinamarca', 'flag' => 'denmark']],
                ['id' => 40,    'name' => 'Allsvenskan', 'category' => ['name' => 'Suécia', 'flag' => 'sweden']],
                ['id' => 80,    'name' => 'Svenska Cupen', 'category' => ['name' => 'Suécia', 'flag' => 'sweden']],
                ['id' => 955,   'name' => 'Saudi Pro League', 'category' => ['name' => 'Arábia Saudita', 'flag' => 'saudi-arabia']],
                ['id' => 808,   'name' => 'Egyptian Premier League', 'category' => ['name' => 'Egito', 'flag' => 'egypt']],
                ['id' => 242,   'name' => 'MLS (Major League Soccer)', 'category' => ['name' => 'EUA', 'flag' => 'usa']],
                ['id' => 495,   'name' => 'US Open Cup', 'category' => ['name' => 'EUA', 'flag' => 'usa']],
                ['id' => 13783, 'name' => 'Leagues Cup', 'category' => ['name' => 'América do Norte', 'flag' => 'north-and-central-america']],
                ['id' => 357,   'name' => 'Fifa Club World Cup', 'category' => ['name' => 'Mundo', 'flag' => 'international']],
                ['id' => 1295,  'name' => 'Emirates Cup', 'category' => ['name' => 'Mundo', 'flag' => 'international']],
                ['id' => 816,   'name' => 'OTP Bank Liga', 'category' => ['name' => 'Húngria', 'flag' => 'hungary']],
                ['id' => 305,   'name' => 'MOL Magyar Kupa', 'category' => ['name' => 'Húngria', 'flag' => 'hungary']],
                ['id' => 210,   'name' => 'Mozzart Bet Superliga', 'category' => ['name' => 'Sérvia', 'flag' => 'serbia']],
                ['id' => 314,   'name' => 'Mozzart Kup Srbije', 'category' => ['name' => 'Sérvia', 'flag' => 'serbia']],
                ['id' => 171,   'name' => 'Cyprus League by Stoiximan', 'category' => ['name' => 'Chipre', 'flag' => 'cyprus']],
                ['id' => 172,   'name' => 'Czech First League', 'category' => ['name' => 'República Tcheca', 'flag' => 'czech-republic']],
            ];

            // Busca quais ligas realmente possuem partidas sincronizadas no banco
            $pdo = getPDOConnection();
            $syncedStmt = $pdo->query("SELECT DISTINCT tournament_id FROM matches");
            $syncedTournamentIds = array_map('intval', $syncedStmt->fetchAll(PDO::FETCH_COLUMN));

            $favs = $sync->getFavoriteLeagues();
            $favMap = [];
            foreach ($favs as $f) {
                $favMap[(int)$f['tournament_id']] = $f;
            }

            $defaultLeaguesMap = [];
            foreach ($defaultLeagues as $dl) {
                $defaultLeaguesMap[(int)$dl['id']] = $dl;
            }

            $resultLeagues = [];
            foreach ($syncedTournamentIds as $tId) {
                if (isset($defaultLeaguesMap[$tId])) {
                    $item = $defaultLeaguesMap[$tId];
                } elseif (isset($favMap[$tId])) {
                    $item = [
                        'id' => $tId,
                        'name' => $favMap[$tId]['name'],
                        'category' => ['name' => $favMap[$tId]['category_name'] ?: 'Geral', 'flag' => ''],
                    ];
                } else {
                    $item = [
                        'id' => $tId,
                        'name' => "Liga #{$tId}",
                        'category' => ['name' => 'Futebol', 'flag' => ''],
                    ];
                }
                $item['is_favorite'] = isset($favMap[$tId]);
                $resultLeagues[] = $item;
            }

            echo json_encode(['success' => true, 'data' => $resultLeagues]);
            break;

        case 'get_seasons':
            $tournamentId = (int)($_GET['tournament_id'] ?? 0);
            $pdo = getPDOConnection();
            $stmt = $pdo->prepare("SELECT DISTINCT season_id as id, season_name as name FROM matches WHERE tournament_id = ? AND season_id > 0 ORDER BY season_id DESC");
            $stmt->execute([$tournamentId]);
            $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $seasons]);
            break;

        case 'get_rounds':
            $tournamentId = (int)($_GET['tournament_id'] ?? 0);
            $seasonId = (int)($_GET['season_id'] ?? 0);
            $pdo = getPDOConnection();
            $stmt = $pdo->prepare("SELECT DISTINCT round FROM matches WHERE tournament_id = ? AND season_id = ? AND round IS NOT NULL ORDER BY round ASC");
            $stmt->execute([$tournamentId, $seasonId]);
            $rounds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $roundsData = array_map(fn($r) => ['round' => $r, 'name' => "Rodada {$r}"], $rounds);
            echo json_encode(['success' => true, 'data' => ['rounds' => $roundsData]]);
            break;

        case 'get_matches':
            $tournamentId = (int)($_GET['tournament_id'] ?? 0);
            $seasonId = (int)($_GET['season_id'] ?? 0);
            $round = isset($_GET['round']) && $_GET['round'] !== 'all' ? (string)$_GET['round'] : null;

            $pdo = getPDOConnection();
            $sql = "SELECT * FROM matches WHERE tournament_id = ? AND season_id = ?";
            $params = [$tournamentId, $seasonId];
            if ($round !== null) {
                $sql .= " AND round = ?";
                $params[] = $round;
            }
            $sql .= " ORDER BY start_timestamp ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'count' => count($matches), 'data' => $matches]);
            break;

        case 'get_favorites':
            $favorites = $sync->getFavoriteLeagues();
            echo json_encode(['success' => true, 'data' => $favorites]);
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

        case 'delete_league_matches':
            $tournamentId = (int)($_POST['tournament_id'] ?? $_GET['tournament_id'] ?? 0);
            $seasonId = (int)($_POST['season_id'] ?? $_GET['season_id'] ?? 0);

            if (!$tournamentId) {
                echo json_encode(['success' => false, 'error' => 'tournament_id é obrigatório']);
                exit;
            }

            $res = $sync->deleteLeagueMatches($tournamentId, $seasonId);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'get_db_matches':
            $tournamentId = (int)($_GET['tournament_id'] ?? 0);
            $seasonId = (int)($_GET['season_id'] ?? 0);
            $onlyValid = isset($_GET['only_valid']) && $_GET['only_valid'] === '1';

            $dbMatches = $sync->getDbMatches($tournamentId, $seasonId, $onlyValid);
            echo json_encode(['success' => true, 'count' => count($dbMatches), 'data' => $dbMatches]);
            break;

        case 'get_upcoming_matches':
            $days = isset($_GET['days']) ? max(1, (int)$_GET['days']) : 7;
            
            // Busca apenas do banco MySQL local (zero consultas Sofascore)
            $events = $sync->getDbMatches(0, 0, false);

            $startTime = strtotime('today 00:00:00');
            $endTime = strtotime("+{$days} days 23:59:59");

            $filtered = array_filter($events, function($m) use ($startTime, $endTime) {
                $status = is_array($m['status'] ?? null) 
                    ? ($m['status']['type'] ?? '') 
                    : ($m['status'] ?? '');

                $ts = (isset($m['start_timestamp']) && (int)$m['start_timestamp'] > 0)
                    ? (int)$m['start_timestamp']
                    : (isset($m['match_date']) ? strtotime($m['match_date']) : 0);

                return ($status === 'notstarted' || $status === 'inprogress') && ($ts >= $startTime && $ts <= $endTime);
            });

            // Se não houver jogos futuros exatos no intervalo de dias, retorna os próximos jogos agendados disponíveis
            if (empty($filtered)) {
                $filtered = array_filter($events, function($m) {
                    $status = is_array($m['status'] ?? null) 
                        ? ($m['status']['type'] ?? '') 
                        : ($m['status'] ?? '');
                    return ($status === 'notstarted' || $status === 'inprogress');
                });
            }

            echo json_encode(['success' => true, 'count' => count($filtered), 'data' => array_values($filtered)]);
            break;

        case 'get_h2h_data':
            $eventId = (int)($_GET['event_id'] ?? 0);
            $homeTeamId = (int)($_GET['home_team_id'] ?? 0);
            $awayTeamId = (int)($_GET['away_team_id'] ?? 0);

            // Busca histórico H2H exclusivamente da base local de partidas finalizadas
            $dbH2H = [];
            if ($homeTeamId > 0 && $awayTeamId > 0) {
                $dbH2H = $sync->getH2HMatches($homeTeamId, $awayTeamId, $eventId);
            }

            echo json_encode([
                'success' => true, 
                'api_h2h' => [], // Zero requisição externa
                'db_h2h' => $dbH2H
            ]);
            break;

        case 'get_team_stats':
            $homeTeamId = (int)($_GET['home_team_id'] ?? $_POST['home_team_id'] ?? 0);
            $awayTeamId = (int)($_GET['away_team_id'] ?? $_POST['away_team_id'] ?? 0);
            $robustParam = $_GET['robust'] ?? $_POST['robust'] ?? '1';
            $robust = ($robustParam === '1' || $robustParam === 'true' || $robustParam === true);

            $homeStats = $homeTeamId ? [
                'overall' => $sync->getTeamVenueStats($homeTeamId, 'all', $robust),
                'home' => $sync->getTeamVenueStats($homeTeamId, 'home', $robust),
                'away' => $sync->getTeamVenueStats($homeTeamId, 'away', $robust),
            ] : [];

            $awayStats = $awayTeamId ? [
                'overall' => $sync->getTeamVenueStats($awayTeamId, 'all', $robust),
                'home' => $sync->getTeamVenueStats($awayTeamId, 'home', $robust),
                'away' => $sync->getTeamVenueStats($awayTeamId, 'away', $robust),
            ] : [];

            echo json_encode([
                'success' => true, 
                'robust' => $robust,
                'home_stats' => $homeStats, 
                'away_stats' => $awayStats
            ]);
            break;

        case 'get_favorite_teams':
            $user = currentUser();
            if (!$user) {
                echo json_encode(['success' => false, 'error' => 'Usuário não autenticado', 'team_ids' => [], 'data' => []]);
                exit;
            }
            $pdo = getPDOConnection();
            $stmt = $pdo->prepare("SELECT id, team_id, team_name, team_logo, created_at FROM user_favorite_teams WHERE user_id = ? ORDER BY team_name ASC");
            $stmt->execute([(int)$user['id']]);
            $favs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $teamIds = array_map(fn($f) => (int)$f['team_id'], $favs);
            echo json_encode(['success' => true, 'team_ids' => $teamIds, 'data' => $favs]);
            break;

        case 'toggle_favorite_team':
            $user = currentUser();
            if (!$user) {
                echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
                exit;
            }
            $teamId = (int)($_REQUEST['team_id'] ?? 0);
            $teamName = trim($_REQUEST['team_name'] ?? '');
            $teamLogo = trim($_REQUEST['team_logo'] ?? '');

            if (!$teamId) {
                echo json_encode(['success' => false, 'error' => 'ID do time é obrigatório']);
                exit;
            }

            $pdo = getPDOConnection();

            // Verificar se já está nos favoritos
            $stmt = $pdo->prepare("SELECT id FROM user_favorite_teams WHERE user_id = ? AND team_id = ?");
            $stmt->execute([(int)$user['id'], $teamId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Remover dos favoritos
                $del = $pdo->prepare("DELETE FROM user_favorite_teams WHERE user_id = ? AND team_id = ?");
                $del->execute([(int)$user['id'], $teamId]);
                $isFavorite = false;
            } else {
                // Se nome/logo vierem vazios, tenta buscar na tabela matches
                if (!$teamName) {
                    $mStmt = $pdo->prepare("
                        SELECT home_team_name AS name, home_team_logo AS logo FROM matches WHERE home_team_id = ? 
                        UNION 
                        SELECT away_team_name AS name, away_team_logo AS logo FROM matches WHERE away_team_id = ?
                        LIMIT 1
                    ");
                    $mStmt->execute([$teamId, $teamId]);
                    $tInfo = $mStmt->fetch(PDO::FETCH_ASSOC);
                    if ($tInfo) {
                        $teamName = $tInfo['name'] ?? "Time #{$teamId}";
                        $teamLogo = $tInfo['logo'] ?? '';
                    } else {
                        $teamName = "Time #{$teamId}";
                    }
                }

                $ins = $pdo->prepare("INSERT INTO user_favorite_teams (user_id, team_id, team_name, team_logo) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE team_name = VALUES(team_name), team_logo = VALUES(team_logo)");
                $ins->execute([(int)$user['id'], $teamId, $teamName, $teamLogo]);
                $isFavorite = true;
            }

            // Retorna os IDs atualizados
            $stmtList = $pdo->prepare("SELECT team_id FROM user_favorite_teams WHERE user_id = ?");
            $stmtList->execute([(int)$user['id']]);
            $allFavIds = array_map('intval', $stmtList->fetchAll(PDO::FETCH_COLUMN));

            echo json_encode([
                'success' => true,
                'is_favorite' => $isFavorite,
                'team_id' => $teamId,
                'team_ids' => $allFavIds
            ]);
            break;

        case 'search_teams':
            $q = trim($_GET['q'] ?? '');
            $pdo = getPDOConnection();
            if (mb_strlen($q) < 2) {
                // Retorna os times mais frequentes recentes se a busca for muito curta
                $stmt = $pdo->query("
                    SELECT team_id, team_name, team_logo FROM (
                        SELECT home_team_id AS team_id, home_team_name AS team_name, home_team_logo AS team_logo FROM matches WHERE home_team_id IS NOT NULL AND home_team_id > 0
                        UNION
                        SELECT away_team_id AS team_id, away_team_name AS team_name, away_team_logo AS team_logo FROM matches WHERE away_team_id IS NOT NULL AND away_team_id > 0
                    ) AS combined_teams
                    GROUP BY team_id, team_name, team_logo
                    ORDER BY team_name ASC
                    LIMIT 40
                ");
            } else {
                $term = "%{$q}%";
                $stmt = $pdo->prepare("
                    SELECT team_id, team_name, team_logo FROM (
                        SELECT home_team_id AS team_id, home_team_name AS team_name, home_team_logo AS team_logo FROM matches WHERE home_team_name LIKE ? AND home_team_id > 0
                        UNION
                        SELECT away_team_id AS team_id, away_team_name AS team_name, away_team_logo AS team_logo FROM matches WHERE away_team_name LIKE ? AND away_team_id > 0
                    ) AS combined_teams
                    GROUP BY team_id, team_name, team_logo
                    ORDER BY team_name ASC
                    LIMIT 50
                ");
                $stmt->execute([$term, $term]);
            }
            $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Marca quais já são favoritos do usuário
            $user = currentUser();
            $favIds = [];
            if ($user) {
                $fStmt = $pdo->prepare("SELECT team_id FROM user_favorite_teams WHERE user_id = ?");
                $fStmt->execute([(int)$user['id']]);
                $favIds = array_map('intval', $fStmt->fetchAll(PDO::FETCH_COLUMN));
            }

            foreach ($teams as &$t) {
                $t['is_favorite'] = in_array((int)$t['team_id'], $favIds, true);
            }
            unset($t);

            echo json_encode(['success' => true, 'data' => $teams]);
            break;

        case 'get_opportunities':
            require_once __DIR__ . '/OpportunityService.php';
            $oppService = new OpportunityService();
            $market = $_GET['market'] ?? 'all';
            $date = $_GET['date'] ?? null;
            $minConfidence = isset($_GET['min_confidence']) ? (int)$_GET['min_confidence'] : 40;

            $favIds = [];
            $onlyFavorites = !empty($_GET['only_favorites']) && $_GET['only_favorites'] !== 'false';
            if ($onlyFavorites) {
                $user = currentUser();
                if ($user) {
                    $pdo = getPDOConnection();
                    $stmtFav = $pdo->prepare("SELECT team_id FROM user_favorite_teams WHERE user_id = ?");
                    $stmtFav->execute([(int)$user['id']]);
                    $favIds = array_map('intval', $stmtFav->fetchAll(PDO::FETCH_COLUMN));
                }
            }

            $opportunities = $oppService->analyzeOpportunities($market, $date, $minConfidence, $favIds);
            echo json_encode([
                'success' => true,
                'count' => count($opportunities),
                'market' => $market,
                'date' => $date ?: date('Y-m-d'),
                'only_favorites' => $onlyFavorites,
                'data' => $opportunities
            ]);
            break;

        case 'get_backtest_stats':
            require_once __DIR__ . '/OpportunityService.php';
            $oppService = new OpportunityService();
            $market = $_GET['market'] ?? 'all';
            $dateRange = $_GET['date_range'] ?? 'month';
            $minConfidence = isset($_GET['min_confidence']) ? (int)$_GET['min_confidence'] : 80;
            $tournamentId = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 0;

            $favIds = [];
            $onlyFavorites = !empty($_GET['only_favorites']) && $_GET['only_favorites'] !== 'false';
            if ($onlyFavorites) {
                $user = currentUser();
                if ($user) {
                    $pdo = getPDOConnection();
                    $stmtFav = $pdo->prepare("SELECT team_id FROM user_favorite_teams WHERE user_id = ?");
                    $stmtFav->execute([(int)$user['id']]);
                    $favIds = array_map('intval', $stmtFav->fetchAll(PDO::FETCH_COLUMN));
                }
            }

            $backtestData = $oppService->analyzeFinishedMatchesBacktest($market, $dateRange, $minConfidence, $tournamentId, $favIds);
            echo json_encode([
                'success' => true,
                'market' => $market,
                'date_range' => $dateRange,
                'min_confidence' => $minConfidence,
                'tournament_id' => $tournamentId,
                'only_favorites' => $onlyFavorites,
                'data' => $backtestData
            ]);
            break;

        case 'get_backtest_leagues':
            $pdo = getPDOConnection();
            $stmt = $pdo->query("
                SELECT DISTINCT tournament_id, 
                       COALESCE(NULLIF(season_name, ''), CONCAT('Liga #', tournament_id)) AS league_label
                FROM matches 
                WHERE status = 'finished' AND is_stats_incomplete = 0 AND tournament_id > 0
                ORDER BY league_label ASC
            ");
            $leagues = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $leagues]);
            break;


        case 'get_single_match':
            $eventId = (int)($_GET['event_id'] ?? 0);
            if (!$eventId) {
                echo json_encode(['success' => false, 'error' => 'event_id é obrigatório']);
                exit;
            }
            $pdo = getPDOConnection();
            $stmt = $pdo->prepare("SELECT * FROM matches WHERE sofascore_event_id = ? OR id = ? LIMIT 1");
            $stmt->execute([$eventId, $eventId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($match) {
                echo json_encode(['success' => true, 'data' => $match]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Partida não encontrada']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
