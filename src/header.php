<?php
require_once __DIR__ . '/auth.php';
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$user = currentUser();
?>
<nav class="navbar">
    <a href="index.php" class="brand">
        <span class="brand-title">w99score</span>
    </a>
    <div class="nav-links">
        <a href="index.php" class="nav-item <?= ($currentPage === 'index.php') ? 'active' : '' ?>">
            <svg class="svg-icon" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8v-10h-8v10zm0-18v6h8V3h-8z"/></svg>
            Dashboard
        </a>
        <a href="oportunidades.php" class="nav-item <?= ($currentPage === 'oportunidades.php') ? 'active' : '' ?>" style="<?= ($currentPage === 'oportunidades.php') ? 'color: #38bdf8;' : '' ?>">
            <svg class="svg-icon" style="fill: #38bdf8;" viewBox="0 0 24 24"><path d="M12 2l2.4 7.4h7.6l-6.1 4.5 2.3 7.1L12 16.5 5.8 21l2.3-7.1L2 9.4h7.6z"/></svg>
            Melhores Oportunidades
        </a>
        <a href="assertividade.php" class="nav-item <?= ($currentPage === 'assertividade.php') ? 'active' : '' ?>" style="<?= ($currentPage === 'assertividade.php') ? 'color: #10b981;' : '' ?>">
            <svg class="svg-icon" style="fill: #10b981;" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            Assertividade & Win Rate
        </a>

        <a href="ligas.php" class="nav-item <?= ($currentPage === 'ligas.php') ? 'active' : '' ?>">
            <svg class="svg-icon" viewBox="0 0 24 24"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0011 15.9V19H7v2h10v-2h-4v-3.1c2.04-.4 3.61-2.01 3.99-4.06C19.39 11.45 21 9.4 21 7V6c0-1.1-.9-1-2-1zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>
            Ligas e Jogos
        </a>
        <a href="analise.php" class="nav-item <?= ($currentPage === 'analise.php') ? 'active' : '' ?>">
            <svg class="svg-icon" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
            Análise Pré-Jogo
        </a>

        <?php if (isAdmin()): ?>
            <a href="usuarios.php" class="nav-item <?= ($currentPage === 'usuarios.php') ? 'active' : '' ?>" style="<?= ($currentPage === 'usuarios.php') ? 'color: #f59e0b;' : '' ?>">
                <svg class="svg-icon" style="fill: #f59e0b;" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                Usuários
            </a>
        <?php endif; ?>

        <?php if ($user): ?>
            <div style="display: inline-flex; align-items: center; gap: 0.75rem; margin-left: 0.5rem; padding-left: 0.75rem; border-left: 1px solid rgba(255, 255, 255, 0.1);">
                <span style="font-size: 0.82rem; color: #94a3b8; font-weight: 600;" title="<?= htmlspecialchars($user['email']) ?>">
                    👤 <?= htmlspecialchars(explode('@', $user['email'])[0]) ?>
                </span>
                <a href="logout.php" class="nav-item" style="color: #fca5a5; padding: 0.35rem 0.65rem; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3);" title="Sair do sistema">
                    Sair
                </a>
            </div>
        <?php endif; ?>
    </div>
</nav>

