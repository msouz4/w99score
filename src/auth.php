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
 * Redireciona para o login caso o usuário não esteja autenticado
 */
function requireAuth(): void {
    if (!isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
        header('Location: login.php');
        exit;
    }
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
