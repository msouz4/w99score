<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>w99score - Análise Pré-Jogo & Estatísticas de Apostas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-body: #090d16;
            --bg-nav: rgba(15, 23, 42, 0.85);
            --card-bg: rgba(30, 41, 59, 0.6);
            --card-hover-bg: rgba(30, 41, 59, 0.95);
            --card-border: rgba(255, 255, 255, 0.08);
            --card-active-border: rgba(139, 92, 246, 0.5);
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
            padding: 0.85rem 2rem;
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
            gap: 0.75rem;
        }

        .nav-item {
            color: var(--text-muted);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .nav-item:hover,
        .nav-item.active {
            color: white;
            background: rgba(255, 255, 255, 0.06);
        }

        .analysis-layout {
            display: grid;
            grid-template-columns: 360px 1fr;
            height: calc(100vh - 65px);
            overflow: hidden;
        }

        .left-panel {
            background: rgba(15, 23, 42, 0.7);
            border-right: 1px solid var(--card-border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .panel-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--card-border);
            background: rgba(30, 41, 59, 0.4);
        }

        .panel-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-box-left {
            position: relative;
        }

        .search-box-left input {
            width: 100%;
            background: rgba(9, 13, 22, 0.8);
            border: 1px solid var(--card-border);
            border-radius: 8px;
            padding: 0.6rem 0.8rem 0.6rem 2.2rem;
            color: white;
            font-size: 0.85rem;
            font-family: inherit;
            outline: none;
        }

        .search-box-left .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .upcoming-list {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .upcoming-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .upcoming-card:hover {
            background: var(--card-hover-bg);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateX(3px);
        }

        .upcoming-card.active {
            background: rgba(139, 92, 246, 0.15);
            border-color: var(--accent-purple);
            box-shadow: 0 4px 14px var(--accent-glow);
        }

        .upcoming-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .tournament-badge {
            color: #a7f3d0;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .upcoming-matchup {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .mini-team {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.88rem;
            font-weight: 600;
            flex: 1;
        }

        .mini-team.home {
            justify-content: flex-end;
            text-align: right;
        }

        .mini-team.away {
            justify-content: flex-start;
            text-align: left;
        }

        .mini-logo {
            width: 22px;
            height: 22px;
            object-fit: contain;
        }

        .vs-badge {
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.05);
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
        }

        .right-panel {
            overflow-y: auto;
            padding: 1.5rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .match-hero-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 1.75rem 2rem;
            backdrop-filter: blur(16px);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .hero-team {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex: 1;
        }

        .hero-team.home {
            justify-content: flex-end;
            text-align: right;
        }

        .hero-team.away {
            justify-content: flex-start;
            text-align: left;
        }

        .hero-team.selectable {
            cursor: pointer;
            position: relative;
            padding: 0.75rem 1.25rem;
            border-radius: 16px;
            border: 2px solid transparent;
            transition: all 0.25s ease;
        }

        .hero-team.selectable:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(139, 92, 246, 0.4);
        }

        .hero-team.active-team {
            background: rgba(139, 92, 246, 0.18) !important;
            border-color: var(--accent-purple) !important;
            box-shadow: 0 0 20px var(--accent-glow);
        }

        .selected-team-badge {
            display: none;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            background: var(--accent-purple);
            color: white;
            margin-top: 0.35rem;
        }

        .hero-team.active-team .selected-team-badge {
            display: inline-block;
        }

        .active-team-banner {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 0.85rem 1.35rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.92rem;
            backdrop-filter: blur(12px);
        }

        .hero-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.03);
            padding: 8px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .hero-team-name {
            font-size: 1.35rem;
            font-weight: 800;
        }

        .hero-center {
            text-align: center;
            padding: 0 2rem;
        }

        .match-date-badge {
            font-size: 0.85rem;
            color: #c4b5fd;
            font-weight: 700;
            background: rgba(139, 92, 246, 0.15);
            padding: 0.35rem 0.9rem;
            border-radius: 9999px;
            border: 1px solid rgba(139, 92, 246, 0.3);
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .vs-text {
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
        }

        .tabs-header-scroll {
            display: flex;
            gap: 0.5rem;
            border-bottom: 1px solid var(--card-border);
            padding-bottom: 0.5rem;
            overflow-x: auto;
            white-space: nowrap;
        }

        .tab-btn {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            font-size: 0.88rem;
            font-weight: 700;
            font-family: inherit;
            padding: 0.65rem 1.1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .tab-btn:hover {
            color: white;
            background: rgba(255, 255, 255, 0.08);
        }

        .tab-btn.active {
            color: white;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            border-color: transparent;
            box-shadow: 0 4px 14px var(--accent-glow);
        }

        .tab-content {
            display: none;
            flex-direction: column;
            gap: 1.5rem;
        }

        .tab-content.active {
            display: flex;
        }

        .stats-period-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
        }

        .stat-box-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.25rem;
            backdrop-filter: blur(12px);
        }

        .stat-box-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .period-rows {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .period-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 0.5rem 0.85rem;
            font-size: 0.88rem;
        }

        .period-row-triple {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 0.65rem 0.9rem;
        }

        .period-row-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-muted);
            font-weight: 600;
        }

        .period-row-metrics {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.84rem;
            font-family: 'JetBrains Mono', monospace;
            gap: 0.5rem;
        }

        .val-feitos {
            color: #34d399;
            font-weight: 700;
        }

        .val-cedidos {
            color: #f87171;
            font-weight: 700;
        }

        .val-total {
            color: #c4b5fd;
            font-weight: 700;
        }

        .period-row-triple.clickable {
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .period-row-triple.clickable:hover {
            border-color: rgba(139, 92, 246, 0.6) !important;
            background: rgba(139, 92, 246, 0.15) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .chart-box-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.25rem;
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .chart-box-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chart-box-title {
            font-size: 0.92rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-canvas-container {
            position: relative;
            height: 220px;
            width: 100%;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(9, 13, 22, 0.85);
            backdrop-filter: blur(8px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            animation: modalFadeIn 0.2s ease;
        }

        .modal-card {
            background: #0f172a;
            border: 1px solid var(--card-border);
            border-radius: 20px;
            width: 100%;
            max-width: 880px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7);
            overflow: hidden;
        }

        .modal-header {
            padding: 1.25rem 1.75rem;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(30, 41, 59, 0.6);
        }

        .modal-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #f8fafc;
        }

        .modal-subtitle {
            font-size: 0.85rem;
            color: #c4b5fd;
            font-weight: 600;
        }

        .modal-close-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            font-size: 1.5rem;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .modal-close-btn:hover {
            color: white;
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.4);
        }

        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
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


    <div class="analysis-layout">
        <!-- Painel Esquerdo -->
        <div class="left-panel">
            <div class="panel-header">
                <h2 class="panel-title">
                    <svg class="svg-icon" viewBox="0 0 24 24">
                        <path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z" />
                    </svg>
                    Próximas Partidas <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500; margin-left: 0.25rem;">(Próximos 5 dias)</span>
                </h2>
                <div class="search-box-left">
                    <span class="search-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                        </svg>
                    </span>
                    <input type="text" id="leftSearchInput" placeholder="Filtrar por time ou liga..." oninput="filterUpcomingList()">
                </div>
            </div>

            <div class="upcoming-list" id="upcomingListContainer">
                <div class="loading-spinner">Buscando próximas partidas...</div>
            </div>
        </div>

        <!-- Painel Direito -->
        <div class="right-panel">
            <div id="noMatchSelectedNotice" class="loading-spinner" style="padding: 5rem 0;">
                Selecione uma partida no painel esquerdo para carregar a análise estatística pré-jogo.
            </div>

            <div id="matchAnalysisContainer" style="display: none; flex-direction: column; gap: 1.5rem;">
                <div class="match-hero-card">
                    <div class="hero-team home selectable" id="heroHomeTeamCard" onclick="selectFocusedTeam('home')" title="Clique para analisar as estatísticas deste time">
                        <div style="display: flex; flex-direction: column; align-items: flex-end;">
                            <span class="hero-team-name" id="heroHomeName">Casa</span>
                            <span class="selected-team-badge" id="heroHomeBadge">TIME SELECIONADO</span>
                        </div>
                        <img class="hero-logo" id="heroHomeLogo" src="" alt="" onerror="this.style.opacity=0.3">
                    </div>

                    <div class="hero-center">
                        <span class="match-date-badge" id="heroMatchDate">--/--/----</span>
                        <div class="vs-text">VS</div>
                        <span style="font-size: 0.8rem; color: var(--text-muted);" id="heroLeagueName">Liga</span>
                    </div>

                    <div class="hero-team away selectable" id="heroAwayTeamCard" onclick="selectFocusedTeam('away')" title="Clique para analisar as estatísticas deste time">
                        <img class="hero-logo" id="heroAwayLogo" src="" alt="" onerror="this.style.opacity=0.3">
                        <div style="display: flex; flex-direction: column; align-items: flex-start;">
                            <span class="hero-team-name" id="heroAwayName">Fora</span>
                            <span class="selected-team-badge" id="heroAwayBadge">TIME SELECIONADO</span>
                        </div>
                    </div>
                </div>

                <div class="active-team-banner" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        Analisando o Time: <strong id="lblActiveTeamName" style="color: #c4b5fd; font-size: 1rem;">--</strong> 
                        <span id="lblActiveTeamRole" style="color: var(--text-muted); font-size: 0.85rem; margin-left: 0.4rem;">(Mandante)</span>
                        <div style="font-size: 0.8rem; color: var(--text-muted); cursor: pointer; margin-top: 0.2rem;" onclick="toggleFocusedTeam()">Clique no outro time acima para alternar a análise</div>
                    </div>
                    <label style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); padding: 0.4rem 0.8rem; border-radius: 9999px; cursor: pointer; user-select: none; font-size: 0.82rem; font-weight: 600; color: #10b981;" title="Marque para aplicar limitação de jogos atípicos nas médias. Desmarque para ver médias simples sem tratamento.">
                        <input type="checkbox" id="chkRobustFilter" checked onchange="toggleRobustFilter(this.checked)" style="accent-color: #10b981; width: 15px; height: 15px; cursor: pointer;">
                        <span>🛡️ Proteção contra Outliers</span>
                    </label>
                </div>

                <!-- 4 ABAS UNIFICADAS -->
                <div class="tabs-header-scroll">
                    <button class="tab-btn active" onclick="switchTab('tabH2H', this)">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
                        </svg>
                        <span>1. Confronto Direto (H2H)</span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('tabOverall', this)">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z" />
                        </svg>
                        <span>2. Desempenho Geral</span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('tabHome', this)">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z" />
                        </svg>
                        <span>3. Jogando em Casa (Mandante)</span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('tabAway', this)">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z" />
                        </svg>
                        <span>4. Jogando Fora (Visitante)</span>
                    </button>
                </div>

                <!-- ABA 1: CONFRONTO DIRETO (H2H) -->
                <div class="tab-content active" id="tabH2H">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <h3 style="font-size: 1.1rem; font-weight: 700;">Estatísticas no Confronto Direto H2H</h3>
                        <span style="font-size: 0.8rem; color: var(--text-muted);">
                            O que <b class="lblFocusedTeamName" style="color: #93c5fd;">Time</b> <span class="val-feitos">FEZ</span> vs. <span class="val-cedidos">SOFREU</span> contra o adversário
                        </span>
                    </div>
                    <div id="gridH2HContainer"></div>
                </div>

                <!-- ABA 2: DESEMPENHO GERAL -->
                <div class="tab-content" id="tabOverall">
                    <h3 style="font-size: 1.1rem; font-weight: 700;">Desempenho Geral do <span class="lblFocusedTeamName">Time</span> (Todos os Jogos)</h3>
                    <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 0.5rem;">Médias de dados <span class="val-feitos">FEITOS</span> vs. <span class="val-cedidos">CEDIDOS</span> pelo <b class="lblFocusedTeamName" style="color: #93c5fd;">Time</b> nos jogos salvos no banco de dados.</p>
                    <div id="gridOverallContainer"></div>
                </div>

                <!-- ABA 3: TIME EM CASA (MANDANTE) -->
                <div class="tab-content" id="tabHome">
                    <h3 style="font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z" />
                        </svg>
                        <span>Desempenho do <span class="lblFocusedTeamName">Time</span> Jogando em Casa (Mandante)</span>
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 0.5rem;">Estatísticas de dados <span class="val-feitos">FEITOS</span> vs. <span class="val-cedidos">CEDIDOS</span> do <b class="lblFocusedTeamName" style="color: #93c5fd;">Time</b> atuando em seu estádio.</p>
                    <div id="gridHomeContainer"></div>
                </div>

                <!-- ABA 4: TIME FORA (VISITANTE) -->
                <div class="tab-content" id="tabAway">
                    <h3 style="font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z" />
                        </svg>
                        <span>Desempenho do <span class="lblFocusedTeamName">Time</span> Jogando Fora (Visitante)</span>
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 0.5rem;">Estatísticas de dados <span class="val-feitos">FEITOS</span> vs. <span class="val-cedidos">CEDIDOS</span> do <b class="lblFocusedTeamName" style="color: #93c5fd;">Time</b> atuando como visitante.</p>
                    <div id="gridAwayContainer"></div>
                </div>
            </div>
        </div>
    <!-- Modal de Gráficos de Estatísticas -->
    <div id="metricsChartModal" class="modal-overlay" style="display: none;" onclick="handleModalBackdropClick(event)">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" id="modalChartTitle">Gráficos dos Últimos Jogos</h3>
                    <span class="modal-subtitle" id="modalChartSubtitle">Estatística Selecionada</span>
                </div>
                <button class="modal-close-btn" onclick="closeChartModal()">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Gráfico 1: TOTAL -->
                <div class="chart-box-card">
                    <div class="chart-box-header">
                        <span class="chart-box-title" style="color: #c4b5fd;">
                            <span style="width: 10px; height: 10px; border-radius: 50%; background: #8b5cf6; display: inline-block;"></span>
                            1. Total por Partida
                        </span>
                        <span style="font-size: 0.78rem; color: var(--text-muted);" id="lblAvgTotal_modal">Média: --</span>
                    </div>
                    <div class="chart-canvas-container">
                        <canvas id="canvas_modal_total"></canvas>
                    </div>
                </div>

                <!-- Gráfico 2: FEITOS -->
                <div class="chart-box-card">
                    <div class="chart-box-header">
                        <span class="chart-box-title" style="color: #34d399;">
                            <span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                            2. Feitos pelo Time por Partida
                        </span>
                        <span style="font-size: 0.78rem; color: var(--text-muted);" id="lblAvgFeitos_modal">Média: --</span>
                    </div>
                    <div class="chart-canvas-container">
                        <canvas id="canvas_modal_feitos"></canvas>
                    </div>
                </div>

                <!-- Gráfico 3: CEDIDOS -->
                <div class="chart-box-card">
                    <div class="chart-box-header">
                        <span class="chart-box-title" style="color: #f87171;">
                            <span style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444; display: inline-block;"></span>
                            3. Cedidos/Sofridos por Partida
                        </span>
                        <span style="font-size: 0.78rem; color: var(--text-muted);" id="lblAvgCedidos_modal">Média: --</span>
                    </div>
                    <div class="chart-canvas-container">
                        <canvas id="canvas_modal_cedidos"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let upcomingMatches = [];
        let selectedEvent = null;
        let cachedTeamStats = null;
        let currentH2HMatches = [];
        let focusedTeamType = 'home'; // 'home' ou 'away'

        function formatBrasiliaTime(timestamp, includeDate = true) {
            if (!timestamp) return '';
            const d = new Date(timestamp * 1000);
            const options = {
                timeZone: 'America/Sao_Paulo',
                hour: '2-digit',
                minute: '2-digit'
            };
            if (includeDate) {
                options.day = '2-digit';
                options.month = '2-digit';
                options.year = '2-digit';
            }
            return d.toLocaleString('pt-BR', options);
        }

        document.addEventListener('DOMContentLoaded', () => {
            fetchUpcomingMatches();
        });

        async function fetchUpcomingMatches() {
            const container = document.getElementById('upcomingListContainer');
            const urlParams = new URLSearchParams(window.location.search);
            const targetEventId = urlParams.get('event_id');

            try {
                const response = await fetch('api.php?action=get_upcoming_matches');
                const result = await response.json();

                let matches = (result.success && result.data) ? result.data : [];

                if (targetEventId) {
                    let found = matches.find(m => (m.id == targetEventId || m.sofascore_event_id == targetEventId));
                    if (!found) {
                        try {
                            const singleRes = await fetch(`api.php?action=get_single_match&event_id=${targetEventId}`);
                            const singleData = await singleRes.json();
                            if (singleData.success && singleData.data) {
                                matches.unshift(singleData.data);
                                found = singleData.data;
                            }
                        } catch (e) {}
                    }
                    upcomingMatches = matches;
                    if (upcomingMatches.length > 0) {
                        renderUpcomingList(upcomingMatches);
                        if (found) {
                            selectMatch(found);
                            setTimeout(() => {
                                const cardEl = document.getElementById(`upcomingCard-${targetEventId}`);
                                if (cardEl) cardEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }, 100);
                        } else {
                            selectMatch(upcomingMatches[0]);
                        }
                    } else {
                        container.innerHTML = `<div class="loading-spinner">Nenhuma partida encontrada para os próximos 5 dias.</div>`;
                    }
                } else {
                    if (matches.length > 0) {
                        upcomingMatches = matches;
                        renderUpcomingList(upcomingMatches);
                        selectMatch(upcomingMatches[0]);
                    } else {
                        container.innerHTML = `<div class="loading-spinner">Nenhuma partida encontrada para os próximos 5 dias.</div>`;
                    }
                }
            } catch (err) {
                container.innerHTML = `<div class="loading-spinner" style="color: var(--live-red);">Erro ao carregar partidas.</div>`;
            }
        }

        function renderUpcomingList(matches) {
            const container = document.getElementById('upcomingListContainer');
            container.innerHTML = matches.map(m => {
                const homeName = m.homeTeam ? m.homeTeam.name : (m.home_team_name || 'Casa');
                const awayName = m.awayTeam ? m.awayTeam.name : (m.away_team_name || 'Fora');
                const homeId = m.homeTeam ? m.homeTeam.id : (m.home_team_id || 0);
                const awayId = m.awayTeam ? m.awayTeam.id : (m.away_team_id || 0);
                const homeLogo = homeId ? `api.php?action=get_image&type=team&id=${homeId}&v=2` : '';
                const awayLogo = awayId ? `api.php?action=get_image&type=team&id=${awayId}&v=2` : '';
                const leagueName = m.tournament ? m.tournament.name : (m.season_name || 'Futebol');

                const ts = m.startTimestamp || m.start_timestamp;
                const timeStr = ts ? formatBrasiliaTime(ts, true) : (m.match_date || '');
                const eventId = m.id || m.sofascore_event_id;

                return `
                    <div class="upcoming-card" id="upcomingCard-${eventId}" onclick="selectMatchById(${eventId})">
                        <div class="upcoming-meta">
                            <span class="tournament-badge">${escapeHtml(leagueName)}</span>
                            <span>${timeStr}</span>
                        </div>
                        <div class="upcoming-matchup">
                            <div class="mini-team home">
                                <span>${escapeHtml(homeName)}</span>
                                <img class="mini-logo" src="${homeLogo}" alt="" onerror="this.style.opacity=0.3">
                            </div>
                            <span class="vs-badge">VS</span>
                            <div class="mini-team away">
                                <img class="mini-logo" src="${awayLogo}" alt="" onerror="this.style.opacity=0.3">
                                <span>${escapeHtml(awayName)}</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function filterUpcomingList() {
            const query = document.getElementById('leftSearchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.upcoming-card');

            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                if (text.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function selectMatchById(eventId) {
            const match = upcomingMatches.find(m => (m.id == eventId || m.sofascore_event_id == eventId));
            if (match) selectMatch(match);
        }

        async function selectMatch(m) {
            selectedEvent = m;
            focusedTeamType = 'home'; // Seleciona o Time Mandante por padrão
            document.querySelectorAll('.upcoming-card').forEach(c => c.classList.remove('active'));
            const cardId = m.id || m.sofascore_event_id;
            const activeCard = document.getElementById(`upcomingCard-${cardId}`);
            if (activeCard) activeCard.classList.add('active');

            document.getElementById('noMatchSelectedNotice').style.display = 'none';
            document.getElementById('matchAnalysisContainer').style.display = 'flex';

            const homeName = m.homeTeam ? m.homeTeam.name : (m.home_team_name || 'Casa');
            const awayName = m.awayTeam ? m.awayTeam.name : (m.away_team_name || 'Fora');
            const homeId = m.homeTeam ? m.homeTeam.id : m.home_team_id;
            const awayId = m.awayTeam ? m.awayTeam.id : m.away_team_id;
            const homeLogo = `api.php?action=get_image&type=team&id=${homeId}&v=2`;
            const awayLogo = `api.php?action=get_image&type=team&id=${awayId}&v=2`;
            const leagueName = m.tournament ? m.tournament.name : (m.season_name || 'Futebol');

            const ts = m.startTimestamp || m.start_timestamp;
            const timeStr = ts ? formatBrasiliaTime(ts, true) : (m.match_date || '');

            document.getElementById('heroHomeName').innerText = homeName;
            document.getElementById('heroAwayName').innerText = awayName;
            document.getElementById('heroHomeLogo').src = homeLogo;
            document.getElementById('heroAwayLogo').src = awayLogo;
            document.getElementById('heroMatchDate').innerText = `${timeStr} (Horário de Brasília)`;
            document.getElementById('heroLeagueName').innerText = leagueName;

            updateFocusedTeamUI();

            loadH2HData(cardId, homeId, awayId);
            loadOverallTeamStats(homeId, awayId);
        }

        function selectFocusedTeam(type) {
            if (focusedTeamType === type) return;
            focusedTeamType = type;
            updateFocusedTeamUI();
            renderAllTabs();
        }

        function toggleFocusedTeam() {
            selectFocusedTeam(focusedTeamType === 'home' ? 'away' : 'home');
        }

        function updateFocusedTeamUI() {
            if (!selectedEvent) return;
            const homeName = selectedEvent.homeTeam ? selectedEvent.homeTeam.name : (selectedEvent.home_team_name || 'Casa');
            const awayName = selectedEvent.awayTeam ? selectedEvent.awayTeam.name : (selectedEvent.away_team_name || 'Fora');
            const isHome = (focusedTeamType === 'home');
            const focusedName = isHome ? homeName : awayName;

            document.getElementById('heroHomeTeamCard').classList.toggle('active-team', isHome);
            document.getElementById('heroAwayTeamCard').classList.toggle('active-team', !isHome);

            document.getElementById('lblActiveTeamName').innerText = focusedName;
            document.getElementById('lblActiveTeamRole').innerText = isHome ? '(Mandante no jogo atual)' : '(Visitante no jogo atual)';

            document.querySelectorAll('.lblFocusedTeamName').forEach(el => el.innerText = focusedName);
        }

        async function loadH2HData(eventId, homeTeamId, awayTeamId) {
            const container = document.getElementById('gridH2HContainer');
            container.innerHTML = '<div class="loading-spinner">Buscando confrontos diretos passados...</div>';

            try {
                const resp = await fetch(`api.php?action=get_h2h_data&event_id=${eventId}&home_team_id=${homeTeamId}&away_team_id=${awayTeamId}`);
                const result = await resp.json();

                if (result.success) {
                    let dbMatches = result.db_h2h || [];
                    currentH2HMatches = dbMatches.filter(m => m.sofascore_event_id != eventId && m.status === 'finished');
                    renderH2HTab();
                } else {
                    container.innerHTML = '<div class="loading-spinner">Nenhum confronto anterior encerrado.</div>';
                }
            } catch (err) {
                container.innerHTML = '<div class="loading-spinner">Erro de conexão ao carregar H2H.</div>';
            }
        }

        let isRobustFilterActive = true;

        function toggleRobustFilter(isActive) {
            isRobustFilterActive = isActive;
            if (selectedEvent) {
                const homeId = selectedEvent.homeTeam ? selectedEvent.homeTeam.id : selectedEvent.home_team_id;
                const awayId = selectedEvent.awayTeam ? selectedEvent.awayTeam.id : selectedEvent.away_team_id;
                loadOverallTeamStats(homeId, awayId);
            }
        }

        async function loadOverallTeamStats(homeTeamId, awayTeamId) {
            try {
                const robustParam = isRobustFilterActive ? '1' : '0';
                const resp = await fetch(`api.php?action=get_team_stats&home_team_id=${homeTeamId}&away_team_id=${awayTeamId}&robust=${robustParam}`);
                const result = await resp.json();

                if (result.success) {
                    cachedTeamStats = result;
                    renderAllTabs();
                }
            } catch (err) {
                console.error("Erro ao carregar estatísticas dos times:", err);
            }
        }

        let modalChartInstances = { total: null, feitos: null, cedidos: null };

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeChartModal();
        });

        function closeChartModal() {
            const modal = document.getElementById('metricsChartModal');
            if (modal) modal.style.display = 'none';
        }

        function handleModalBackdropClick(e) {
            if (e.target && e.target.id === 'metricsChartModal') {
                closeChartModal();
            }
        }

        function openChartModal(categoryKey, periodKey, metricLabel, tabPrefix) {
            if (!selectedEvent) return;

            const homeId = selectedEvent.homeTeam ? selectedEvent.homeTeam.id : selectedEvent.home_team_id;
            const awayId = selectedEvent.awayTeam ? selectedEvent.awayTeam.id : selectedEvent.away_team_id;
            const homeName = selectedEvent.homeTeam ? selectedEvent.homeTeam.name : (selectedEvent.home_team_name || 'Casa');
            const awayName = selectedEvent.awayTeam ? selectedEvent.awayTeam.name : (selectedEvent.away_team_name || 'Fora');

            const focusedTeamId = (focusedTeamType === 'home') ? homeId : awayId;
            const focusedName = (focusedTeamType === 'home') ? homeName : awayName;

            let matchesList = [];
            let tabName = '';

            if (tabPrefix === 'h2h') {
                matchesList = currentH2HMatches || [];
                tabName = 'Confronto Direto H2H';
            } else if (cachedTeamStats) {
                const statsObj = (focusedTeamType === 'home') ? cachedTeamStats.home_stats : cachedTeamStats.away_stats;
                if (statsObj) {
                    if (tabPrefix === 'overall') {
                        matchesList = statsObj.overall?.matches || [];
                        tabName = 'Desempenho Geral';
                    } else if (tabPrefix === 'home') {
                        matchesList = statsObj.home?.matches || [];
                        tabName = 'Jogando em Casa';
                    } else if (tabPrefix === 'away') {
                        matchesList = statsObj.away?.matches || [];
                        tabName = 'Jogando Fora';
                    }
                }
            }

            document.getElementById('modalChartTitle').innerText = `Gráficos: ${metricLabel}`;
            document.getElementById('modalChartSubtitle').innerText = `Time: ${focusedName} (${tabName} - ${matchesList.length} jogos)`;

            document.getElementById('metricsChartModal').style.display = 'flex';
            setTimeout(() => {
                renderModalCharts(matchesList, focusedTeamId, categoryKey, periodKey, metricLabel);
            }, 50);
        }

        function getMatchMetricValue(m, focusedTeamId, category, period) {
            const isHome = (parseInt(m.home_team_id) === parseInt(focusedTeamId));
            let home_ht = 0, away_ht = 0, home_ft = 0, away_ft = 0;

            if (category === 'goals') {
                home_ht = parseInt(m.home_score_ht) || 0;
                away_ht = parseInt(m.away_score_ht) || 0;
                home_ft = parseInt(m.home_score_ft) || 0;
                away_ft = parseInt(m.away_score_ft) || 0;
            } else if (category === 'corners') {
                home_ht = parseInt(m.home_corners_ht) || 0;
                away_ht = parseInt(m.away_corners_ht) || 0;
                home_ft = parseInt(m.home_corners_ft) || 0;
                away_ft = parseInt(m.away_corners_ft) || 0;
            } else if (category === 'yellow_cards') {
                home_ht = parseInt(m.home_yellow_cards_ht) || 0;
                away_ht = parseInt(m.away_yellow_cards_ht) || 0;
                home_ft = parseInt(m.home_yellow_cards_ft) || 0;
                away_ft = parseInt(m.away_yellow_cards_ft) || 0;
            } else if (category === 'shots') {
                home_ht = parseInt(m.home_shots_ht ?? m.home_shots_on_target_ht) || 0;
                away_ht = parseInt(m.away_shots_ht ?? m.away_shots_on_target_ht) || 0;
                home_ft = parseInt(m.home_shots_ft ?? m.home_shots_on_target_ft) || 0;
                away_ft = parseInt(m.away_shots_ft ?? m.away_shots_on_target_ft) || 0;
            }

            const home_st = Math.max(0, home_ft - home_ht);
            const away_st = Math.max(0, away_ft - away_ht);

            let feitos = 0, cedidos = 0;
            if (period === 'ht') {
                feitos = isHome ? home_ht : away_ht;
                cedidos = isHome ? away_ht : home_ht;
            } else if (period === 'st') {
                feitos = isHome ? home_st : away_st;
                cedidos = isHome ? away_st : home_st;
            } else {
                feitos = isHome ? home_ft : away_ft;
                cedidos = isHome ? away_ft : home_ft;
            }

            return {
                feitos: feitos,
                cedidos: cedidos,
                total: feitos + cedidos
            };
        }

        function renderModalCharts(matchesList, focusedTeamId, categoryKey, periodKey, metricLabel) {
            if (modalChartInstances.total) modalChartInstances.total.destroy();
            if (modalChartInstances.feitos) modalChartInstances.feitos.destroy();
            if (modalChartInstances.cedidos) modalChartInstances.cedidos.destroy();
            modalChartInstances = { total: null, feitos: null, cedidos: null };

            if (!matchesList || matchesList.length === 0) return;

            const sortedMatches = matchesList.slice().sort((a, b) => {
                const tsA = a.start_timestamp || a.startTimestamp || 0;
                const tsB = b.start_timestamp || b.startTimestamp || 0;
                return tsA - tsB;
            });

            const labels = [];
            const fullMatchLabels = [];
            const totalVals = [];
            const feitosVals = [];
            const cedidosVals = [];

            sortedMatches.forEach(m => {
                const ts = m.start_timestamp || m.startTimestamp;
                const dateStr = ts ? formatBrasiliaTime(ts, false) : '';
                const shortDate = dateStr.split(' ')[0] || '';

                const homeName = m.home_team_name || 'Casa';
                const awayName = m.away_team_name || 'Fora';
                const isHome = (parseInt(m.home_team_id) === parseInt(focusedTeamId));
                const oppName = isHome ? awayName : homeName;

                labels.push(`${shortDate} (${isHome ? 'C' : 'F'}) vs ${oppName.substring(0, 10)}`);
                fullMatchLabels.push(`${homeName} ${m.home_score_ft ?? '-'} x ${m.away_score_ft ?? '-'} ${awayName} (${dateStr})`);

                const res = getMatchMetricValue(m, focusedTeamId, categoryKey, periodKey);
                totalVals.push(res.total);
                feitosVals.push(res.feitos);
                cedidosVals.push(res.cedidos);
            });

            const count = totalVals.length;
            if (count === 0) return;

            const avgTotal = (totalVals.reduce((a, b) => a + b, 0) / count).toFixed(2);
            const avgFeitos = (feitosVals.reduce((a, b) => a + b, 0) / count).toFixed(2);
            const avgCedidos = (cedidosVals.reduce((a, b) => a + b, 0) / count).toFixed(2);

            const lblT = document.getElementById('lblAvgTotal_modal');
            const lblF = document.getElementById('lblAvgFeitos_modal');
            const lblC = document.getElementById('lblAvgCedidos_modal');

            if (lblT) lblT.innerHTML = `Média: <strong style="color: #c4b5fd;">${avgTotal}</strong> por jogo`;
            if (lblF) lblF.innerHTML = `Média: <strong style="color: #34d399;">${avgFeitos}</strong> por jogo`;
            if (lblC) lblC.innerHTML = `Média: <strong style="color: #f87171;">${avgCedidos}</strong> por jogo`;

            const createSingleChart = (canvasId, titleLabel, dataValues, avgVal, barColor, hoverColor) => {
                const ctx = document.getElementById(canvasId);
                if (!ctx) return null;

                return new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Linha de Média',
                                data: Array(count).fill(avgVal),
                                type: 'line',
                                borderColor: '#f59e0b',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                pointRadius: 0,
                                fill: false,
                                order: 1
                            },
                            {
                                label: titleLabel,
                                data: dataValues,
                                backgroundColor: barColor,
                                hoverBackgroundColor: hoverColor,
                                borderRadius: 6,
                                order: 2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                labels: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 11 } }
                            },
                            tooltip: {
                                callbacks: {
                                    title: function(context) {
                                        const idx = context[0].dataIndex;
                                        return fullMatchLabels[idx] || context[0].label;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 10 } },
                                grid: { color: 'rgba(255, 255, 255, 0.05)' }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { color: '#94a3b8', font: { family: 'JetBrains Mono', size: 10 }, stepSize: 1 },
                                grid: { color: 'rgba(255, 255, 255, 0.05)' }
                            }
                        }
                    }
                });
            };

            modalChartInstances.total = createSingleChart('canvas_modal_total', `Total (${metricLabel})`, totalVals, avgTotal, 'rgba(139, 92, 246, 0.75)', '#a78bfa');
            modalChartInstances.feitos = createSingleChart('canvas_modal_feitos', `Feitos (${metricLabel})`, feitosVals, avgFeitos, 'rgba(16, 185, 129, 0.75)', '#34d399');
            modalChartInstances.cedidos = createSingleChart('canvas_modal_cedidos', `Cedidos (${metricLabel})`, cedidosVals, avgCedidos, 'rgba(239, 68, 68, 0.75)', '#f87171');
        }

        function renderAllTabs() {
            renderH2HTab();
            renderTeamVenueTabs();
        }

        function renderH2HTab() {
            const container = document.getElementById('gridH2HContainer');
            if (!selectedEvent) return;

            const homeId = selectedEvent.homeTeam ? selectedEvent.homeTeam.id : selectedEvent.home_team_id;
            const awayId = selectedEvent.awayTeam ? selectedEvent.awayTeam.id : selectedEvent.away_team_id;
            const focusedTeamId = (focusedTeamType === 'home') ? homeId : awayId;

            if (!currentH2HMatches || currentH2HMatches.length === 0) {
                container.innerHTML = '<div class="loading-spinner">Nenhum confronto anterior encerrado entre essas equipes gravado no banco de dados.</div>';
                return;
            }

            const h2hStats = calculateH2HStatsForTeam(currentH2HMatches, focusedTeamId);
            container.innerHTML = renderTeamStatsGrid(h2hStats, currentH2HMatches, focusedTeamId, 'h2h');
        }

        function calculateH2HStatsForTeam(matches, focusedTeamId) {
            const count = matches.length;
            if (count === 0) return null;

            let gHtF = 0, gStF = 0, gFtF = 0, gHtC = 0, gStC = 0, gFtC = 0;
            let cHtF = 0, cStF = 0, cFtF = 0, cHtC = 0, cStC = 0, cFtC = 0;
            let yHtF = 0, yStF = 0, yFtF = 0, yHtC = 0, yStC = 0, yFtC = 0;
            let sHtF = 0, sStF = 0, sFtF = 0, sHtC = 0, sStC = 0, sFtC = 0;

            matches.forEach(m => {
                const isHomeInMatch = (parseInt(m.home_team_id) === parseInt(focusedTeamId));

                // Gols
                const gHt_F = isHomeInMatch ? (m.home_score_ht || 0) : (m.away_score_ht || 0);
                const gFt_F = isHomeInMatch ? (m.home_score_ft || 0) : (m.away_score_ft || 0);
                const gSt_F = Math.max(0, gFt_F - gHt_F);

                const gHt_C = isHomeInMatch ? (m.away_score_ht || 0) : (m.home_score_ht || 0);
                const gFt_C = isHomeInMatch ? (m.away_score_ft || 0) : (m.home_score_ft || 0);
                const gSt_C = Math.max(0, gFt_C - gHt_C);

                gHtF += gHt_F; gStF += gSt_F; gFtF += gFt_F;
                gHtC += gHt_C; gStC += gSt_C; gFtC += gFt_C;

                // Escanteios
                const cHt_F = isHomeInMatch ? (m.home_corners_ht || 0) : (m.away_corners_ht || 0);
                const cFt_F = isHomeInMatch ? (m.home_corners_ft || 0) : (m.away_corners_ft || 0);
                const cSt_F = Math.max(0, cFt_F - cHt_F);

                const cHt_C = isHomeInMatch ? (m.away_corners_ht || 0) : (m.home_corners_ht || 0);
                const cFt_C = isHomeInMatch ? (m.away_corners_ft || 0) : (m.home_corners_ft || 0);
                const cSt_C = Math.max(0, cFt_C - cHt_C);

                cHtF += cHt_F; cStF += cSt_F; cFtF += cFt_F;
                cHtC += cHt_C; cStC += cSt_C; cFtC += cFt_C;

                // Cartões Amarelos
                const yHt_F = isHomeInMatch ? (m.home_yellow_cards_ht || 0) : (m.away_yellow_cards_ht || 0);
                const yFt_F = isHomeInMatch ? (m.home_yellow_cards_ft || 0) : (m.away_yellow_cards_ft || 0);
                const ySt_F = Math.max(0, yFt_F - yHt_F);

                const yHt_C = isHomeInMatch ? (m.away_yellow_cards_ht || 0) : (m.home_yellow_cards_ht || 0);
                const yFt_C = isHomeInMatch ? (m.away_yellow_cards_ft || 0) : (m.home_yellow_cards_ft || 0);
                const ySt_C = Math.max(0, yFt_C - yHt_C);

                yHtF += yHt_F; yStF += ySt_F; yFtF += yFt_F;
                yHtC += yHt_C; yStC += ySt_C; yFtC += yFt_C;

                // Finalizações (Total Shots)
                const sHt_F = isHomeInMatch ? (m.home_shots_ht ?? m.home_shots_on_target_ht ?? 0) : (m.away_shots_ht ?? m.away_shots_on_target_ht ?? 0);
                const sFt_F = isHomeInMatch ? (m.home_shots_ft ?? m.home_shots_on_target_ft ?? 0) : (m.away_shots_ft ?? m.away_shots_on_target_ft ?? 0);
                const sSt_F = Math.max(0, sFt_F - sHt_F);

                const sHt_C = isHomeInMatch ? (m.away_shots_ht ?? m.away_shots_on_target_ht ?? 0) : (m.home_shots_ht ?? m.home_shots_on_target_ht ?? 0);
                const sFt_C = isHomeInMatch ? (m.away_shots_ft ?? m.away_shots_on_target_ft ?? 0) : (m.home_shots_ft ?? m.home_shots_on_target_ft ?? 0);
                const sSt_C = Math.max(0, sFt_C - sHt_C);

                sHtF += sHt_F; sStF += sSt_F; sFtF += sFt_F;
                sHtC += sHt_C; sStC += sSt_C; sFtC += sFt_C;
            });

            const buildCat = (hF, sF, fF, hC, sC, fC) => ({
                feitos: { avg_ht: (hF/count).toFixed(2), avg_st: (sF/count).toFixed(2), avg_ft: (fF/count).toFixed(2) },
                cedidos: { avg_ht: (hC/count).toFixed(2), avg_st: (sC/count).toFixed(2), avg_ft: (fC/count).toFixed(2) },
                total: { avg_ht: ((hF+hC)/count).toFixed(2), avg_st: ((sF+sC)/count).toFixed(2), avg_ft: ((fF+fC)/count).toFixed(2) }
            });

            return {
                goals: buildCat(gHtF, gStF, gFtF, gHtC, gStC, gFtC),
                corners: buildCat(cHtF, cStF, cFtF, cHtC, cStC, cFtC),
                yellow_cards: buildCat(yHtF, yStF, yFtF, yHtC, yStC, yFtC),
                shots: buildCat(sHtF, sStF, sFtF, sHtC, sStC, sFtC),
                shots_on_target: buildCat(sHtF, sStF, sFtF, sHtC, sStC, sFtC)
            };
        }

        function renderTeamVenueTabs() {
            if (!cachedTeamStats || !selectedEvent) return;

            const homeId = selectedEvent.homeTeam ? selectedEvent.homeTeam.id : selectedEvent.home_team_id;
            const awayId = selectedEvent.awayTeam ? selectedEvent.awayTeam.id : selectedEvent.away_team_id;
            const focusedTeamId = (focusedTeamType === 'home') ? homeId : awayId;
            const statsObj = (focusedTeamType === 'home') ? cachedTeamStats.home_stats : cachedTeamStats.away_stats;

            if (!statsObj) return;

            // Aba 2: Geral
            const overallMatches = statsObj.overall?.matches || [];
            document.getElementById('gridOverallContainer').innerHTML = renderTeamStatsGrid(statsObj.overall, overallMatches, focusedTeamId, 'overall');

            // Aba 3: Em Casa (Mandante)
            const homeMatches = statsObj.home?.matches || [];
            document.getElementById('gridHomeContainer').innerHTML = renderTeamStatsGrid(statsObj.home, homeMatches, focusedTeamId, 'home');

            // Aba 4: Fora (Visitante)
            const awayMatches = statsObj.away?.matches || [];
            document.getElementById('gridAwayContainer').innerHTML = renderTeamStatsGrid(statsObj.away, awayMatches, focusedTeamId, 'away');
        }

        function renderTeamStatsGrid(teamStats, matchesList, focusedTeamId, tabPrefix) {
            if (!teamStats) {
                return '<div class="loading-spinner">Sem dados estatísticos disponíveis.</div>';
            }

            const goalsHtml = renderStatBoxCard('Gols (Média)', 'goals', `
                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
            `, teamStats.goals, tabPrefix);

            const cornersHtml = renderStatBoxCard('Escanteios (Média)', 'corners', `
                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"/></svg>
            `, teamStats.corners, tabPrefix);

            const yellowHtml = renderStatBoxCard('Cartões Amarelos', 'yellow_cards', `
                <svg class="svg-icon" style="fill: var(--amber-gold);" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
            `, teamStats.yellow_cards, tabPrefix);

            const shotsHtml = renderStatBoxCard('Finalizações (Média)', 'shots', `
                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
            `, teamStats.shots || teamStats.shots_on_target, tabPrefix);

            const matchesHtml = renderMatchesListForTeam(matchesList, focusedTeamId);

            return `
                <div class="stats-period-grid">
                    ${goalsHtml}
                    ${cornersHtml}
                    ${yellowHtml}
                    ${shotsHtml}
                </div>

                <div style="margin-top: 1.25rem;">
                    <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.75rem;">Jogos que compõem este desempenho (${matchesList ? matchesList.length : 0} partidas)</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                        ${matchesHtml}
                    </div>
                </div>
            `;
        }

        function renderStatBoxCard(title, categoryKey, iconSvg, catData, tabPrefix) {
            if (!catData || (!catData.feitos && !catData.avg_ft)) {
                catData = {
                    feitos: { avg_ht: '-', avg_st: '-', avg_ft: '-' },
                    cedidos: { avg_ht: '-', avg_st: '-', avg_ft: '-' },
                    total: { avg_ht: '-', avg_st: '-', avg_ft: '-' }
                };
            }
            const f = catData.feitos || { avg_ht: '-', avg_st: '-', avg_ft: '-' };
            const c = catData.cedidos || { avg_ht: '-', avg_st: '-', avg_ft: '-' };
            const t = catData.total || { avg_ht: '-', avg_st: '-', avg_ft: '-' };

            const cleanTitle = title.replace(/\s*\(Média\)/, '');

            return `
                <div class="stat-box-card">
                    <div class="stat-box-title">
                        ${iconSvg}
                        <span>${title}</span>
                    </div>
                    <div class="period-rows">
                        <!-- 1º Tempo HT -->
                        <div class="period-row-triple clickable"
                             onclick="openChartModal('${categoryKey}', 'ht', '${cleanTitle} - 1º Tempo (HT)', '${tabPrefix}')"
                             title="Clique para abrir os 3 gráficos desta estatística">
                            <div class="period-row-header">
                                <span>1º Tempo (HT)</span>
                                <span style="font-size: 0.7rem; color: var(--accent-purple); font-weight: 600;">📊 Ver Gráficos</span>
                            </div>
                            <div class="period-row-metrics">
                                <span class="val-feitos">Feitos: ${f.avg_ht ?? f.ht}</span>
                                <span class="val-cedidos">Cedidos: ${c.avg_ht ?? c.ht}</span>
                                <span class="val-total">(Total: ${t.avg_ht ?? t.ht})</span>
                            </div>
                        </div>

                        <!-- 2º Tempo 2ºT -->
                        <div class="period-row-triple clickable"
                             onclick="openChartModal('${categoryKey}', 'st', '${cleanTitle} - 2º Tempo (2ºT)', '${tabPrefix}')"
                             title="Clique para abrir os 3 gráficos desta estatística">
                            <div class="period-row-header">
                                <span>2º Tempo (2ºT)</span>
                                <span style="font-size: 0.7rem; color: var(--accent-purple); font-weight: 600;">📊 Ver Gráficos</span>
                            </div>
                            <div class="period-row-metrics">
                                <span class="val-feitos">Feitos: ${f.avg_st ?? f.st}</span>
                                <span class="val-cedidos">Cedidos: ${c.avg_st ?? c.st}</span>
                                <span class="val-total">(Total: ${t.avg_st ?? t.st})</span>
                            </div>
                        </div>

                        <!-- Total FT -->
                        <div class="period-row-triple clickable" style="border-color: rgba(139, 92, 246, 0.4); background: rgba(139, 92, 246, 0.08);"
                             onclick="openChartModal('${categoryKey}', 'ft', '${cleanTitle} - Total da Partida (FT)', '${tabPrefix}')"
                             title="Clique para abrir os 3 gráficos desta estatística">
                            <div class="period-row-header">
                                <span style="color: white; font-weight: 700;">Total da Partida (FT)</span>
                                <span style="font-size: 0.7rem; color: #c4b5fd; font-weight: 600;">📊 Ver Gráficos</span>
                            </div>
                            <div class="period-row-metrics">
                                <span class="val-feitos">Feitos: ${f.avg_ft ?? f.ft}</span>
                                <span class="val-cedidos">Cedidos: ${c.avg_ft ?? c.ft}</span>
                                <span class="val-total">(Total: ${t.avg_ft ?? t.ft})</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderTabCharts(tabPrefix, matchesList, focusedTeamId) {
            if (!matchesList || matchesList.length === 0) return;

            if (activeChartInstances[tabPrefix]) {
                if (activeChartInstances[tabPrefix].total) activeChartInstances[tabPrefix].total.destroy();
                if (activeChartInstances[tabPrefix].feitos) activeChartInstances[tabPrefix].feitos.destroy();
                if (activeChartInstances[tabPrefix].cedidos) activeChartInstances[tabPrefix].cedidos.destroy();
            }
            activeChartInstances[tabPrefix] = { total: null, feitos: null, cedidos: null };

            const sortedMatches = matchesList.slice().sort((a, b) => {
                const tsA = a.start_timestamp || a.startTimestamp || 0;
                const tsB = b.start_timestamp || b.startTimestamp || 0;
                return tsA - tsB;
            });

            const labels = [];
            const fullMatchLabels = [];
            const totalVals = [];
            const feitosVals = [];
            const cedidosVals = [];

            sortedMatches.forEach(m => {
                const ts = m.start_timestamp || m.startTimestamp;
                const dateStr = ts ? formatBrasiliaTime(ts, false) : '';
                const shortDate = dateStr.split(' ')[0] || '';

                const homeName = m.home_team_name || 'Casa';
                const awayName = m.away_team_name || 'Fora';
                const isHome = (parseInt(m.home_team_id) === parseInt(focusedTeamId));
                const oppName = isHome ? awayName : homeName;

                labels.push(`${shortDate} (${isHome ? 'C' : 'F'}) vs ${oppName.substring(0, 10)}`);
                fullMatchLabels.push(`${homeName} ${m.home_score_ft ?? '-'} x ${m.away_score_ft ?? '-'} ${awayName} (${dateStr})`);

                const res = getMatchMetricValue(m, focusedTeamId, selectedMetric.category, selectedMetric.period);
                totalVals.push(res.total);
                feitosVals.push(res.feitos);
                cedidosVals.push(res.cedidos);
            });

            const count = totalVals.length;
            if (count === 0) return;

            const avgTotal = (totalVals.reduce((a, b) => a + b, 0) / count).toFixed(2);
            const avgFeitos = (feitosVals.reduce((a, b) => a + b, 0) / count).toFixed(2);
            const avgCedidos = (cedidosVals.reduce((a, b) => a + b, 0) / count).toFixed(2);

            const lblT = document.getElementById(`lblAvgTotal_${tabPrefix}`);
            const lblF = document.getElementById(`lblAvgFeitos_${tabPrefix}`);
            const lblC = document.getElementById(`lblAvgCedidos_${tabPrefix}`);

            if (lblT) lblT.innerHTML = `Média: <strong style="color: #c4b5fd;">${avgTotal}</strong> por jogo`;
            if (lblF) lblF.innerHTML = `Média: <strong style="color: #34d399;">${avgFeitos}</strong> por jogo`;
            if (lblC) lblC.innerHTML = `Média: <strong style="color: #f87171;">${avgCedidos}</strong> por jogo`;

            const createSingleChart = (canvasId, titleLabel, dataValues, avgVal, barColor, hoverColor) => {
                const ctx = document.getElementById(canvasId);
                if (!ctx) return null;

                return new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Linha de Média',
                                data: Array(count).fill(avgVal),
                                type: 'line',
                                borderColor: '#f59e0b',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                pointRadius: 0,
                                fill: false,
                                order: 1
                            },
                            {
                                label: titleLabel,
                                data: dataValues,
                                backgroundColor: barColor,
                                hoverBackgroundColor: hoverColor,
                                borderRadius: 6,
                                order: 2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                labels: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 11 } }
                            },
                            tooltip: {
                                callbacks: {
                                    title: function(context) {
                                        const idx = context[0].dataIndex;
                                        return fullMatchLabels[idx] || context[0].label;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 10 } },
                                grid: { color: 'rgba(255, 255, 255, 0.05)' }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { color: '#94a3b8', font: { family: 'JetBrains Mono', size: 10 }, stepSize: 1 },
                                grid: { color: 'rgba(255, 255, 255, 0.05)' }
                            }
                        }
                    }
                });
            };

            activeChartInstances[tabPrefix].total = createSingleChart(`canvas_${tabPrefix}_total`, `Total (${selectedMetric.label})`, totalVals, avgTotal, 'rgba(139, 92, 246, 0.75)', '#a78bfa');
            activeChartInstances[tabPrefix].feitos = createSingleChart(`canvas_${tabPrefix}_feitos`, `Feitos (${selectedMetric.label})`, feitosVals, avgFeitos, 'rgba(16, 185, 129, 0.75)', '#34d399');
            activeChartInstances[tabPrefix].cedidos = createSingleChart(`canvas_${tabPrefix}_cedidos`, `Cedidos (${selectedMetric.label})`, cedidosVals, avgCedidos, 'rgba(239, 68, 68, 0.75)', '#f87171');
        }

        function renderMatchesListForTeam(matches, focusedTeamId) {
            if (!matches || matches.length === 0) {
                return '<div class="loading-spinner">Nenhuma partida gravada para este filtro.</div>';
            }

            return matches.map(m => {
                const ts = m.start_timestamp || m.startTimestamp;
                const dateStr = ts ? formatBrasiliaTime(ts, true) : (m.match_date || '');
                const isHome = (parseInt(m.home_team_id) === parseInt(focusedTeamId));

                const teamScore = isHome ? (m.home_score_ft ?? '-') : (m.away_score_ft ?? '-');
                const oppScore = isHome ? (m.away_score_ft ?? '-') : (m.home_score_ft ?? '-');

                const teamCorners = isHome ? (m.home_corners_ft ?? 0) : (m.away_corners_ft ?? 0);
                const oppCorners = isHome ? (m.away_corners_ft ?? 0) : (m.home_corners_ft ?? 0);

                const teamCards = isHome ? (m.home_yellow_cards_ft ?? 0) : (m.away_yellow_cards_ft ?? 0);
                const oppCards = isHome ? (m.away_yellow_cards_ft ?? 0) : (m.home_yellow_cards_ft ?? 0);

                const teamShots = isHome ? (m.home_shots_ft ?? m.home_shots_on_target_ft ?? '-') : (m.away_shots_ft ?? m.away_shots_on_target_ft ?? '-');
                const oppShots = isHome ? (m.away_shots_ft ?? m.away_shots_on_target_ft ?? '-') : (m.home_shots_ft ?? m.home_shots_on_target_ft ?? '-');

                return `
                    <div class="period-row" style="padding: 0.75rem 1rem; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap;">
                        <div>
                            <span><b>${escapeHtml(m.home_team_name)}</b> ${m.home_score_ft ?? '-'} : ${m.away_score_ft ?? '-'} <b>${escapeHtml(m.away_team_name)}</b></span>
                            <span style="color: var(--text-muted); font-size: 0.78rem; margin-left: 0.5rem;">(${isHome ? 'Mandante' : 'Visitante'})</span>
                        </div>
                        <div style="font-size: 0.8rem; font-family: 'JetBrains Mono', monospace; display: flex; align-items: center; gap: 0.75rem;">
                            <span class="val-feitos">Feitos: ${teamScore} Gols | ${teamCorners} Esc. | ${teamCards} Cart. | ${teamShots} Fin.</span>
                            <span class="val-cedidos">Cedidos: ${oppScore} Gols | ${oppCorners} Esc. | ${oppCards} Cart. | ${oppShots} Fin.</span>
                            <span style="color: var(--text-muted); font-size: 0.75rem;">(${dateStr})</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function switchTab(tabId, btnEl) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            btnEl.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/'/g, "\\'").replace(/"/g, "&quot;");
        }
    </script>
</body>

</html>