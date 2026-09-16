<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Apenas administradores podem acessar
requireAdmin();

$pdo = getPDOConnection();
$msgSuccess = '';
$msgError = '';
$generatedPasswordAlert = null;

// Processamento de Ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        $email = trim($_POST['email'] ?? '');
        $isAdminFlag = isset($_POST['is_admin']) ? 1 : 0;

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msgError = 'Por favor, informe um endereço de e-mail válido.';
        } else {
            try {
                // Verificar se o e-mail já está cadastrado
                $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $chk->execute([$email]);
                if ($chk->fetch()) {
                    $msgError = "O e-mail '{$email}' já está cadastrado no sistema.";
                } else {
                    $rawPass = bin2hex(random_bytes(8)); // Senha de 16 caracteres
                    $hash = password_hash($rawPass, PASSWORD_BCRYPT);

                    $ins = $pdo->prepare("INSERT INTO users (email, password_hash, is_admin) VALUES (?, ?, ?)");
                    $ins->execute([$email, $hash, $isAdminFlag]);

                    $msgSuccess = "Usuário '{$email}' criado com sucesso!";
                    $generatedPasswordAlert = [
                        'email' => $email,
                        'password' => $rawPass,
                        'title' => 'Novo Usuário Criado!'
                    ];
                }
            } catch (\Throwable $e) {
                $msgError = 'Erro ao criar usuário: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'reset_password') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            try {
                $chk = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                $chk->execute([$userId]);
                $u = $chk->fetch();

                if ($u) {
                    $rawPass = bin2hex(random_bytes(8));
                    $hash = password_hash($rawPass, PASSWORD_BCRYPT);

                    $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $upd->execute([$hash, $userId]);

                    $msgSuccess = "Senha do usuário '{$u['email']}' redefinida com sucesso!";
                    $generatedPasswordAlert = [
                        'email' => $u['email'],
                        'password' => $rawPass,
                        'title' => 'Nova Senha Gerada!'
                    ];
                }
            } catch (\Throwable $e) {
                $msgError = 'Erro ao redefinir senha: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $currUser = currentUser();

        if ($userId === (int)$currUser['id']) {
            $msgError = 'Você não pode excluir o seu próprio usuário logado.';
        } else {
            try {
                $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $del->execute([$userId]);
                $msgSuccess = 'Usuário excluído com sucesso.';
            } catch (\Throwable $e) {
                $msgError = 'Erro ao excluir usuário: ' . $e->getMessage();
            }
        }
    }
}

// Buscar lista de usuários cadastrados
$users = [];
try {
    $stmt = $pdo->query("SELECT id, email, is_admin, created_at FROM users ORDER BY is_admin DESC, email ASC");
    $users = $stmt->fetchAll();
} catch (\Throwable $e) {
    $msgError = 'Erro ao carregar usuários: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>w99score - Gestão de Usuários</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #090d16;
            --bg-nav: rgba(15, 23, 42, 0.85);
            --card-bg: rgba(30, 41, 59, 0.6);
            --card-hover-bg: rgba(30, 41, 59, 0.95);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-purple: #8b5cf6;
            --accent-blue: #3b82f6;
            --accent-glow: rgba(139, 92, 246, 0.25);
            --amber-gold: #f59e0b;
            --success: #10b981;
            --live-red: #ef4444;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #090d16 70%);
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 3rem;
        }

        .svg-icon {
            width: 18px;
            height: 18px;
            fill: currentColor;
            display: inline-block;
            vertical-align: middle;
        }

        /* Navbar Header */
        .navbar {
            background: var(--bg-nav);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--card-border);
            position: sticky;
            top: 0;
            z-index: 50;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; }

        .brand-title {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            background: linear-gradient(to right, #ffffff, #c4b5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links { display: flex; gap: 1rem; align-items: center; }

        .nav-item {
            color: var(--text-muted);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .nav-item:hover, .nav-item.active {
            color: white;
            background: rgba(255, 255, 255, 0.06);
        }

        .container {
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .page-header { margin-bottom: 2rem; }
        .page-title { font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem; }
        .page-subtitle { color: var(--text-muted); font-size: 1rem; }

        .panel-grid {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 850px) {
            .panel-grid { grid-template-columns: 1fr; }
        }

        .card-panel {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.5rem;
            backdrop-filter: blur(12px);
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; }

        .form-input {
            width: 100%;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: white;
            font-size: 0.9rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-input:focus { border-color: var(--accent-purple); }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #e2e8f0;
            cursor: pointer;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            border: none;
            color: white;
            padding: 0.8rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .btn-submit:hover { transform: translateY(-2px); }

        /* Banner de Senha Gerada */
        .password-alert-box {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.18), rgba(6, 182, 212, 0.12));
            border: 1px solid rgba(16, 185, 129, 0.4);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            color: #ecfdf5;
        }

        .password-alert-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: #34d399;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .password-display-field {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-top: 0.75rem;
        }

        .password-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.25rem;
            font-weight: 700;
            color: #a7f3d0;
            letter-spacing: 0.05em;
            flex: 1;
        }

        .btn-copy {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-copy:hover { background: rgba(16, 185, 129, 0.35); }

        .alert-msg {
            padding: 0.85rem 1.25rem;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        .alert-msg.success { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #6ee7b7; }
        .alert-msg.error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; }

        /* Tabela de Usuários */
        .users-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .users-table th {
            text-align: left;
            padding: 0.85rem 1rem;
            background: rgba(15, 23, 42, 0.6);
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--card-border);
        }

        .users-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: middle;
        }

        .badge-role {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-role.admin { background: rgba(245, 158, 11, 0.18); border: 1px solid rgba(245, 158, 11, 0.4); color: var(--amber-gold); }
        .badge-role.user { background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); color: #93c5fd; }

        .btn-action-sm {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: white;
            padding: 0.4rem 0.7rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-action-sm:hover { background: rgba(255, 255, 255, 0.12); }
        .btn-action-danger { background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.3); color: #fca5a5; }
        .btn-action-danger:hover { background: rgba(239, 68, 68, 0.25); color: white; }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Gestão de Usuários</h1>
        <p class="page-subtitle">Cadastre novos usuários, redefina senhas e controle os níveis de acesso (Admin / Usuário).</p>
    </div>

    <?php if ($msgSuccess): ?>
        <div class="alert-msg success"><?= htmlspecialchars($msgSuccess) ?></div>
    <?php endif; ?>

    <?php if ($msgError): ?>
        <div class="alert-msg error"><?= htmlspecialchars($msgError) ?></div>
    <?php endif; ?>

    <?php if ($generatedPasswordAlert): ?>
        <div class="password-alert-box">
            <div class="password-alert-title">
                <span>🔑</span> <?= htmlspecialchars($generatedPasswordAlert['title']) ?>
            </div>
            <p style="font-size: 0.9rem; color: #cbd5e1;">
                A senha abaixo foi gerada aleatoriamente para <strong><?= htmlspecialchars($generatedPasswordAlert['email']) ?></strong>. Copie e envie ao usuário. Ela não poderá ser visualizada novamente.
            </p>
            <div class="password-display-field">
                <span class="password-value" id="generatedPasswordVal"><?= htmlspecialchars($generatedPasswordAlert['password']) ?></span>
                <button class="btn-copy" onclick="copyPassword()">Copiar Senha</button>
            </div>
        </div>
    <?php endif; ?>

    <div class="panel-grid">
        <!-- Formulário de Criar Usuário -->
        <div class="card-panel">
            <h2 class="card-title">
                <svg class="svg-icon" style="fill: var(--accent-purple);" viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                Novo Usuário
            </h2>
            <form method="POST" action="usuarios.php">
                <input type="hidden" name="action" value="create_user">

                <div class="form-group">
                    <label class="form-label" for="email">E-mail do Usuário</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="usuario@exemplo.com" required>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_admin" value="1" style="width: 18px; height: 18px; accent-color: var(--accent-purple);">
                         Conceder permissão de Administrador
                    </label>
                </div>

                <p style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                    ⚡ A senha será gerada aleatoriamente pelo sistema na criação.
                </p>

                <button type="submit" class="btn-submit">Criar Usuário</button>
            </form>
        </div>

        <!-- Tabela de Usuários Existentes -->
        <div class="card-panel">
            <h2 class="card-title">
                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                Usuários Cadastrados (<?= count($users) ?>)
            </h2>

            <table class="users-table">
                <thead>
                    <tr>
                        <th>E-mail</th>
                        <th>Nível</th>
                        <th>Criado Em</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($u['email']) ?></strong>
                            </td>
                            <td>
                                <?php if ((int)$u['is_admin'] === 1): ?>
                                    <span class="badge-role admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge-role user">Usuário</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">
                                <?= date('d/m/Y H:i', strtotime($u['created_at'])) ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.4rem;">
                                    <form method="POST" action="usuarios.php" style="display:inline;" onsubmit="return confirm('Gerar uma nova senha aleatória para <?= htmlspecialchars($u['email']) ?>?');">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn-action-sm" title="Gerar nova senha aleatória">Nova Senha</button>
                                    </form>

                                    <?php if ((int)$u['id'] !== (int)currentUser()['id']): ?>
                                        <form method="POST" action="usuarios.php" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir o usuário <?= htmlspecialchars($u['email']) ?>?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn-action-sm btn-action-danger" title="Excluir Usuário">Excluir</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function copyPassword() {
    const el = document.getElementById('generatedPasswordVal');
    if (!el) return;
    navigator.clipboard.writeText(el.innerText).then(() => {
        alert('Senha copiada para a área de transferência!');
    });
}
</script>

</body>
</html>
