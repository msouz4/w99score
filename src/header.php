<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<style>
.navbar {
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    position: sticky;
    top: 0;
    z-index: 50;
    padding: 1rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
}
.brand-logo {
    width: 38px;
    height: 38px;
    background: linear-gradient(135deg, #8b5cf6, #3b82f6);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    color: white;
    font-size: 1.2rem;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
}
.brand-title {
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    background: linear-gradient(to right, #ffffff, #c4b5fd);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.nav-links {
    display: flex;
    gap: 1rem;
}
.nav-item {
    color: #94a3b8;
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
@media (max-width: 768px) {
    .navbar {
        padding: 0.75rem 1rem;
        flex-direction: column;
        gap: 0.75rem;
    }
    .nav-links {
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.4rem;
    }
    .nav-item {
        padding: 0.4rem 0.75rem;
        font-size: 0.82rem;
    }
}
</style>
<nav class="navbar">
    <a href="index.php" class="brand">
        <div class="brand-logo">W99</div>
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
        <a href="ligas.php" class="nav-item <?= ($currentPage === 'ligas.php') ? 'active' : '' ?>">
            <svg class="svg-icon" viewBox="0 0 24 24"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0011 15.9V19H7v2h10v-2h-4v-3.1c2.04-.4 3.61-2.01 3.99-4.06C19.39 11.45 21 9.4 21 7V6c0-1.1-.9-1-2-1zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>
            Ligas e Jogos
        </a>
        <a href="favoritos.php" class="nav-item <?= ($currentPage === 'favoritos.php') ? 'active' : '' ?>">
            <svg class="svg-icon" style="fill: var(--amber-gold);" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
            Ligas Favoritas
        </a>
        <a href="analise.php" class="nav-item <?= ($currentPage === 'analise.php') ? 'active' : '' ?>">
            <svg class="svg-icon" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
            Análise Pré-Jogo
        </a>
    </div>
</nav>
