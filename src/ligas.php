<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>w99score - Brasileirão Série A</title>
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
            --card-hover-border: rgba(139, 92, 246, 0.4);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-purple: #8b5cf6;
            --accent-blue: #3b82f6;
            --accent-glow: rgba(139, 92, 246, 0.25);
            --amber-gold: #f59e0b;
            --success: #10b981;
            --live-red: #ef4444;
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
            padding-bottom: 3rem;
        }

        .svg-icon {
            width: 18px;
            height: 18px;
            fill: currentColor;
            display: inline-block;
            vertical-align: middle;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* Hero Banner Série A */
        .serie-a-banner {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.95));
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2rem;
            backdrop-filter: blur(16px);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            position: relative;
            overflow: hidden;
        }

        .serie-a-banner::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15), transparent 70%);
            pointer-events: none;
        }

        .banner-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .tournament-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .banner-info h1 {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .banner-info p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-top: 0.35rem;
        }

        .banner-badges {
            display: flex;
            gap: 0.6rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }

        .badge-pill {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            text-transform: uppercase;
        }

        .banner-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-sync-action {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            color: white;
            border: none;
            padding: 0.75rem 1.4rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.92rem;
            cursor: pointer;
            box-shadow: 0 6px 16px var(--accent-glow);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-sync-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
        }

        .btn-fav-star {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-fav-star:hover, .btn-fav-star.active {
            background: rgba(245, 158, 11, 0.2);
            border-color: var(--amber-gold);
            color: var(--amber-gold);
        }

        /* Controls Toolbar */
        .controls-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            backdrop-filter: blur(12px);
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            align-items: center;
            justify-content: space-between;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .filter-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .select-custom {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid var(--card-border);
            color: white;
            padding: 0.55rem 1rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: inherit;
            outline: none;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .select-custom:focus {
            border-color: var(--accent-purple);
        }

        .search-input {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid var(--card-border);
            color: white;
            padding: 0.55rem 1rem 0.55rem 2.4rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: inherit;
            outline: none;
            width: 220px;
        }

        .search-wrapper {
            position: relative;
        }

        .search-wrapper .search-icon {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .matches-badge {
            font-size: 0.82rem;
            font-weight: 700;
            background: rgba(139, 92, 246, 0.2);
            color: #c4b5fd;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        /* Matches List */
        .matches-list {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .match-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 1rem 1.4rem;
            display: grid;
            grid-template-columns: 140px 1fr 120px;
            align-items: center;
            gap: 1.25rem;
            transition: all 0.2s ease;
        }

        .match-card:hover {
            background: var(--card-hover-bg);
            border-color: var(--card-hover-border);
            transform: translateY(-2px);
        }

        .match-meta {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .status-tag {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            width: fit-content;
        }

        .status-finished { background: rgba(255, 255, 255, 0.08); color: var(--text-muted); }
        .status-live { background: rgba(239, 68, 68, 0.2); color: var(--live-red); border: 1px solid rgba(239, 68, 68, 0.3); }
        .status-scheduled { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }

        .teams-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
        }

        .team {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }

        .team.home { justify-content: flex-end; text-align: right; }
        .team.away { justify-content: flex-start; text-align: left; }

        .team-name {
            font-size: 1rem;
            font-weight: 600;
            color: white;
        }

        .team-flag {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }

        .score-box {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.25rem;
            font-weight: 700;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid var(--card-border);
            padding: 0.4rem 0.9rem;
            border-radius: 10px;
            min-width: 75px;
            text-align: center;
            color: #f1f5f9;
        }

        .match-actions {
            text-align: right;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .loading-spinner {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
            font-size: 1rem;
        }

        /* Modal de Sincronização */
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
            max-width: 580px;
            width: 100%;
            padding: 2rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7);
        }

        .progress-bar-track {
            height: 12px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 6px;
            overflow: hidden;
            margin: 1.25rem 0 0.75rem 0;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-purple), var(--success));
            width: 0%;
            transition: width 0.3s ease;
        }

        .sync-status-msg {
            font-size: 0.9rem;
            color: var(--text-muted);
            min-height: 1.5rem;
            line-height: 1.4;
        }

        .btn-finish-sync {
            background: var(--success);
            color: #064e3b;
            font-weight: 700;
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            margin-top: 1.25rem;
        }

        @media (max-width: 768px) {
            .match-card {
                grid-template-columns: 1fr;
                gap: 0.75rem;
                text-align: center;
            }
            .match-meta, .match-actions {
                text-align: center;
                align-items: center;
            }
            .serie-a-banner {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <div class="container">
        <!-- Hero Banner Série A -->
        <div class="serie-a-banner">
            <div class="banner-left">
                <img class="tournament-logo" src="api.php?action=get_image&type=tournament&id=325" alt="Brasileirão Série A" onerror="this.onerror=null; this.style.opacity=0.3;">
                <div class="banner-info">
                    <h1>Brasileirão Série A 🇧🇷</h1>
                    <p>Principal campeonato de futebol masculino do Brasil</p>
                    <div class="banner-badges">
                        <span class="badge-pill">Série A</span>
                        <span class="badge-pill">3 Anos Recentes (2024, 2025, 2026)</span>
                        <span class="badge-pill">38 Rodadas</span>
                    </div>
                </div>
            </div>
            <div class="banner-actions">
                <button class="btn-fav-star" id="btnFavStar" title="Favoritar Liga" onclick="toggleSerieAFavorite(this)">
                    <svg class="svg-icon" viewBox="0 0 24 24"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/></svg>
                </button>
                <button class="btn-sync-action" onclick="syncSerieAThreeYears()">
                    <span>⚡ Sincronizar 3 Anos (2024, 2025, 2026)</span>
                </button>
            </div>
        </div>

        <!-- Filtros e Controles -->
        <div class="controls-card">
            <div class="filter-group">
                <span class="filter-label">Temporada:</span>
                <select id="seasonSelect" class="select-custom" onchange="onSeasonSelectChange()">
                    <!-- Carregadas instantaneamente -->
                </select>

                <span class="filter-label" style="margin-left: 0.5rem;">Rodada:</span>
                <select id="roundSelect" class="select-custom" onchange="onRoundSelectChange()">
                    <option value="all">Todas as 38 Rodadas</option>
                </select>
            </div>

            <div class="filter-group">
                <div class="search-wrapper">
                    <span class="search-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    </span>
                    <input type="text" id="matchSearch" class="search-input" placeholder="Filtrar por time..." oninput="filterMatchesByTeam()">
                </div>
                <span id="matchCountBadge" class="matches-badge">Carregando...</span>
            </div>
        </div>

        <!-- Partidas -->
        <div id="matchesContainer" class="matches-list">
            <div class="loading-spinner">Carregando partidas da Série A...</div>
        </div>
    </div>

    <!-- Modal de Progresso de Sincronização -->
    <div class="modal-overlay" id="syncProgressModal">
        <div class="modal-card">
            <h2 id="syncTitle" style="font-size: 1.35rem; margin-bottom: 0.5rem; color: white;">⚡ Sincronizando Brasileirão Série A</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Baixando e atualizando partidas dos 3 anos recentes (2024, 2025 e 2026).</p>
            
            <div class="progress-bar-track">
                <div id="progressBarFill" class="progress-bar-fill"></div>
            </div>
            
            <div id="syncStatus" class="sync-status-msg">Preparando sincronização...</div>
            
            <div style="text-align: right;">
                <button id="btnFinish" class="btn-finish-sync" style="display: none;" onclick="closeSyncModal()">Concluído</button>
            </div>
        </div>
    </div>

    <script>
        const tournamentId = 325; // Brasileirão Série A
        const tournamentName = 'Brasileirão Série A';
        let availableSeasons = [
            { id: 87678, name: 'Brasileiro Serie A 2026', year: '2026' },
            { id: 72034, name: 'Brasileiro Serie A 2025', year: '2025' },
            { id: 58766, name: 'Brasileirão Betano 2024', year: '2024' }
        ];
        let currentSeasonId = 87678; // 2026 padrão
        let allMatches = [];

        const starFilledSvg = `<svg class="svg-icon" style="fill: var(--amber-gold);" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>`;
        const starOutlineSvg = `<svg class="svg-icon" viewBox="0 0 24 24"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/></svg>`;

        document.addEventListener('DOMContentLoaded', () => {
            initSeasons();
            checkFavoriteStatus();
        });

        // 1. Inicializa o seletor de temporadas
        async function initSeasons() {
            const seasonSelect = document.getElementById('seasonSelect');

            // Preenche imediatamente com os 3 anos garantidos
            renderSeasonsOptions(availableSeasons);

            // Popula as 38 rodadas padrão da Série A
            populateRounds();

            // Carrega as partidas da temporada atual
            loadMatches(currentSeasonId, 'all');

            // Tenta buscar atualizações de temporadas do backend
            try {
                const res = await fetch(`api.php?action=get_seasons&tournament_id=${tournamentId}&three_years=1`);
                const json = await res.json();
                if (json.success && Array.isArray(json.data) && json.data.length > 0) {
                    availableSeasons = json.data;
                    renderSeasonsOptions(availableSeasons);
                }
            } catch (e) {
                console.warn('Usando temporadas predefinidas garantidas.');
            }
        }

        function renderSeasonsOptions(seasons) {
            const seasonSelect = document.getElementById('seasonSelect');
            seasonSelect.innerHTML = seasons.map(s => `
                <option value="${s.id}" ${s.id == currentSeasonId ? 'selected' : ''}>${s.name} (${s.year || ''})</option>
            `).join('');
        }

        function populateRounds() {
            const roundSelect = document.getElementById('roundSelect');
            let html = '<option value="all">Todas as 38 Rodadas</option>';
            for (let i = 1; i <= 38; i++) {
                html += `<option value="${i}">Rodada ${i}</option>`;
            }
            roundSelect.innerHTML = html;
        }

        function onSeasonSelectChange() {
            currentSeasonId = document.getElementById('seasonSelect').value;
            const round = document.getElementById('roundSelect').value;
            loadMatches(currentSeasonId, round);
        }

        function onRoundSelectChange() {
            const round = document.getElementById('roundSelect').value;
            loadMatches(currentSeasonId, round);
        }

        // 2. Carrega partidas da temporada selecionada
        async function loadMatches(seasonId, round = 'all') {
            const container = document.getElementById('matchesContainer');
            const badge = document.getElementById('matchCountBadge');
            badge.innerText = 'Carregando...';
            container.innerHTML = '<div class="loading-spinner">Carregando partidas da Série A...</div>';

            try {
                let url = `api.php?action=get_matches&tournament_id=${tournamentId}&season_id=${seasonId}`;
                if (round !== 'all') {
                    url += `&round=${round}`;
                }

                const resp = await fetch(url);
                const json = await resp.json();

                if (json.success && Array.isArray(json.data) && json.data.length > 0) {
                    allMatches = json.data;
                    renderMatches(allMatches);
                } else {
                    container.innerHTML = `
                        <div class="loading-spinner">
                            <p style="margin-bottom: 0.75rem;">Nenhuma partida encontrada nesta temporada.</p>
                            <button class="btn-sync-action" style="font-size: 0.85rem; padding: 0.5rem 1rem;" onclick="syncSerieAThreeYears()">
                                ⚡ Sincronizar Partidas Agora
                            </button>
                        </div>
                    `;
                    badge.innerText = '0 jogos';
                }
            } catch (err) {
                container.innerHTML = `
                    <div class="loading-spinner" style="color: var(--live-red);">
                        Erro ao carregar partidas. Clique no botão de sincronizar para atualizar os dados locais.
                    </div>
                `;
                badge.innerText = 'Erro';
            }
        }

        function renderMatches(events) {
            const container = document.getElementById('matchesContainer');
            const badge = document.getElementById('matchCountBadge');
            badge.innerText = `${events.length} jogos`;

            container.innerHTML = events.map(evt => {
                const homeTeam = evt.homeTeam ? evt.homeTeam.name : 'Casa';
                const awayTeam = evt.awayTeam ? evt.awayTeam.name : 'Fora';
                const homeLogo = evt.homeTeam ? `api.php?action=get_image&type=team&id=${evt.homeTeam.id}` : '';
                const awayLogo = evt.awayTeam ? `api.php?action=get_image&type=team&id=${evt.awayTeam.id}` : '';

                const homeScore = evt.homeScore && evt.homeScore.current !== undefined ? evt.homeScore.current : '-';
                const awayScore = evt.awayScore && evt.awayScore.current !== undefined ? evt.awayScore.current : '-';

                const statusType = evt.status ? evt.status.type : 'finished';
                let statusBadgeClass = 'status-finished';
                let statusLabel = 'Encerrado';

                if (statusType === 'inprogress') {
                    statusBadgeClass = 'status-live';
                    statusLabel = 'Ao Vivo';
                } else if (statusType === 'notstarted') {
                    statusBadgeClass = 'status-scheduled';
                    statusLabel = 'Agendado';
                }

                const startDate = evt.startTimestamp ? new Date(evt.startTimestamp * 1000).toLocaleDateString('pt-BR', {
                    timeZone: 'America/Sao_Paulo', day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit'
                }) : '';

                const roundInfo = evt.roundInfo ? `Rodada ${evt.roundInfo.round}` : '';

                return `
                    <div class="match-card" data-teams="${homeTeam.toLowerCase()} ${awayTeam.toLowerCase()}">
                        <div class="match-meta">
                            <span class="status-tag ${statusBadgeClass}">${statusLabel}</span>
                            <span>${startDate}</span>
                            <span>${roundInfo}</span>
                        </div>
                        <div class="teams-container">
                            <div class="team home">
                                <span class="team-name">${homeTeam}</span>
                                <img class="team-flag" src="${homeLogo}" alt="${homeTeam}" onerror="this.onerror=null; this.style.opacity=0.3;">
                            </div>
                            <div class="score-box">
                                ${homeScore} : ${awayScore}
                            </div>
                            <div class="team away">
                                <img class="team-flag" src="${awayLogo}" alt="${awayTeam}" onerror="this.onerror=null; this.style.opacity=0.3;">
                                <span class="team-name">${awayTeam}</span>
                            </div>
                        </div>
                        <div class="match-actions">
                            <a href="analise.php?event_id=${evt.id}" style="color: #c4b5fd; text-decoration: none; font-weight: 600;">
                                Analisar →
                            </a>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function filterMatchesByTeam() {
            const query = document.getElementById('matchSearch').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.match-card');
            let count = 0;

            cards.forEach(card => {
                const teams = card.getAttribute('data-teams') || '';
                if (teams.includes(query)) {
                    card.style.display = 'grid';
                    count++;
                } else {
                    card.style.display = 'none';
                }
            });

            document.getElementById('matchCountBadge').innerText = `${count} jogos`;
        }

        // 3. Sincronização dos 3 Anos (2024, 2025, 2026)
        async function syncSerieAThreeYears() {
            const modal = document.getElementById('syncProgressModal');
            const progressFill = document.getElementById('progressBarFill');
            const statusText = document.getElementById('syncStatus');
            const btnFinish = document.getElementById('btnFinish');

            progressFill.style.width = '0%';
            statusText.innerText = 'Iniciando sincronização dos 3 anos recentes...';
            btnFinish.style.display = 'none';
            modal.classList.add('active');

            try {
                const seasons = availableSeasons;
                const totalSeasons = seasons.length;
                let totalSynced = 0;
                let totalSkipped = 0;

                for (let sIdx = 0; sIdx < totalSeasons; sIdx++) {
                    const season = seasons[sIdx];
                    const seasonProgress = (sIdx / totalSeasons) * 100;
                    statusText.innerText = `[${sIdx + 1}/${totalSeasons}] Baixando partidas de ${season.name}...`;

                    const mResp = await fetch(`api.php?action=get_matches&tournament_id=${tournamentId}&season_id=${season.id}`);
                    const mJson = await mResp.json();

                    if (!mJson.success || !mJson.data || mJson.data.length === 0) continue;

                    const events = mJson.data;
                    const totalEvents = events.length;
                    const chunkSize = 25;

                    for (let i = 0; i < totalEvents; i += chunkSize) {
                        const chunk = events.slice(i, i + chunkSize);
                        const progress = (Math.min(i + chunkSize, totalEvents) / totalEvents) * (100 / totalSeasons);
                        progressFill.style.width = `${Math.round(seasonProgress + progress)}%`;
                        statusText.innerText = `[${season.name}] Sincronizando partidas (${Math.min(i + chunkSize, totalEvents)}/${totalEvents})...`;

                        try {
                            const syncResp = await fetch('api.php?action=batch_sync_matches', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    events: chunk,
                                    season_id: season.id,
                                    season_name: season.name
                                })
                            });
                            const syncRes = await syncResp.json();
                            if (syncRes.success && syncRes.data) {
                                totalSynced += syncRes.data.synced || 0;
                                totalSkipped += syncRes.data.skipped || 0;
                            }
                        } catch (e) {
                            console.error('Erro no lote:', e);
                        }
                    }
                }

                progressFill.style.width = '100%';
                statusText.innerHTML = `
                    <strong style="color: var(--success);">✔ Sincronização dos 3 anos concluída!</strong><br>
                    Temporadas: <strong>${seasons.map(s => s.name).join(', ')}</strong><br>
                    Partidas novas/atualizadas: <strong>${totalSynced}</strong> (${totalSkipped} mantidas sem alteração).
                `;
                btnFinish.style.display = 'inline-block';
                loadMatches(currentSeasonId, document.getElementById('roundSelect').value);

            } catch (err) {
                statusText.innerText = 'Ocorreu um erro durante a sincronização.';
                btnFinish.style.display = 'inline-block';
            }
        }

        function closeSyncModal() {
            document.getElementById('syncProgressModal').classList.remove('active');
        }

        // 4. Favoritar / Desfavoritar
        async function checkFavoriteStatus() {
            try {
                const resp = await fetch('api.php?action=get_favorites');
                const json = await resp.json();
                if (json.success && Array.isArray(json.data)) {
                    const isFav = json.data.some(f => f.tournament_id == tournamentId);
                    const btn = document.getElementById('btnFavStar');
                    if (isFav) {
                        btn.classList.add('active');
                        btn.innerHTML = starFilledSvg;
                    } else {
                        btn.classList.remove('active');
                        btn.innerHTML = starOutlineSvg;
                    }
                }
            } catch (e) {}
        }

        async function toggleSerieAFavorite(buttonEl) {
            try {
                const formData = new FormData();
                formData.append('tournament_id', tournamentId);
                formData.append('name', tournamentName);
                formData.append('category_name', 'Brasil');
                formData.append('logo_url', 'api.php?action=get_image&type=tournament&id=325');

                const resp = await fetch('api.php?action=toggle_favorite', {
                    method: 'POST',
                    body: formData
                });
                const json = await resp.json();
                if (json.success) {
                    if (json.data.is_favorite) {
                        buttonEl.classList.add('active');
                        buttonEl.innerHTML = starFilledSvg;
                    } else {
                        buttonEl.classList.remove('active');
                        buttonEl.innerHTML = starOutlineSvg;
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }
    </script>
</body>
</html>
