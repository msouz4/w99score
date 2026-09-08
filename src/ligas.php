<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>w99score - Ligas por País</title>
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

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .header-badges {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }

        .info-pill {
            background: rgba(139, 92, 246, 0.15);
            border: 1px solid rgba(139, 92, 246, 0.3);
            color: #c4b5fd;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        /* Breadcrumb / Navigation Bar */
        .nav-toolbar {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1rem 1.25rem;
            backdrop-filter: blur(12px);
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
        }

        .breadcrumb-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .crumb-link {
            color: #c4b5fd;
            cursor: pointer;
            text-decoration: none;
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .crumb-link:hover {
            color: white;
            text-decoration: underline;
        }

        .crumb-separator {
            color: var(--text-muted);
        }

        .crumb-current {
            color: white;
            font-weight: 700;
        }

        .btn-back-countries {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--card-border);
            color: white;
            padding: 0.45rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-back-countries:hover {
            background: rgba(255, 255, 255, 0.16);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .search-box {
            flex: 1;
            min-width: 260px;
            max-width: 420px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 0.65rem 1rem 0.65rem 2.4rem;
            color: white;
            font-size: 0.9rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .search-box input:focus {
            border-color: var(--accent-purple);
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        /* Countries Grid */
        .countries-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.25rem;
        }

        .country-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .country-card:hover {
            background: var(--card-hover-bg);
            border-color: var(--card-hover-border);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }

        .country-main {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .country-emoji {
            font-size: 2.2rem;
            line-height: 1;
        }

        .country-info h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.2rem;
        }

        .country-info span {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .country-arrow {
            color: var(--accent-purple);
            font-size: 1.2rem;
            transition: transform 0.2s ease;
        }

        .country-card:hover .country-arrow {
            transform: translateX(4px);
            color: #c4b5fd;
        }

        /* Leagues Section & Grid */
        .leagues-section-header {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .leagues-country-title {
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .leagues-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .league-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.5rem;
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
            position: relative;
        }

        .league-card:hover {
            background: var(--card-hover-bg);
            border-color: var(--card-hover-border);
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.4);
        }

        .btn-star-favorite {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-star-favorite:hover, .btn-star-favorite.active {
            background: rgba(245, 158, 11, 0.2);
            border-color: var(--amber-gold);
            color: var(--amber-gold);
        }

        .league-top {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-right: 2.5rem;
        }

        .league-logo {
            width: 52px;
            height: 52px;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 6px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .league-info {
            flex: 1;
        }

        .category-badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #a7f3d0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.2rem;
        }

        .league-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: white;
            line-height: 1.3;
        }

        .league-actions {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .btn-view-matches {
            width: 100%;
            background: rgba(139, 92, 246, 0.15);
            border: 1px solid rgba(139, 92, 246, 0.3);
            color: #c4b5fd;
            padding: 0.65rem 1rem;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-view-matches:hover {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .btn-quick-sync {
            width: 100%;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            padding: 0.55rem 1rem;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .btn-quick-sync:hover {
            background: var(--success);
            color: #064e3b;
            border-color: transparent;
        }

        /* Modal for Matches */
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

        .modal-container {
            background: #111827;
            border: 1px solid var(--card-border);
            border-radius: 20px;
            max-width: 960px;
            width: 100%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7);
            overflow: hidden;
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal-container {
            transform: scale(1);
        }

        .modal-header {
            padding: 1.25rem 1.5rem;
            background: rgba(30, 41, 59, 0.8);
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .modal-league-logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .modal-toolbar {
            padding: 1rem 1.5rem;
            background: rgba(15, 23, 42, 0.6);
            border-bottom: 1px solid var(--card-border);
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
        }

        .controls-group {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .select-custom {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid var(--card-border);
            color: white;
            padding: 0.45rem 0.85rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
            outline: none;
            cursor: pointer;
        }

        .season-filter-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            color: var(--text-muted);
            cursor: pointer;
            user-select: none;
        }

        .season-filter-toggle input {
            cursor: pointer;
        }

        .matches-count-badge {
            font-size: 0.8rem;
            font-weight: 700;
            background: rgba(139, 92, 246, 0.2);
            color: #c4b5fd;
            padding: 0.3rem 0.75rem;
            border-radius: 9999px;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        .match-search-input {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid var(--card-border);
            color: white;
            padding: 0.45rem 0.85rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
            outline: none;
            width: 180px;
        }

        .btn-close {
            background: rgba(255, 255, 255, 0.05);
            border: none;
            color: var(--text-muted);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-close:hover {
            color: white;
            background: rgba(255, 255, 255, 0.15);
        }

        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }

        .matches-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .match-card {
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 0.9rem 1.25rem;
            display: grid;
            grid-template-columns: 110px 1fr 120px;
            align-items: center;
            gap: 1rem;
        }

        .match-meta {
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .status-tag {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            width: fit-content;
        }

        .status-finished { background: rgba(255, 255, 255, 0.1); color: var(--text-muted); }
        .status-live { background: rgba(239, 68, 68, 0.2); color: var(--live-red); border: 1px solid rgba(239, 68, 68, 0.3); }
        .status-scheduled { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }

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

        .loading-spinner {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
            font-size: 1rem;
        }

        /* Sync Progress Modal */
        .progress-box {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1rem 0;
        }

        .progress-bar-track {
            height: 12px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 1rem;
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
            margin-bottom: 0.5rem;
            min-height: 1.5rem;
        }

        .btn-finish-sync {
            background: var(--success);
            color: #064e3b;
            font-weight: 700;
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Ligas e Campeonatos</h1>
            <p class="page-subtitle">Navegue primeiro pelos países e consulte as 5 principais ligas masculinas de cada um.</p>
            <div class="header-badges">
                <span class="info-pill">⚽ Apenas Futebol Masculino</span>
                <span class="info-pill">🏆 Top 5 Principais por País</span>
                <span class="info-pill">⚡ Sincronização Focada nos 3 Anos (Anterior, Atual e Próximo)</span>
            </div>
        </div>

        <!-- Navigation & Search Toolbar -->
        <div class="nav-toolbar">
            <div class="breadcrumb-box" id="breadcrumbNav">
                <span class="crumb-current">🌐 Países</span>
            </div>

            <div class="search-box">
                <span class="search-icon">
                    <svg class="svg-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                </span>
                <input type="text" id="searchInput" placeholder="Buscar país..." oninput="onSearchInput()">
            </div>
        </div>

        <!-- View 1: Countries Grid -->
        <div id="countriesView">
            <div class="countries-grid" id="countriesContainer">
                <div class="loading-spinner">Carregando lista de países...</div>
            </div>
        </div>

        <!-- View 2: Country's Top 5 Leagues -->
        <div id="leaguesView" style="display: none;">
            <div class="leagues-section-header">
                <div class="leagues-country-title" id="selectedCountryHeader">
                    <!-- Preenchido dinamicamente -->
                </div>
                <button class="btn-back-countries" onclick="showCountriesView()">
                    ← Voltar aos Países
                </button>
            </div>
            <div class="leagues-grid" id="leaguesContainer">
                <div class="loading-spinner">Carregando ligas do país...</div>
            </div>
        </div>
    </div>

    <!-- Modal de Partidas -->
    <div class="modal-overlay" id="matchesModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title-group">
                    <img id="modalLeagueLogo" class="modal-league-logo" src="" alt="Liga Logo" onerror="this.onerror=null; this.style.opacity=0.3;">
                    <div>
                        <h2 id="modalLeagueTitle" class="modal-title">Nome da Liga</h2>
                    </div>
                </div>
                <button class="btn-close" onclick="closeModal()">✕</button>
            </div>

            <!-- Toolbar de Filtros do Modal -->
            <div class="modal-toolbar">
                <div class="controls-group">
                    <label style="font-size: 0.8rem; color: var(--text-muted);">Temporada:</label>
                    <select id="seasonSelector" class="select-custom" onchange="onSeasonChange()"></select>

                    <label class="season-filter-toggle">
                        <input type="checkbox" id="chkThreeYearsOnly" checked onchange="toggleThreeYearsModalFilter()">
                        Apenas 3 anos (Anterior, Atual, Próximo)
                    </label>

                    <label style="font-size: 0.8rem; color: var(--text-muted); margin-left: 0.5rem;">Rodada:</label>
                    <select id="roundSelector" class="select-custom" onchange="onRoundChange()">
                        <option value="all">Todas as Rodadas</option>
                    </select>
                </div>

                <div class="controls-group">
                    <button class="btn-quick-sync" onclick="syncModalLeagueThreeYears()">
                        ⚡ Sincronizar 3 Anos Recentes
                    </button>
                    <input type="text" id="matchSearchInput" class="match-search-input" placeholder="Filtrar time..." oninput="filterModalMatches()">
                    <span id="matchesCountBadge" class="matches-count-badge">0 jogos</span>
                </div>
            </div>

            <div class="modal-body">
                <div id="matchesContainer" class="matches-list">
                    <div class="loading-spinner">Carregando partidas...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Progresso de Sincronização -->
    <div class="modal-overlay" id="syncProgressModal">
        <div class="modal-container" style="max-width: 600px;">
            <div class="modal-header">
                <h2 id="syncProgressTitle" class="modal-title">⚡ Sincronizando Ligas</h2>
                <button class="btn-close" onclick="closeSyncProgressModal()">✕</button>
            </div>
            <div class="modal-body">
                <div class="progress-box">
                    <div class="progress-bar-track">
                        <div id="syncProgressBarFill" class="progress-bar-fill"></div>
                    </div>
                    <div id="syncStatusText" class="sync-status-msg">Preparando sincronização...</div>
                </div>
                <div style="text-align: right;">
                    <button id="btnFinishSync" class="btn-finish-sync" style="display: none;" onclick="closeSyncProgressModal()">
                        Concluído
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let allCategories = [];
        let currentCategory = null;
        let currentTournamentId = null;
        let currentTournamentName = '';
        let currentSeasonId = null;
        let cachedTournamentSeasons = {};

        const starFilledSvg = `<svg class="svg-icon" style="fill: var(--amber-gold);" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>`;
        const starOutlineSvg = `<svg class="svg-icon" viewBox="0 0 24 24"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/></svg>`;

        document.addEventListener('DOMContentLoaded', () => {
            fetchCategories();
        });

        // 1. Busca e exibe a lista de países
        async function fetchCategories() {
            const container = document.getElementById('countriesContainer');
            try {
                const response = await fetch('api.php?action=get_categories');
                const result = await response.json();
                
                if (result.success && Array.isArray(result.data)) {
                    allCategories = result.data;
                    renderCountries(allCategories);
                } else {
                    container.innerHTML = `<div class="loading-spinner" style="color: var(--live-red);">Erro ao carregar lista de países.</div>`;
                }
            } catch (err) {
                container.innerHTML = `<div class="loading-spinner" style="color: var(--live-red);">Falha de conexão ao carregar países.</div>`;
            }
        }

        function renderCountries(categories) {
            const container = document.getElementById('countriesContainer');
            if (categories.length === 0) {
                container.innerHTML = `<div class="loading-spinner">Nenhum país encontrado.</div>`;
                return;
            }

            container.innerHTML = categories.map(cat => {
                const emoji = cat.emoji || '⚽';
                return `
                    <div class="country-card" onclick="selectCountry(${cat.id}, '${escapeHtml(cat.name)}', '${emoji}')">
                        <div class="country-main">
                            <span class="country-emoji">${emoji}</span>
                            <div class="country-info">
                                <h3>${cat.name}</h3>
                                <span>Top 5 ligas masculinas</span>
                            </div>
                        </div>
                        <div class="country-arrow">→</div>
                    </div>
                `;
            }).join('');
        }

        // 2. Navegação entre visualização de países e de ligas
        function showCountriesView() {
            currentCategory = null;
            document.getElementById('countriesView').style.display = 'block';
            document.getElementById('leaguesView').style.display = 'none';
            document.getElementById('breadcrumbNav').innerHTML = `<span class="crumb-current">🌐 Países</span>`;
            document.getElementById('searchInput').placeholder = 'Buscar país...';
            document.getElementById('searchInput').value = '';
            renderCountries(allCategories);
        }

        async function selectCountry(categoryId, countryName, emoji) {
            currentCategory = { id: categoryId, name: countryName, emoji: emoji };
            
            document.getElementById('countriesView').style.display = 'none';
            document.getElementById('leaguesView').style.display = 'block';
            
            document.getElementById('breadcrumbNav').innerHTML = `
                <span class="crumb-link" onclick="showCountriesView()">🌐 Países</span>
                <span class="crumb-separator">/</span>
                <span class="crumb-current">${emoji} ${countryName}</span>
            `;

            document.getElementById('selectedCountryHeader').innerHTML = `
                <span>${emoji}</span>
                <span>${countryName}</span>
                <span class="info-pill" style="font-size: 0.8rem; font-weight: normal; margin-left: 0.5rem;">5 Principais Ligas Masculinas</span>
            `;

            document.getElementById('searchInput').placeholder = `Buscar liga em ${countryName}...`;
            document.getElementById('searchInput').value = '';

            fetchCountryLeagues(categoryId);
        }

        // 3. Busca e renderiza as 5 principais ligas masculinas do país
        async function fetchCountryLeagues(categoryId) {
            const container = document.getElementById('leaguesContainer');
            container.innerHTML = `<div class="loading-spinner">Buscando as 5 principais ligas masculinas...</div>`;

            try {
                const response = await fetch(`api.php?action=get_category_leagues&category_id=${categoryId}`);
                const result = await response.json();

                if (result.success && Array.isArray(result.data)) {
                    renderLeagues(result.data);
                } else {
                    container.innerHTML = `<div class="loading-spinner" style="color: var(--live-red);">Nenhuma liga disponível para este país.</div>`;
                }
            } catch (err) {
                container.innerHTML = `<div class="loading-spinner" style="color: var(--live-red);">Erro ao buscar ligas do país.</div>`;
            }
        }

        function renderLeagues(leagues) {
            const container = document.getElementById('leaguesContainer');
            if (leagues.length === 0) {
                container.innerHTML = `<div class="loading-spinner">Nenhuma liga encontrada.</div>`;
                return;
            }

            container.innerHTML = leagues.map(league => {
                const categoryName = currentCategory ? currentCategory.name : (league.category ? league.category.name : 'Futebol');
                const logoUrl = `api.php?action=get_image&type=tournament&id=${league.id}`;
                const isFav = league.is_favorite ? 'active' : '';
                const starSvg = league.is_favorite ? starFilledSvg : starOutlineSvg;
                
                return `
                    <div class="league-card" data-name="${league.name.toLowerCase()}">
                        <button class="btn-star-favorite ${isFav}" title="Favoritar Liga" onclick="toggleFavorite(event, ${league.id}, '${escapeHtml(league.name)}', '${escapeHtml(categoryName)}', '${logoUrl}', this)">
                            ${starSvg}
                        </button>
                        <div class="league-top">
                            <img class="league-logo" src="${logoUrl}" alt="${league.name}" onerror="this.onerror=null; this.style.opacity=0.3;">
                            <div class="league-info">
                                <span class="category-badge">${categoryName}</span>
                                <h3 class="league-name">${league.name}</h3>
                            </div>
                        </div>
                        <div class="league-actions">
                            <button class="btn-view-matches" onclick="openLeagueMatches(${league.id}, '${escapeHtml(league.name)}', '${logoUrl}')">
                                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
                                <span>Ver Partidas e Rodadas</span>
                            </button>
                            <button class="btn-quick-sync" onclick="syncThreeYears(${league.id}, '${escapeHtml(league.name)}')">
                                <span>⚡ Sincronizar 3 Anos (Anterior, Atual, Próximo)</span>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // 4. Favoritar / Desfavoritar liga
        async function toggleFavorite(event, tournamentId, name, categoryName, logoUrl, buttonEl) {
            event.stopPropagation();
            try {
                const formData = new FormData();
                formData.append('tournament_id', tournamentId);
                formData.append('name', name);
                formData.append('category_name', categoryName);
                formData.append('logo_url', logoUrl);

                const response = await fetch('api.php?action=toggle_favorite', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    if (result.data.is_favorite) {
                        buttonEl.classList.add('active');
                        buttonEl.innerHTML = starFilledSvg;
                    } else {
                        buttonEl.classList.remove('active');
                        buttonEl.innerHTML = starOutlineSvg;
                    }
                }
            } catch (err) {
                console.error('Erro ao favoritar:', err);
            }
        }

        // 5. Busca e Filtro Geral
        function onSearchInput() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            if (!currentCategory) {
                // Filtrando países
                const filtered = allCategories.filter(cat => 
                    cat.name.toLowerCase().includes(query) || 
                    (cat.slug && cat.slug.toLowerCase().includes(query))
                );
                renderCountries(filtered);
            } else {
                // Filtrando ligas do país selecionado
                const cards = document.querySelectorAll('#leaguesContainer .league-card');
                cards.forEach(card => {
                    const name = card.getAttribute('data-name') || '';
                    if (name.includes(query)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
        }

        // 6. Modal de Partidas
        async function openLeagueMatches(tournamentId, name, logoUrl) {
            currentTournamentId = tournamentId;
            currentTournamentName = name;
            document.getElementById('modalLeagueTitle').innerText = name;
            document.getElementById('modalLeagueLogo').src = logoUrl;
            document.getElementById('matchesModal').classList.add('active');
            
            const seasonSelect = document.getElementById('seasonSelector');
            const matchesContainer = document.getElementById('matchesContainer');
            seasonSelect.innerHTML = '<option>Carregando temporadas...</option>';
            matchesContainer.innerHTML = '<div class="loading-spinner">Buscando temporadas da liga...</div>';

            try {
                const sResponse = await fetch(`api.php?action=get_seasons&tournament_id=${tournamentId}`);
                const sResult = await sResponse.json();

                if (sResult.success) {
                    cachedTournamentSeasons[tournamentId] = {
                        all: sResult.data,
                        threeYears: sResult.three_years_data || sResult.data.slice(0, 3)
                    };
                    populateSeasonsDropdown();
                } else {
                    matchesContainer.innerHTML = '<div class="loading-spinner">Nenhuma temporada encontrada.</div>';
                }
            } catch (err) {
                matchesContainer.innerHTML = '<div class="loading-spinner" style="color: var(--live-red);">Erro ao buscar temporadas.</div>';
            }
        }

        function populateSeasonsDropdown() {
            const cached = cachedTournamentSeasons[currentTournamentId];
            if (!cached) return;

            const only3Years = document.getElementById('chkThreeYearsOnly').checked;
            const seasons = only3Years ? cached.threeYears : cached.all;
            const seasonSelect = document.getElementById('seasonSelector');

            if (seasons && seasons.length > 0) {
                seasonSelect.innerHTML = seasons.map(s => `
                    <option value="${s.id}">${s.name} (${s.year || ''})</option>
                `).join('');

                currentSeasonId = seasons[0].id;
                fetchRoundsAndMatches(currentTournamentId, currentSeasonId);
            } else {
                seasonSelect.innerHTML = '<option>Nenhuma temporada</option>';
            }
        }

        function toggleThreeYearsModalFilter() {
            populateSeasonsDropdown();
        }

        async function fetchRoundsAndMatches(tournamentId, seasonId) {
            const roundSelect = document.getElementById('roundSelector');
            roundSelect.innerHTML = '<option value="all">Carregando rodadas...</option>';

            try {
                const rResp = await fetch(`api.php?action=get_rounds&tournament_id=${tournamentId}&season_id=${seasonId}`);
                const rRes = await rResp.json();

                if (rRes.success && rRes.data && rRes.data.rounds) {
                    let roundsHtml = '<option value="all">Todas as Rodadas</option>';
                    rRes.data.rounds.forEach(r => {
                        roundsHtml += `<option value="${r.round}">Rodada ${r.round}</option>`;
                    });
                    roundSelect.innerHTML = roundsHtml;
                } else {
                    roundSelect.innerHTML = '<option value="all">Todas as Rodadas</option>';
                }
            } catch(e) {
                roundSelect.innerHTML = '<option value="all">Todas as Rodadas</option>';
            }

            fetchMatches(tournamentId, seasonId, 'all');
        }

        async function fetchMatches(tournamentId, seasonId, round = 'all') {
            const matchesContainer = document.getElementById('matchesContainer');
            document.getElementById('matchesCountBadge').innerText = 'Carregando...';
            matchesContainer.innerHTML = '<div class="loading-spinner">Carregando partidas da Sofascore API...</div>';

            try {
                let url = `api.php?action=get_matches&tournament_id=${tournamentId}&season_id=${seasonId}`;
                if (round !== 'all') {
                    url += `&round=${round}`;
                }

                const response = await fetch(url);
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    renderMatches(result.data);
                } else {
                    matchesContainer.innerHTML = '<div class="loading-spinner">Nenhuma partida encontrada para este filtro.</div>';
                    document.getElementById('matchesCountBadge').innerText = '0 jogos';
                }
            } catch (err) {
                matchesContainer.innerHTML = '<div class="loading-spinner" style="color: var(--live-red);">Erro ao carregar partidas.</div>';
                document.getElementById('matchesCountBadge').innerText = 'Erro';
            }
        }

        function renderMatches(events) {
            const matchesContainer = document.getElementById('matchesContainer');
            document.getElementById('matchesCountBadge').innerText = `${events.length} jogos`;

            matchesContainer.innerHTML = events.map(evt => {
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
                        <div style="text-align: right; font-size: 0.8rem; color: var(--text-muted);">
                            ${evt.venue ? evt.venue.stadium?.name || '' : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function onSeasonChange() {
            currentSeasonId = document.getElementById('seasonSelector').value;
            if (currentTournamentId && currentSeasonId) {
                fetchRoundsAndMatches(currentTournamentId, currentSeasonId);
            }
        }

        function onRoundChange() {
            const round = document.getElementById('roundSelector').value;
            if (currentTournamentId && currentSeasonId) {
                fetchMatches(currentTournamentId, currentSeasonId, round);
            }
        }

        function filterModalMatches() {
            const query = document.getElementById('matchSearchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.match-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const teams = card.getAttribute('data-teams');
                if (teams.includes(query)) {
                    card.style.display = 'grid';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            document.getElementById('matchesCountBadge').innerText = `${visibleCount} jogos`;
        }

        function closeModal() {
            document.getElementById('matchesModal').classList.remove('active');
        }

        // 7. Sincronização dos 3 Anos (Anterior, Atual e Próximo)
        function syncModalLeagueThreeYears() {
            if (currentTournamentId && currentTournamentName) {
                syncThreeYears(currentTournamentId, currentTournamentName);
            }
        }

        async function syncThreeYears(tournamentId, leagueName) {
            const progressModal = document.getElementById('syncProgressModal');
            const modalTitle = document.getElementById('syncProgressTitle');
            const progressFill = document.getElementById('syncProgressBarFill');
            const statusText = document.getElementById('syncStatusText');
            const btnFinish = document.getElementById('btnFinishSync');

            modalTitle.innerText = `⚡ Sincronizar: ${leagueName} (3 Anos)`;
            progressFill.style.width = '0%';
            statusText.innerText = 'Buscando temporadas recentes (ano anterior, atual e próximo)...';
            btnFinish.style.display = 'none';
            progressModal.classList.add('active');

            try {
                // Busca as 3 temporadas
                const sResp = await fetch(`api.php?action=get_seasons&tournament_id=${tournamentId}&three_years=1`);
                const sRes = await sResp.json();

                if (!sRes.success || !sRes.data || sRes.data.length === 0) {
                    statusText.innerText = 'Nenhuma temporada recente encontrada para esta liga.';
                    btnFinish.style.display = 'inline-block';
                    return;
                }

                const seasons = sRes.data;
                const totalSeasons = seasons.length;
                let totalMatchesSynced = 0;
                let totalMatchesSkipped = 0;

                for (let sIdx = 0; sIdx < totalSeasons; sIdx++) {
                    const season = seasons[sIdx];
                    const seasonProgressBase = (sIdx / totalSeasons) * 100;
                    statusText.innerText = `[Temporada ${sIdx + 1}/${totalSeasons}] Buscando partidas de ${season.name}...`;

                    const mResp = await fetch(`api.php?action=get_matches&tournament_id=${tournamentId}&season_id=${season.id}`);
                    const mRes = await mResp.json();

                    if (!mRes.success || !mRes.data || mRes.data.length === 0) {
                        continue;
                    }

                    const events = mRes.data;
                    const chunkSize = 25;
                    const totalEvents = events.length;

                    for (let i = 0; i < totalEvents; i += chunkSize) {
                        const chunk = events.slice(i, i + chunkSize);
                        const chunkProgress = (Math.min(i + chunkSize, totalEvents) / totalEvents) * (100 / totalSeasons);
                        const currentPercent = Math.round(seasonProgressBase + chunkProgress);
                        
                        progressFill.style.width = `${currentPercent}%`;
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
                                totalMatchesSynced += syncRes.data.synced || 0;
                                totalMatchesSkipped += syncRes.data.skipped || 0;
                            }
                        } catch (e) {
                            console.error('Erro no lote de sincronização:', e);
                        }
                    }
                }

                progressFill.style.width = '100%';
                statusText.innerHTML = `
                    <strong style="color: var(--success);">✔ Sincronização dos 3 anos concluída!</strong><br>
                    Temporadas: <strong>${seasons.map(s => s.name).join(', ')}</strong><br>
                    Partidas novas/atualizadas: <strong>${totalMatchesSynced}</strong> (já atualizadas: ${totalMatchesSkipped}).
                `;
                btnFinish.style.display = 'inline-block';

            } catch (err) {
                statusText.innerText = 'Erro durante a sincronização.';
                btnFinish.style.display = 'inline-block';
            }
        }

        function closeSyncProgressModal() {
            document.getElementById('syncProgressModal').classList.remove('active');
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/'/g, "\\'").replace(/"/g, "&quot;");
        }
    </script>
</body>
</html>
