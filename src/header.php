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

        <button type="button" onclick="openFavoritesModal()" class="nav-item" style="background: rgba(245, 158, 11, 0.12); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); cursor: pointer;" title="Gerenciar Times Favoritos">
            <svg class="svg-icon" style="fill: #fbbf24;" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
            Meus Favoritos
        </button>

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

<!-- Modal Global de Gerenciamento de Times Favoritos -->
<style>
.fav-modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(9, 13, 22, 0.8);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.fav-modal-card {
    background: #1e293b;
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 50px rgba(0,0,0,0.6);
    overflow: hidden;
}
.fav-modal-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(15, 23, 42, 0.6);
}
.fav-modal-header h3 {
    font-size: 1.1rem;
    color: #fbbf24;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.fav-modal-close {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.5rem;
    cursor: pointer;
    line-height: 1;
}
.fav-modal-close:hover { color: #white; }
.fav-modal-body {
    padding: 1.25rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.fav-search-input {
    width: 100%;
    padding: 0.75rem 1rem;
    background: #0f172a;
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 10px;
    color: #f8fafc;
    font-size: 0.9rem;
    outline: none;
}
.fav-search-input:focus {
    border-color: #fbbf24;
}
.fav-team-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.6rem 0.8rem;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 8px;
    transition: all 0.2s;
}
.fav-team-item:hover {
    background: rgba(255,255,255,0.07);
}
.fav-team-info {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.88rem;
    font-weight: 600;
}
.fav-team-logo {
    width: 24px;
    height: 24px;
    object-fit: contain;
}
.fav-btn-star {
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    transition: transform 0.15s ease;
}
.fav-btn-star:hover {
    transform: scale(1.2);
}
.fav-list-container {
    max-height: 200px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
</style>

<div id="favoritesModal" class="fav-modal-overlay" style="display:none;" onclick="if(event.target===this)closeFavoritesModal()">
    <div class="fav-modal-card">
        <div class="fav-modal-header">
            <h3>⭐ Meus Times Favoritos</h3>
            <button type="button" class="fav-modal-close" onclick="closeFavoritesModal()">&times;</button>
        </div>
        <div class="fav-modal-body">
            <div>
                <input type="text" id="favSearchInput" class="fav-search-input" placeholder="🔍 Digite o nome do time para buscar e favoritar..." oninput="debounceSearchFavTeams()">
            </div>
            <div id="favSearchResults" class="fav-list-container"></div>
            
            <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 0.5rem 0;">
            
            <h4 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">
                Seus Times Favoritados (<span id="favCountSpan">0</span>)
            </h4>
            <div id="favMyList" class="fav-list-container"></div>
        </div>
    </div>
</div>

<script>
window.userFavoriteTeamIds = [];
let searchFavDebounceTimer = null;

function openFavoritesModal() {
    document.getElementById('favoritesModal').style.display = 'flex';
    loadUserFavorites();
    searchFavTeams('');
}

function closeFavoritesModal() {
    document.getElementById('favoritesModal').style.display = 'none';
}

function loadUserFavorites(onComplete) {
    fetch('api.php?action=get_favorite_teams')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                window.userFavoriteTeamIds = res.team_ids || [];
                renderFavMyList(res.data || []);
                if (typeof window.onFavoritesUpdated === 'function') {
                    window.onFavoritesUpdated(window.userFavoriteTeamIds);
                }
                if (typeof onComplete === 'function') onComplete();
            }
        }).catch(err => console.error(err));
}

function renderFavMyList(list) {
    const container = document.getElementById('favMyList');
    const countSpan = document.getElementById('favCountSpan');
    if (countSpan) countSpan.textContent = list.length;

    if (!list || list.length === 0) {
        container.innerHTML = '<div style="font-size: 0.85rem; color: #64748b; font-style: italic; padding: 0.5rem 0;">Nenhum time favoritado ainda.</div>';
        return;
    }

    container.innerHTML = list.map(t => `
        <div class="fav-team-item">
            <div class="fav-team-info">
                ${t.team_logo ? `<img src="${t.team_logo}" class="fav-team-logo" alt="">` : '⚽'}
                <span>${t.team_name}</span>
            </div>
            <button type="button" class="fav-btn-star" title="Remover dos Favoritos" onclick="toggleFavoriteGlobal(${t.team_id}, '${t.team_name.replace(/'/g, "\\'")}', '${(t.team_logo||'').replace(/'/g, "\\'")}')">
                ⭐
            </button>
        </div>
    `).join('');
}

function debounceSearchFavTeams() {
    clearTimeout(searchFavDebounceTimer);
    searchFavDebounceTimer = setTimeout(() => {
        const q = document.getElementById('favSearchInput').value;
        searchFavTeams(q);
    }, 300);
}

function searchFavTeams(q) {
    fetch(`api.php?action=search_teams&q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                renderFavSearchResults(res.data || []);
            }
        }).catch(err => console.error(err));
}

function renderFavSearchResults(teams) {
    const container = document.getElementById('favSearchResults');
    if (!teams || teams.length === 0) {
        container.innerHTML = '<div style="font-size: 0.85rem; color: #64748b; padding: 0.5rem 0;">Nenhum time encontrado.</div>';
        return;
    }

    container.innerHTML = teams.map(t => {
        const isFav = window.userFavoriteTeamIds.includes(parseInt(t.team_id));
        return `
            <div class="fav-team-item">
                <div class="fav-team-info">
                    ${t.team_logo ? `<img src="${t.team_logo}" class="fav-team-logo" alt="">` : '⚽'}
                    <span>${t.team_name}</span>
                </div>
                <button type="button" class="fav-btn-star" title="${isFav ? 'Remover dos Favoritos' : 'Adicionar aos Favoritos'}" onclick="toggleFavoriteGlobal(${t.team_id}, '${t.team_name.replace(/'/g, "\\'")}', '${(t.team_logo||'').replace(/'/g, "\\'")}')">
                    ${isFav ? '⭐' : '☆'}
                </button>
            </div>
        `;
    }).join('');
}

function toggleFavoriteGlobal(teamId, teamName, teamLogo) {
    fetch(`api.php?action=toggle_favorite_team`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `team_id=${encodeURIComponent(teamId)}&team_name=${encodeURIComponent(teamName || '')}&team_logo=${encodeURIComponent(teamLogo || '')}`
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.userFavoriteTeamIds = res.team_ids || [];
            loadUserFavorites();
            const q = document.getElementById('favSearchInput') ? document.getElementById('favSearchInput').value : '';
            searchFavTeams(q);
        }
    }).catch(err => console.error(err));
}

// Carrega os favoritos iniciais em segundo plano ao abrir a página
document.addEventListener('DOMContentLoaded', () => {
    loadUserFavorites();
});
</script>


