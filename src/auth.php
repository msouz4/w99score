<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna os dados do usuário atualmente autenticado ou null
 */
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Retorna true se o usuário estiver autenticado
 */
function isAuthenticated(): bool {
    return currentUser() !== null;
}

/**
 * Retorna true se o usuário autenticado for administrador
 */
function isAdmin(): bool {
    $u = currentUser();
    return $u !== null && !empty($u['is_admin']);
}

/**
 * Retorna o IP real do cliente (suportando proxies e Cloudflare)
 */
function getClientIP(): string {
    $keys = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    foreach ($keys as $k) {
        if (!empty($_SERVER[$k])) {
            $ipList = explode(',', $_SERVER[$k]);
            $ip = trim($ipList[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Registra o acesso do usuário autenticado no banco MySQL com controle de throttle leve (1s)
 */
function logUserAccess(): void {
    $user = currentUser();
    if (!$user) return;

    $userId = (int)$user['id'];
    $ip = getClientIP();
    
    // Extrai o nome da página e parâmetros de forma limpa (ex: "oportunidades.php", "analise.php?event_id=123")
    $rawUri = $_SERVER['REQUEST_URI'] ?? ($_SERVER['PHP_SELF'] ?? 'index.php');
    $path = parse_url($rawUri, PHP_URL_PATH);
    $baseName = basename($path ?: '');
    $query = parse_url($rawUri, PHP_URL_QUERY);
    
    $pageUrl = $baseName ?: 'index.php';
    if (!empty($query)) {
        $pageUrl .= '?' . $query;
    }

    $userAgent = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Throttle ultraleve em sessão: evita gravação dupla se for o mesmo IP, página e usuário em < 1 segundo
    $sessKey = "last_access_log_{$userId}";
    $now = microtime(true);
    if (isset($_SESSION[$sessKey])) {
        $lastLog = $_SESSION[$sessKey];
        if (
            (($now - ($lastLog['time'] ?? 0)) < 1.0) &&
            (($lastLog['page'] ?? '') === $pageUrl) &&
            (($lastLog['ip'] ?? '') === $ip)
        ) {
            return;
        }
    }
    $_SESSION[$sessKey] = ['time' => $now, 'page' => $pageUrl, 'ip' => $ip];

    try {
        if (function_exists('getPDOConnection')) {
            $pdo = getPDOConnection();
            $stmt = $pdo->prepare("INSERT INTO user_access_logs (user_id, ip_address, user_agent, request_method, page_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $ip, $userAgent, $method, mb_substr($pageUrl, 0, 255)]);
        }
    } catch (\Throwable $e) {
        // Log silencioso em caso de falha de escrita
        error_log("Erro ao registrar log de acesso de usuário: " . $e->getMessage());
    }
}

/**
 * Redireciona para o login caso o usuário não esteja autenticado
 */
function requireAuth(): void {
    if (!isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
        header('Location: login.php');
        exit;
    }

    // Previne cache do navegador para garantir que todo clique no menu execute o PHP
    if (!headers_sent()) {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    logUserAccess();
}

/**
 * Exige que o usuário esteja autenticado e seja administrador
 */
function requireAdmin(): void {
    requireAuth();
    if (!isAdmin()) {
        http_response_code(403);
        echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Acesso Negado</title>";
        echo "<link href='https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap' rel='stylesheet'>";
        echo "<style>body{background:#090d16;color:#f8fafc;font-family:'Plus Jakarta Sans',sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}";
        echo ".card{background:rgba(30,41,59,0.8);border:1px solid rgba(239,68,68,0.3);padding:2rem;border-radius:16px;text-align:center;max-width:400px;}";
        echo "h1{color:#ef4444;font-size:1.4rem;margin-bottom:0.5rem;}p{color:#94a3b8;font-size:0.9rem;margin-bottom:1.5rem;}";
        echo "a{background:#8b5cf6;color:white;padding:0.6rem 1.2rem;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.9rem;}</style></head><body>";
        echo "<div class='card'><h1>🚫 Acesso Negado</h1><p>Você precisa de privilégios de administrador para acessar esta página.</p><a href='index.php'>Voltar ao Painel</a></div></body></html>";
        exit;
    }
}

/**
 * Encerra a sessão do usuário
 */
function logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: login.php');
    exit;
}
