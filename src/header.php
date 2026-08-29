<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
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
