<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Garante conexão e boot do banco de dados na abertura da página de login
try {
    $pdoBoot = getPDOConnection();
} catch (\Throwable $e) {
    // Ignora tratamentos adicionais se o banco estiver em boot
}

// Se já estiver logado, redireciona para a página principal
if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Por favor, preencha o e-mail e a senha.';
    } else {
        try {
            $pdo = getPDOConnection();
            $stmt = $pdo->prepare("SELECT id, email, password_hash, is_admin FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Login bem sucedido
                $_SESSION['user'] = [
                    'id' => (int)$user['id'],
                    'email' => $user['email'],
                    'is_admin' => (int)$user['is_admin'] === 1
                ];

                $clientIp = getClientIP();
                try {
                    $upd = $pdo->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?");
                    $upd->execute([$clientIp, (int)$user['id']]);
                } catch (\Throwable $t) {
                    // Ignora erro opcional de atualização de colunas
                }

                logUserAccess();

                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: {$redirect}");
                exit;
            } else {
                $error = 'E-mail ou senha incorretos.';
            }
        } catch (\Throwable $e) {
            $error = 'Erro no servidor: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>w99score - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #090d16;
            --card-bg: rgba(30, 41, 59, 0.65);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-purple: #8b5cf6;
            --accent-blue: #3b82f6;
            --accent-glow: rgba(139, 92, 246, 0.25);
            --live-red: #ef4444;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #090d16 75%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 420px;
            width: 100%;
            backdrop-filter: blur(16px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-logo {
            width: 54px;
            height: 54px;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: white;
            font-size: 1.6rem;
            margin: 0 auto 1rem auto;
            box-shadow: 0 6px 20px var(--accent-glow);
        }

        .brand-title {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            background: linear-gradient(to right, #ffffff, #c4b5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 0.35rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 0.85rem 1rem;
            color: white;
            font-size: 0.95rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .form-input:focus {
            border-color: var(--accent-purple);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            border: none;
            color: white;
            padding: 0.9rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px var(--accent-glow);
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.88rem;
            margin-bottom: 1.25rem;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-header">
        <div class="brand-logo">⚡</div>
        <h1 class="brand-title">w99score</h1>
        <p class="brand-subtitle">Entre com suas credenciais de acesso</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label class="form-label" for="email">E-mail de Acesso</label>
            <input type="email" id="email" name="email" class="form-input" placeholder="seu.email@exemplo.com" value="<?= htmlspecialchars($email) ?>" required autofocus>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Senha</label>
            <input type="password" id="password" name="password" class="form-input" placeholder="••••••••••••" required>
        </div>

        <button type="submit" class="btn-submit">Entrar no Sistema</button>
    </form>
</div>

</body>
</html>
