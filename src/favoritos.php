<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>w99score - Ligas Favoritas & Sincronização</title>
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
            --success-glow: rgba(16, 185, 129, 0.2);
            --live-red: #ef4444;
            --warning-bg: rgba(245, 158, 11, 0.15);
            --warning-border: rgba(245, 158, 11, 0.3);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #090d16 70%);
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 4rem;
        }

        .svg-icon {
            width: 18px;
            height: 18px;
            fill: currentColor;
            display: inline-block;
            vertical-align: middle;
        }

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

        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px var(--accent-glow);
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-top: 0.2rem;
        }

        /* Favorites Grid */
        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .fav-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.5rem;
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .fav-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .fav-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 6px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .fav-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: white;
        }

        .fav-category {
            font-size: 0.75rem;
            font-weight: 700;
            color: #a7f3d0;
            text-transform: uppercase;
        }

        .fav-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn-sync {
            flex: 1;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px var(--accent-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-sync:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-view-db {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-main);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-view-db:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Synced Matches Section */
        .section-title-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filters-db {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-tab {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-tab.active {
            background: var(--accent-purple);
            color: white;
            border-color: transparent;
        }

        /* Synced Matches Cards Table */
        .matches-db-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .db-match-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            backdrop-filter: blur(12px);
            display: grid;
            grid-template-columns: 140px 1fr 280px;
            align-items: center;
            gap: 1.5rem;
        }

        .db-match-card.incomplete {
            border-color: var(--warning-border);
            background: rgba(30, 41, 59, 0.4);
        }

        .match-meta-box {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .badge-incomplete {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: var(--warning-bg);
            border: 1px solid var(--warning-border);
            color: var(--amber-gold);
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 700;
            width: fit-content;
        }

        .badge-complete {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: var(--success-glow);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success);
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 700;
            width: fit-content;
        }

        .teams-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.25rem;
        }

        .team {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex: 1;
        }

        .team.home { justify-content: flex-end; text-align: right; }
        .team.away { justify-content: flex-start; text-align: left; }

        .team-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: white;
        }

        .team-flag {
            width: 26px;
            height: 26px;
            object-fit: contain;
        }

        .score-box {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.15rem;
            font-weight: 700;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--card-border);
            padding: 0.35rem 0.8rem;
            border-radius: 8px;
            min-width: 65px;
            text-align: center;
            color: #f1f5f9;
        }

        .stats-breakdown-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 0.75rem;
            font-size: 0.8rem;
            font-family: 'JetBrains Mono', monospace;
            text-align: center;
        }

        .stat-col {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .stat-title {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .stat-val {
            font-weight: 700;
            color: #f1f5f9;
        }

        /* Modais */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(9, 13, 22, 0.88);
            backdrop-filter: blur(8px);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-card {
            background: #111827;
            border: 1px solid var(--card-border);
            border-radius: 20px;
            max-width: 550px;
            width: 100%;
            padding: 2rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7);
        }

        .select-season-custom {
            width: 100%;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid var(--card-border);
            color: white;
            padding: 0.85rem 1rem;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
            outline: none;
            margin: 1.25rem 0;
            cursor: pointer;
        }

        .progress-bar-container {
            width: 100%;
            height: 12px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 9999px;
            overflow: hidden;
            margin: 1.5rem 0;
        }

        .progress-bar-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--accent-purple), var(--accent-blue));
            border-radius: 9999px;
            transition: width 0.2s ease;
        }

        .sync-status-text {
            font-size: 0.88rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            font-family: 'JetBrains Mono', monospace;
        }

        .loading-spinner {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php require_once __DIR__ . '/header.php'; ?>


    <!-- Main Container -->
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <svg class="svg-icon" style="width: 28px; height: 28px; fill: var(--amber-gold);" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                    Ligas Favoritas
                </h1>
                <p class="page-subtitle">Escolha a temporada específica de cada liga para sincronizar com o MySQL.</p>
            </div>
        </div>

        <!-- Favorites Cards Grid -->
        <div class="favorites-grid" id="favoritesContainer">
            <div class="loading-spinner">Carregando ligas favoritas...</div>
        </div>

        <!-- Synced Matches Section -->
        <div class="section-title-group">
            <div>
                <h2 class="section-title">
                    <svg class="svg-icon" style="width: 24px; height: 24px;" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                    Partidas Sincronizadas no Banco
                </h2>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Histórico de estatísticas salvas no MySQL com auditoria de dados incompletos.</p>
            </div>
            
            <div class="filters-db">
                <button class="btn-tab active" onclick="filterDbTab('all', this)">Todas as Partidas</button>
                <button class="btn-tab" onclick="filterDbTab('valid', this)">✓ Válidas para Análise</button>
                <button class="btn-tab" onclick="filterDbTab('incomplete', this)">⚠️ Dados Incompletos (Flagged)</button>
            </div>
        </div>

        <div class="matches-db-grid" id="dbMatchesContainer">
            <div class="loading-spinner">Carregando dados sincronizados...</div>
        </div>
    </div>

    <!-- Modal 1: Escolha da Temporada Específica -->
    <div class="modal-overlay" id="selectSeasonModal">
        <div class="modal-card">
            <h3 style="font-size: 1.3rem; margin-bottom: 0.4rem;" id="seasonModalTitle">Selecionar Temporada</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Escolha a temporada específica que você deseja sincronizar:</p>
            
            <select id="syncSeasonSelect" class="select-season-custom">
                <option>Carregando temporadas disponíveis...</option>
            </select>

            <div style="display: flex; gap: 0.75rem;">
                <button class="btn-sync" onclick="confirmSeasonSync()">
                    <svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46A7.93 7.93 0 0020 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74A7.93 7.93 0 004 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>
                    <span>Iniciar Sincronização</span>
                </button>
                <button class="btn-view-db" onclick="closeSeasonModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Modal 2: Progresso da Sincronização -->
    <div class="modal-overlay" id="syncProgressModal">
        <div class="modal-card" style="text-align: center;">
            <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem;" id="progressModalTitle">⚡ Sincronizando Temporada...</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Baixando jogos e estatísticas por tempo (HT/FT) da temporada selecionada.</p>
            
            <div class="progress-bar-container">
                <div class="progress-bar-fill" id="progressBarFill"></div>
            </div>

            <div class="sync-status-text" id="syncStatusText">Iniciando...</div>

            <button id="btnFinishSync" class="btn-tab" style="display: none; margin-top: 1rem;" onclick="closeProgressModal()">Concluído</button>
        </div>
    </div>

    <script>
        let favoriteLeagues = [];
        let allDbMatches = [];
        let selectedLeagueForSync = null;

        document.addEventListener('DOMContentLoaded', () => {
            loadFavorites();
            loadDbMatches();
        });

        async function loadFavorites() {
            const container = document.getElementById('favoritesContainer');
            try {
                const response = await fetch('api.php?action=get_favorites');
                const result = await response.json();

                if (result.success) {
                    favoriteLeagues = result.data;
                    renderFavorites(favoriteLeagues);
                } else {
                    container.innerHTML = `<div class="loading-spinner">Erro ao carregar favoritos.</div>`;
                }
            } catch (err) {
                container.innerHTML = `<div class="loading-spinner">Erro de conexão.</div>`;
            }
        }

        function renderFavorites(favs) {
            const container = document.getElementById('favoritesContainer');
            if (favs.length === 0) {
                container.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: var(--card-bg); border-radius: 16px; border: 1px solid var(--card-border);">
                        <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 1rem;">Nenhuma liga adicionada aos favoritos ainda.</p>
                        <a href="ligas.php" class="btn-tab" style="text-decoration: none; display: inline-block;">Ir para Ligas e Favoritar</a>
                    </div>
                `;
                return;
            }

            container.innerHTML = favs.map(fav => `
                <div class="fav-card">
                    <div class="fav-header">
                        <img class="fav-logo" src="api.php?action=get_image&type=tournament&id=${fav.tournament_id}" alt="${fav.name}" onerror="this.src='https://www.sofascore.com/static/images/default-tournament.png'">
                        <div>
                            <span class="fav-category">${fav.category_name || 'Futebol'}</span>
                            <h3 class="fav-name">${fav.name}</h3>
                        </div>
                    </div>
                    <div class="fav-actions">
                        <button class="btn-sync" onclick="openSeasonSelection(${fav.tournament_id}, '${escapeHtml(fav.name)}')">
                            <svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46A7.93 7.93 0 0020 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74A7.93 7.93 0 004 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>
                            <span>Escolher Temporada e Sincronizar</span>
                        </button>
                        <button class="btn-view-db" onclick="filterDbMatchesByLeague(${fav.tournament_id})">
                            <svg class="svg-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                            <span>Ver Jogos</span>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        async function openSeasonSelection(tournamentId, name) {
            selectedLeagueForSync = { tournamentId, name };
            document.getElementById('seasonModalTitle').innerText = `Sincronizar: ${name}`;
            const selectEl = document.getElementById('syncSeasonSelect');
            selectEl.innerHTML = '<option>Carregando temporadas disponíveis...</option>';
            document.getElementById('selectSeasonModal').classList.add('active');

            try {
                const sResp = await fetch(`api.php?action=get_seasons&tournament_id=${tournamentId}`);
                const sRes = await sResp.json();

                if (sRes.success && sRes.data.length > 0) {
                    selectEl.innerHTML = sRes.data.map(s => `
                        <option value="${s.id}" data-name="${escapeHtml(s.name)}">${s.name} (${s.year || ''})</option>
                    `).join('');
                } else {
                    selectEl.innerHTML = '<option value="">Nenhuma temporada encontrada</option>';
                }
            } catch (err) {
                selectEl.innerHTML = '<option value="">Erro ao carregar temporadas</option>';
            }
        }

        function closeSeasonModal() {
            document.getElementById('selectSeasonModal').classList.remove('active');
        }

        async function confirmSeasonSync() {
            const selectEl = document.getElementById('syncSeasonSelect');
            const seasonId = selectEl.value;
            if (!seasonId) return;

            const selectedOption = selectEl.options[selectEl.selectedIndex];
            const seasonName = selectedOption.getAttribute('data-name') || selectedOption.text;

            closeSeasonModal();
            startSpecificSeasonSync(selectedLeagueForSync.tournamentId, selectedLeagueForSync.name, seasonId, seasonName);
        }

        async function startSpecificSeasonSync(tournamentId, leagueName, seasonId, seasonName) {
            const progressModal = document.getElementById('syncProgressModal');
            const modalTitle = document.getElementById('progressModalTitle');
            const progressFill = document.getElementById('progressBarFill');
            const statusText = document.getElementById('syncStatusText');
            const btnFinish = document.getElementById('btnFinishSync');

            modalTitle.innerText = `⚡ ${leagueName} (${seasonName})`;
            progressFill.style.width = '0%';
            statusText.innerText = `Buscando jogos da temporada ${seasonName}...`;
            btnFinish.style.display = 'none';
            progressModal.classList.add('active');

            try {
                const mResp = await fetch(`api.php?action=get_matches&tournament_id=${tournamentId}&season_id=${seasonId}`);
                const mRes = await mResp.json();

                if (!mRes.success || mRes.data.length === 0) {
                    statusText.innerText = `Nenhum jogo encontrado na temporada ${seasonName}.`;
                    btnFinish.style.display = 'inline-block';
                    return;
                }

                const events = mRes.data;
                const total = events.length;
                const chunkSize = 25;
                let syncedCount = 0;
                let skippedCount = 0;
                let incompleteCount = 0;

                for (let i = 0; i < total; i += chunkSize) {
                    const chunk = events.slice(i, i + chunkSize);
                    const currentStep = Math.min(i + chunkSize, total);
                    const percent = Math.round((currentStep / total) * 100);
                    
                    progressFill.style.width = `${percent}%`;
                    statusText.innerText = `[${currentStep}/${total}] Sincronizando partidas da temporada ${seasonName}...`;

                    try {
                        const syncResp = await fetch('api.php?action=batch_sync_matches', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                events: chunk,
                                season_id: seasonId,
                                season_name: seasonName
                            })
                        });
                        const syncRes = await syncResp.json();
                        if (syncRes.success && syncRes.data) {
                            syncedCount += syncRes.data.synced || 0;
                            skippedCount += syncRes.data.skipped || 0;
                            incompleteCount += syncRes.data.incomplete || 0;
                        }
                    } catch (e) {
                        console.error('Erro no lote de sincronização:', e);
                    }
                }

                progressFill.style.width = '100%';
                let resultSummary = `✔ Temporada ${seasonName} sincronizada com sucesso! `;
                if (syncedCount === 0) {
                    resultSummary += `Todas as ${skippedCount} partidas já estão atualizadas no banco de dados.`;
                } else {
                    resultSummary += `${syncedCount} partidas novas/atualizadas (${skippedCount} mantidas sem alteração por estarem concluídas ou futuras).`;
                }
                if (incompleteCount > 0) {
                    resultSummary += ` (${incompleteCount} com estatísticas parciais).`;
                }
                statusText.innerText = resultSummary;
                btnFinish.style.display = 'inline-block';

                loadDbMatches(tournamentId, false);

            } catch (err) {
                statusText.innerText = 'Erro ao processar sincronização da temporada.';
                btnFinish.style.display = 'inline-block';
            }
        }

        function closeProgressModal() {
            document.getElementById('syncProgressModal').classList.remove('active');
        }

        async function loadDbMatches(tournamentId = 0, onlyValid = false) {
            const container = document.getElementById('dbMatchesContainer');
            container.innerHTML = '<div class="loading-spinner">Carregando partidas salvas no MySQL...</div>';

            try {
                let url = 'api.php?action=get_db_matches';
                if (tournamentId > 0) url += `&tournament_id=${tournamentId}`;
                if (onlyValid) url += `&only_valid=1`;

                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    allDbMatches = result.data;
                    renderDbMatches(allDbMatches);
                } else {
                    container.innerHTML = '<div class="loading-spinner">Erro ao carregar banco de dados.</div>';
                }
            } catch (err) {
                container.innerHTML = '<div class="loading-spinner">Erro de conexão.</div>';
            }
        }

        function renderDbMatches(matches) {
            const container = document.getElementById('dbMatchesContainer');
            if (matches.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 3rem; background: var(--card-bg); border-radius: 16px; border: 1px solid var(--card-border);">
                        <p style="color: var(--text-muted);">Nenhuma partida encontrada no banco para os filtros selecionados.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = matches.map(m => {
                const isIncomplete = parseInt(m.is_stats_incomplete) === 1;
                const statusBadge = isIncomplete ? 
                    `<span class="badge-incomplete" title="${m.incomplete_reason || 'Dados parciais'}">⚠️ Dados Incompletos</span>` :
                    `<span class="badge-complete">✓ Válido p/ Estatísticas</span>`;

                const matchDate = m.start_timestamp ? new Date(m.start_timestamp * 1000).toLocaleDateString('pt-BR', {
                    timeZone: 'America/Sao_Paulo', day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit'
                }) : (m.match_date ? new Date(m.match_date).toLocaleDateString('pt-BR', { timeZone: 'America/Sao_Paulo', day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' }) : '');

                const gHt = `${m.home_score_ht ?? '-'} : ${m.away_score_ht ?? '-'}`;
                const gFt = `${m.home_score_ft ?? '-'} : ${m.away_score_ft ?? '-'}`;
                const cHt = `${m.home_corners_ht ?? '-'} : ${m.away_corners_ht ?? '-'}`;
                const cFt = `${m.home_corners_ft ?? '-'} : ${m.away_corners_ft ?? '-'}`;
                const yHt = `${m.home_yellow_cards_ht ?? '-'} : ${m.away_yellow_cards_ht ?? '-'}`;
                const yFt = `${m.home_yellow_cards_ft ?? '-'} : ${m.away_yellow_cards_ft ?? '-'}`;
                const sHt = `${m.home_shots_on_target_ht ?? '-'} : ${m.away_shots_on_target_ht ?? '-'}`;
                const sFt = `${m.home_shots_on_target_ft ?? '-'} : ${m.away_shots_on_target_ft ?? '-'}`;

                return `
                    <div class="db-match-card ${isIncomplete ? 'incomplete' : ''}">
                        <div class="match-meta-box">
                            ${statusBadge}
                            <span style="font-weight: 700; color: white; margin-top: 0.3rem;">
                                ${m.season_name ? m.season_name + ' • ' : ''}${m.round ? 'Rodada ' + m.round : ''}
                            </span>
                            <span>${matchDate}</span>
                        </div>

                        <div class="teams-container">
                            <div class="team home">
                                <span class="team-name">${m.home_team_name}</span>
                                <img class="team-flag" src="api.php?action=get_image&type=team&id=${m.home_team_id}" alt="" onerror="this.style.opacity=0.3">
                            </div>
                            <div class="score-box">
                                ${m.home_score_ft ?? '-'} : ${m.away_score_ft ?? '-'}
                            </div>
                            <div class="team away">
                                <img class="team-flag" src="api.php?action=get_image&type=team&id=${m.away_team_id}" alt="" onerror="this.style.opacity=0.3">
                                <span class="team-name">${m.away_team_name}</span>
                            </div>
                        </div>

                        <div class="stats-breakdown-grid">
                            <div class="stat-col">
                                <span class="stat-title">Gols</span>
                                <span class="stat-val">HT ${gHt}</span>
                                <span class="stat-val">FT ${gFt}</span>
                            </div>
                            <div class="stat-col">
                                <span class="stat-title">Escanteios</span>
                                <span class="stat-val">HT ${cHt}</span>
                                <span class="stat-val">FT ${cFt}</span>
                            </div>
                            <div class="stat-col">
                                <span class="stat-title">Cartões</span>
                                <span class="stat-val">HT ${yHt}</span>
                                <span class="stat-val">FT ${yFt}</span>
                            </div>
                            <div class="stat-col">
                                <span class="stat-title">Chutes Gol</span>
                                <span class="stat-val">HT ${sHt}</span>
                                <span class="stat-val">FT ${sFt}</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function filterDbTab(tab, btnEl) {
            document.querySelectorAll('.btn-tab').forEach(b => b.classList.remove('active'));
            btnEl.classList.add('active');

            if (tab === 'all') {
                loadDbMatches(0, false);
            } else if (tab === 'valid') {
                loadDbMatches(0, true);
            } else if (tab === 'incomplete') {
                const filtered = allDbMatches.filter(m => parseInt(m.is_stats_incomplete) === 1);
                renderDbMatches(filtered);
            }
        }

        function filterDbMatchesByLeague(tournamentId) {
            loadDbMatches(tournamentId, false);
        }

        function escapeHtml(str) {
            return str.replace(/'/g, "\\'").replace(/"/g, "&quot;");
        }
    </script>
</body>
</html>
