<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>w99score - Análise Pré-Jogo & Estatísticas de Apostas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        /* Navbar Header */
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

        .nav-item:hover, .nav-item.active {
            color: white;
            background: rgba(255, 255, 255, 0.06);
        }

        /* Layout Split 2 Painéis */
        .analysis-layout {
            display: grid;
            grid-template-columns: 360px 1fr;
            height: calc(100vh - 65px);
            overflow: hidden;
        }

        /* Painel Esquerdo (Lista de Partidas Futuras) */
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

        .mini-team.home { justify-content: flex-end; text-align: right; }
        .mini-team.away { justify-content: flex-start; text-align: left; }

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

        /* Painel Direito */
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

        .hero-team.home { justify-content: flex-end; text-align: right; }
        .hero-team.away { justify-content: flex-start; text-align: left; }

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

        /* 2 Abas Principais */
        .tabs-header {
            display: flex;
            gap: 1rem;
            border-bottom: 1px solid var(--card-border);
            padding-bottom: 0.5rem;
        }

        .tab-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1rem;
            font-weight: 700;
            font-family: inherit;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tab-btn:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
        }

        .tab-btn.active {
            color: white;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
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

        /* Tabela / Cards de Estatísticas por Período */
        .stats-period-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
            font-size: 0.85rem;
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
            font-family: 'JetBrains Mono', monospace;
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

        .period-label {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .period-values {
            display: flex;
            gap: 0.75rem;
            font-weight: 700;
        }

        .val-home { color: #93c5fd; }
        .val-away { color: #fca5a5; }
        .val-total { color: #a7f3d0; }

        .accordion-btn {
            width: 100%;
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid var(--card-border);
            color: var(--text-main);
            padding: 0.85rem 1.25rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1rem;
        }

        .accordion-btn:hover {
            background: rgba(30, 41, 59, 0.9);
        }

        .accordion-content {
            display: none;
            margin-top: 0.75rem;
            flex-direction: column;
            gap: 0.75rem;
        }

        .accordion-content.active {
            display: flex;
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
    <nav class="navbar">
        <a href="index.php" class="brand">
            <div class="brand-logo">W99</div>
            <span class="brand-title">w99score</span>
        </a>
        <div class="nav-links">
            <a href="index.php" class="nav-item">Dashboard</a>
            <a href="ligas.php" class="nav-item">
                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0011 15.9V19H7v2h10v-2h-4v-3.1c2.04-.4 3.61-2.01 3.99-4.06C19.39 11.45 21 9.4 21 7V6c0-1.1-.9-1-2-1zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>
                Ligas e Jogos
            </a>
            <a href="favoritos.php" class="nav-item">
                <svg class="svg-icon" style="fill: var(--amber-gold);" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                Ligas Favoritas
            </a>
            <a href="analise.php" class="nav-item active">
                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                Análise Pré-Jogo
            </a>
        </div>
    </nav>

    <!-- Layout Split 2 Painéis -->
    <div class="analysis-layout">
        <!-- Painel Esquerdo: Lista de Próximos Jogos -->
        <div class="left-panel">
            <div class="panel-header">
                <h2 class="panel-title">
                    <svg class="svg-icon" viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
                    Próximas Partidas (Horário de Brasília)
                </h2>
                <div class="search-box-left">
                    <span class="search-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    </span>
                    <input type="text" id="leftSearchInput" placeholder="Filtrar por time ou liga..." oninput="filterUpcomingList()">
                </div>
            </div>

            <div class="upcoming-list" id="upcomingListContainer">
                <div class="loading-spinner">Buscando próximas partidas...</div>
            </div>
        </div>

        <!-- Painel Direito: Análise Estatística Pré-Jogo -->
        <div class="right-panel">
            <div id="noMatchSelectedNotice" class="loading-spinner" style="padding: 5rem 0;">
                👈 Selecione uma partida no painel esquerdo para carregar a análise estatística pré-jogo.
            </div>

            <div id="matchAnalysisContainer" style="display: none; flex-direction: column; gap: 1.5rem;">
                <!-- Header do Confronto Selecionado -->
                <div class="match-hero-card">
                    <div class="hero-team home">
                        <span class="hero-team-name" id="heroHomeName">Casa</span>
                        <img class="hero-logo" id="heroHomeLogo" src="" alt="" onerror="this.style.opacity=0.3">
                    </div>

                    <div class="hero-center">
                        <span class="match-date-badge" id="heroMatchDate">--/--/----</span>
                        <div class="vs-text">VS</div>
                        <span style="font-size: 0.8rem; color: var(--text-muted);" id="heroLeagueName">Liga</span>
                    </div>

                    <div class="hero-team away">
                        <img class="hero-logo" id="heroAwayLogo" src="" alt="" onerror="this.style.opacity=0.3">
                        <span class="hero-team-name" id="heroAwayName">Fora</span>
                    </div>
                </div>

                <!-- 2 Abas de Seleção -->
                <div class="tabs-header">
                    <button class="tab-btn active" onclick="switchTab('tabH2H', this)">
                        <svg class="svg-icon" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                        <span>1. Confronto Direto (H2H)</span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('tabOverall', this)">
                        <svg class="svg-icon" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                        <span>2. Desempenho Geral dos 2 Times</span>
                    </button>
                </div>

                <!-- ABA 1: CONFRONTO DIRETO (H2H) -->
                <div class="tab-content active" id="tabH2H">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <h3 style="font-size: 1.1rem; font-weight: 700;">📊 Estatísticas dos Confrontos Diretos</h3>
                        <span style="font-size: 0.8rem; color: var(--text-muted);">
                            Valores por Time (<b id="h2hLegendHome" style="color: #93c5fd;">Casa</b> : <b id="h2hLegendAway" style="color: #fca5a5;">Fora</b>) e <b style="color: #a7f3d0;">Total Combinado</b>
                        </span>
                    </div>
                    
                    <div class="stats-period-grid">
                        <!-- Box Gols H2H -->
                        <div class="stat-box-card">
                            <div class="stat-box-title">
                                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
                                Gols no H2H
                            </div>
                            <div class="period-rows">
                                <div class="period-row">
                                    <span class="period-label">1º Tempo (HT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="h2hGoalsHtHome">-</span> : <span class="val-away" id="h2hGoalsHtAway">-</span>
                                        <span class="val-total" id="h2hGoalsHtTotal">(- Total)</span>
                                    </div>
                                </div>
                                <div class="period-row">
                                    <span class="period-label">2º Tempo (2ºT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="h2hGoalsStHome">-</span> : <span class="val-away" id="h2hGoalsStAway">-</span>
                                        <span class="val-total" id="h2hGoalsStTotal">(- Total)</span>
                                    </div>
                                </div>
                                <div class="period-row" style="border-color: rgba(139, 92, 246, 0.3);">
                                    <span class="period-label" style="color: white;">Total (FT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="h2hGoalsFtHome">-</span> : <span class="val-away" id="h2hGoalsFtAway">-</span>
                                        <span class="val-total" id="h2hGoalsFtTotal">(- Total)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Box Escanteios H2H -->
                        <div class="stat-box-card">
                            <div class="stat-box-title">
                                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"/></svg>
                                Escanteios no H2H
                            </div>
                            <div class="period-rows">
                                <div class="period-row">
                                    <span class="period-label">1º Tempo (HT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="h2hCornersHtHome">-</span> : <span class="val-away" id="h2hCornersHtAway">-</span>
                                        <span class="val-total" id="h2hCornersHtTotal">(- Total)</span>
                                    </div>
                                </div>
                                <div class="period-row">
                                    <span class="period-label">2º Tempo (2ºT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="h2hCornersStHome">-</span> : <span class="val-away" id="h2hCornersStAway">-</span>
                                        <span class="val-total" id="h2hCornersStTotal">(- Total)</span>
                                    </div>
                                </div>
                                <div class="period-row" style="border-color: rgba(139, 92, 246, 0.3);">
                                    <span class="period-label" style="color: white;">Total (FT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="h2hCornersFtHome">-</span> : <span class="val-away" id="h2hCornersFtAway">-</span>
                                        <span class="val-total" id="h2hCornersFtTotal">(- Total)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Box Cartões H2H -->
                        <div class="stat-box-card">
                            <div class="stat-box-title">
                                <svg class="svg-icon" style="fill: var(--amber-gold);" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                                Cartões Amarelos H2H
                            </div>
                            <div class="period-rows">
                                <div class="period-row">
                                    <span class="period-label">1º Tempo (HT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="h2hYellowHtHome">-</span> : <span class="val-away" id="h2hYellowHtAway">-</span>
                                        <span class="val-total" id="h2hYellowHtTotal">(- Total)</span>
                                    </div>
                                </div>
                                <div class="period-row">
                                    <span class="period-label">2º Tempo (2ºT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="h2hYellowStHome">-</span> : <span class="val-away" id="h2hYellowStAway">-</span>
                                        <span class="val-total" id="h2hYellowStTotal">(- Total)</span>
                                    </div>
                                </div>
                                <div class="period-row" style="border-color: rgba(139, 92, 246, 0.3);">
                                    <span class="period-label" style="color: white;">Total (FT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="h2hYellowFtHome">-</span> : <span class="val-away" id="h2hYellowFtAway">-</span>
                                        <span class="val-total" id="h2hYellowFtTotal">(- Total)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Box Chutes H2H -->
                        <div class="stat-box-card">
                            <div class="stat-box-title">
                                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                                Chutes a Gol H2H
                            </div>
                            <div class="period-rows">
                                <div class="period-row">
                                    <span class="period-label">1º Tempo (HT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="h2hShotsHtHome">-</span> : <span class="val-away" id="h2hShotsHtAway">-</span>
                                        <span class="val-total" id="h2hShotsHtTotal">(- Total)</span>
                                    </div>
                                </div>
                                <div class="period-row">
                                    <span class="period-label">2º Tempo (2ºT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="h2hShotsStHome">-</span> : <span class="val-away" id="h2hShotsStAway">-</span>
                                        <span class="val-total" id="h2hShotsStTotal">(- Total)</span>
                                    </div>
                                </div>
                                <div class="period-row" style="border-color: rgba(139, 92, 246, 0.3);">
                                    <span class="period-label" style="color: white;">Total (FT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="h2hShotsFtHome">-</span> : <span class="val-away" id="h2hShotsFtAway">-</span>
                                        <span class="val-total" id="h2hShotsFtTotal">(- Total)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4 style="font-size: 1rem; font-weight: 700; margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <svg class="svg-icon" viewBox="0 0 24 24"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
                        Histórico das Partidas Anteriores do Confronto Direto
                    </h4>
                    <div id="h2hMatchesList" style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <div class="loading-spinner">Carregando histórico do confronto direto...</div>
                    </div>
                </div>

                <!-- ABA 2: DESEMPENHO GERAL DOS 2 TIMES -->
                <div class="tab-content" id="tabOverall">
                    <h3 style="font-size: 1.1rem; font-weight: 700;">📈 Estatísticas Acumuladas & Médias por Tempo</h3>
                    <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 0.5rem;">Comparativo entre <b id="teamNameLegendHome" style="color: #93c5fd;">Time Casa</b> vs <b id="teamNameLegendAway" style="color: #fca5a5;">Time Fora</b> nos jogos salvos no banco de dados.</p>

                    <div class="stats-period-grid">
                        <!-- Gols Acumulados -->
                        <div class="stat-box-card">
                            <div class="stat-box-title">
                                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
                                Gols (Médias)
                            </div>
                            <div class="period-rows">
                                <div class="period-row">
                                    <span class="period-label">1º Tempo (HT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="ovGoalsHtHome">-</span> : <span class="val-away" id="ovGoalsHtAway">-</span>
                                    </div>
                                </div>
                                <div class="period-row">
                                    <span class="period-label">2º Tempo (2ºT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="ovGoalsStHome">-</span> : <span class="val-away" id="ovGoalsStAway">-</span>
                                    </div>
                                </div>
                                <div class="period-row" style="border-color: rgba(139, 92, 246, 0.3);">
                                    <span class="period-label" style="color: white;">Total (FT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="ovGoalsFtHome">-</span> : <span class="val-away" id="ovGoalsFtAway">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Escanteios Acumulados -->
                        <div class="stat-box-card">
                            <div class="stat-box-title">
                                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"/></svg>
                                Escanteios (Médias)
                            </div>
                            <div class="period-rows">
                                <div class="period-row">
                                    <span class="period-label">1º Tempo (HT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="ovCornersHtHome">-</span> : <span class="val-away" id="ovCornersHtAway">-</span>
                                    </div>
                                </div>
                                <div class="period-row">
                                    <span class="period-label">2º Tempo (2ºT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="ovCornersStHome">-</span> : <span class="val-away" id="ovCornersStAway">-</span>
                                    </div>
                                </div>
                                <div class="period-row" style="border-color: rgba(139, 92, 246, 0.3);">
                                    <span class="period-label" style="color: white;">Total (FT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="ovCornersFtHome">-</span> : <span class="val-away" id="ovCornersFtAway">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cartões Acumulados -->
                        <div class="stat-box-card">
                            <div class="stat-box-title">
                                <svg class="svg-icon" style="fill: var(--amber-gold);" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                                Cartões Amarelos
                            </div>
                            <div class="period-rows">
                                <div class="period-row">
                                    <span class="period-label">1º Tempo (HT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="ovYellowHtHome">-</span> : <span class="val-away" id="ovYellowHtAway">-</span>
                                    </div>
                                </div>
                                <div class="period-row">
                                    <span class="period-label">2º Tempo (2ºT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="ovYellowStHome">-</span> : <span class="val-away" id="ovYellowStAway">-</span>
                                    </div>
                                </div>
                                <div class="period-row" style="border-color: rgba(139, 92, 246, 0.3);">
                                    <span class="period-label" style="color: white;">Total (FT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="ovYellowFtHome">-</span> : <span class="val-away" id="ovYellowFtAway">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chutes a Gol Acumulados -->
                        <div class="stat-box-card">
                            <div class="stat-box-title">
                                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                                Chutes a Gol
                            </div>
                            <div class="period-rows">
                                <div class="period-row">
                                    <span class="period-label">1º Tempo (HT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="ovShotsHtHome">-</span> : <span class="val-away" id="ovShotsHtAway">-</span>
                                    </div>
                                </div>
                                <div class="period-row">
                                    <span class="period-label">2º Tempo (2ºT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="ovShotsStHome">-</span> : <span class="val-away" id="ovShotsStAway">-</span>
                                    </div>
                                </div>
                                <div class="period-row" style="border-color: rgba(139, 92, 246, 0.3);">
                                    <span class="period-label" style="color: white;">Total (FT)</span>
                                    <div class="period-values">
                                        <span class="val-home" id="ovShotsFtHome">-</span> : <span class="val-away" id="ovShotsFtAway">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acordeão Expansível de Detalhes -->
                    <button class="accordion-btn" onclick="toggleAccordion('accContent')">
                        <span style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg class="svg-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                            Ver Partidas Individuais que Compõem estes Totais
                        </span>
                        <span id="accIcon">▼</span>
                    </button>
                    <div class="accordion-content" id="accContent">
                        <div id="detailedMatchesContainer" style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <div class="loading-spinner">Selecione uma partida para carregar a composição...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let upcomingMatches = [];
        let selectedEvent = null;

        // Formata data e hora explicitamente no fuso de Brasília (America/Sao_Paulo)
        function formatBrasiliaTime(timestamp, includeDate = true) {
            if (!timestamp) return '';
            const d = new Date(timestamp * 1000);
            const options = { timeZone: 'America/Sao_Paulo', hour: '2-digit', minute: '2-digit' };
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
            try {
                const response = await fetch('api.php?action=get_upcoming_matches');
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    upcomingMatches = result.data;
                    renderUpcomingList(upcomingMatches);
                    selectMatch(upcomingMatches[0]);
                } else {
                    container.innerHTML = `<div class="loading-spinner">Nenhuma partida futura encontrada.</div>`;
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
                const homeLogo = m.homeTeam ? `https://api.sofascore.app/api/v1/team/${m.homeTeam.id}/image` : (m.home_team_logo || '');
                const awayLogo = m.awayTeam ? `https://api.sofascore.app/api/v1/team/${m.awayTeam.id}/image` : (m.away_team_logo || '');
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
            const homeLogo = `https://api.sofascore.app/api/v1/team/${homeId}/image`;
            const awayLogo = `https://api.sofascore.app/api/v1/team/${awayId}/image`;
            const leagueName = m.tournament ? m.tournament.name : (m.season_name || 'Futebol');

            const ts = m.startTimestamp || m.start_timestamp;
            const timeStr = ts ? formatBrasiliaTime(ts, true) : (m.match_date || '');

            document.getElementById('heroHomeName').innerText = homeName;
            document.getElementById('heroAwayName').innerText = awayName;
            document.getElementById('heroHomeLogo').src = homeLogo;
            document.getElementById('heroAwayLogo').src = awayLogo;
            document.getElementById('heroMatchDate').innerText = `${timeStr} (Horário de Brasília)`;
            document.getElementById('heroLeagueName').innerText = leagueName;

            document.getElementById('teamNameLegendHome').innerText = homeName;
            document.getElementById('teamNameLegendAway').innerText = awayName;
            document.getElementById('h2hLegendHome').innerText = homeName;
            document.getElementById('h2hLegendAway').innerText = awayName;

            loadH2HData(cardId, homeId, awayId);
            loadOverallTeamStats(homeId, awayId);
        }

        async function loadH2HData(eventId, homeTeamId, awayTeamId) {
            const container = document.getElementById('h2hMatchesList');
            container.innerHTML = '<div class="loading-spinner">Buscando confrontos diretos passados...</div>';

            try {
                const resp = await fetch(`api.php?action=get_h2h_data&event_id=${eventId}&home_team_id=${homeTeamId}&away_team_id=${awayTeamId}`);
                const result = await resp.json();

                if (result.success) {
                    let dbMatches = result.db_h2h || [];
                    dbMatches = dbMatches.filter(m => m.sofascore_event_id != eventId && m.status === 'finished');

                    calculateH2HAverages(dbMatches, homeTeamId, awayTeamId);
                    renderH2HMatchesList(dbMatches, result.api_h2h, eventId);
                } else {
                    container.innerHTML = '<div class="loading-spinner">Nenhum confronto anterior encerrado.</div>';
                }
            } catch (err) {
                container.innerHTML = '<div class="loading-spinner">Erro de conexão.</div>';
            }
        }

        function calculateH2HAverages(matches, homeTeamId, awayTeamId) {
            const count = matches.length;
            if (count === 0) {
                const reset = (id) => document.getElementById(id).innerText = '-';
                ['h2hGoalsHtHome','h2hGoalsHtAway','h2hGoalsHtTotal', 'h2hGoalsStHome','h2hGoalsStAway','h2hGoalsStTotal', 'h2hGoalsFtHome','h2hGoalsFtAway','h2hGoalsFtTotal',
                 'h2hCornersHtHome','h2hCornersHtAway','h2hCornersHtTotal', 'h2hCornersStHome','h2hCornersStAway','h2hCornersStTotal', 'h2hCornersFtHome','h2hCornersFtAway','h2hCornersFtTotal',
                 'h2hYellowHtHome','h2hYellowHtAway','h2hYellowHtTotal', 'h2hYellowStHome','h2hYellowStAway','h2hYellowStTotal', 'h2hYellowFtHome','h2hYellowFtAway','h2hYellowFtTotal',
                 'h2hShotsHtHome','h2hShotsHtAway','h2hShotsHtTotal', 'h2hShotsStHome','h2hShotsStAway','h2hShotsStTotal', 'h2hShotsFtHome','h2hShotsFtAway','h2hShotsFtTotal'].forEach(reset);
                return;
            }

            let gHtHome = 0, gHtAway = 0, gFtHome = 0, gFtAway = 0;
            let cHtHome = 0, cHtAway = 0, cFtHome = 0, cFtAway = 0;
            let yHtHome = 0, yHtAway = 0, yFtHome = 0, yFtAway = 0;
            let sHtHome = 0, sHtAway = 0, sFtHome = 0, sFtAway = 0;

            matches.forEach(m => {
                const isHomeInMatch = (parseInt(m.home_team_id) === parseInt(homeTeamId));

                const hGht = isHomeInMatch ? (m.home_score_ht || 0) : (m.away_score_ht || 0);
                const aGht = isHomeInMatch ? (m.away_score_ht || 0) : (m.home_score_ht || 0);
                const hGft = isHomeInMatch ? (m.home_score_ft || 0) : (m.away_score_ft || 0);
                const aGft = isHomeInMatch ? (m.away_score_ft || 0) : (m.home_score_ft || 0);
                gHtHome += hGht; gHtAway += aGht; gFtHome += hGft; gFtAway += aGft;

                const hCht = isHomeInMatch ? (m.home_corners_ht || 0) : (m.away_corners_ht || 0);
                const aCht = isHomeInMatch ? (m.away_corners_ht || 0) : (m.home_corners_ht || 0);
                const hCft = isHomeInMatch ? (m.home_corners_ft || 0) : (m.away_corners_ft || 0);
                const aCft = isHomeInMatch ? (m.away_corners_ft || 0) : (m.home_corners_ft || 0);
                cHtHome += hCht; cHtAway += aCht; cFtHome += hCft; cFtAway += aCft;

                const hYht = isHomeInMatch ? (m.home_yellow_cards_ht || 0) : (m.away_yellow_cards_ht || 0);
                const aYht = isHomeInMatch ? (m.away_yellow_cards_ht || 0) : (m.home_yellow_cards_ht || 0);
                const hYft = isHomeInMatch ? (m.home_yellow_cards_ft || 0) : (m.away_yellow_cards_ft || 0);
                const aYft = isHomeInMatch ? (m.away_yellow_cards_ft || 0) : (m.home_yellow_cards_ft || 0);
                yHtHome += hYht; yHtAway += aYht; yFtHome += hYft; yFtAway += aYft;

                const hSht = isHomeInMatch ? (m.home_shots_on_target_ht || 0) : (m.away_shots_on_target_ht || 0);
                const aSht = isHomeInMatch ? (m.away_shots_on_target_ht || 0) : (m.home_shots_on_target_ht || 0);
                const hSft = isHomeInMatch ? (m.home_shots_on_target_ft || 0) : (m.away_shots_on_target_ft || 0);
                const aSft = isHomeInMatch ? (m.away_shots_on_target_ft || 0) : (m.home_shots_on_target_ft || 0);
                sHtHome += hSht; sHtAway += aSht; sFtHome += hSft; sFtAway += aSft;
            });

            const avg = (val) => (val / count).toFixed(2);

            // Gols H2H
            document.getElementById('h2hGoalsHtHome').innerText = avg(gHtHome);
            document.getElementById('h2hGoalsHtAway').innerText = avg(gHtAway);
            document.getElementById('h2hGoalsHtTotal').innerText = `(${( (gHtHome + gHtAway) / count ).toFixed(2)} Total)`;

            const gStHome = Math.max(0, gFtHome - gHtHome);
            const gStAway = Math.max(0, gFtAway - gHtAway);
            document.getElementById('h2hGoalsStHome').innerText = avg(gStHome);
            document.getElementById('h2hGoalsStAway').innerText = avg(gStAway);
            document.getElementById('h2hGoalsStTotal').innerText = `(${( (gStHome + gStAway) / count ).toFixed(2)} Total)`;

            document.getElementById('h2hGoalsFtHome').innerText = avg(gFtHome);
            document.getElementById('h2hGoalsFtAway').innerText = avg(gFtAway);
            document.getElementById('h2hGoalsFtTotal').innerText = `(${( (gFtHome + gFtAway) / count ).toFixed(2)} Total)`;

            // Escanteios H2H
            document.getElementById('h2hCornersHtHome').innerText = avg(cHtHome);
            document.getElementById('h2hCornersHtAway').innerText = avg(cHtAway);
            document.getElementById('h2hCornersHtTotal').innerText = `(${( (cHtHome + cHtAway) / count ).toFixed(2)} Total)`;

            const cStHome = Math.max(0, cFtHome - cHtHome);
            const cStAway = Math.max(0, cFtAway - cHtAway);
            document.getElementById('h2hCornersStHome').innerText = avg(cStHome);
            document.getElementById('h2hCornersStAway').innerText = avg(cStAway);
            document.getElementById('h2hCornersStTotal').innerText = `(${( (cStHome + cStAway) / count ).toFixed(2)} Total)`;

            document.getElementById('h2hCornersFtHome').innerText = avg(cFtHome);
            document.getElementById('h2hCornersFtAway').innerText = avg(cFtAway);
            document.getElementById('h2hCornersFtTotal').innerText = `(${( (cFtHome + cFtAway) / count ).toFixed(2)} Total)`;

            // Cartões Amarelos H2H
            document.getElementById('h2hYellowHtHome').innerText = avg(yHtHome);
            document.getElementById('h2hYellowHtAway').innerText = avg(yHtAway);
            document.getElementById('h2hYellowHtTotal').innerText = `(${( (yHtHome + yHtAway) / count ).toFixed(2)} Total)`;

            const yStHome = Math.max(0, yFtHome - yHtHome);
            const yStAway = Math.max(0, yFtAway - yHtAway);
            document.getElementById('h2hYellowStHome').innerText = avg(yStHome);
            document.getElementById('h2hYellowStAway').innerText = avg(yStAway);
            document.getElementById('h2hYellowStTotal').innerText = `(${( (yStHome + yStAway) / count ).toFixed(2)} Total)`;

            document.getElementById('h2hYellowFtHome').innerText = avg(yFtHome);
            document.getElementById('h2hYellowFtAway').innerText = avg(yFtAway);
            document.getElementById('h2hYellowFtTotal').innerText = `(${( (yFtHome + yFtAway) / count ).toFixed(2)} Total)`;

            // Chutes a Gol H2H
            document.getElementById('h2hShotsHtHome').innerText = avg(sHtHome);
            document.getElementById('h2hShotsHtAway').innerText = avg(sHtAway);
            document.getElementById('h2hShotsHtTotal').innerText = `(${( (sHtHome + sHtAway) / count ).toFixed(2)} Total)`;

            const sStHome = Math.max(0, sFtHome - sHtHome);
            const sStAway = Math.max(0, sFtAway - sHtAway);
            document.getElementById('h2hShotsStHome').innerText = avg(sStHome);
            document.getElementById('h2hShotsStAway').innerText = avg(sStAway);
            document.getElementById('h2hShotsStTotal').innerText = `(${( (sStHome + sStAway) / count ).toFixed(2)} Total)`;

            document.getElementById('h2hShotsFtHome').innerText = avg(sFtHome);
            document.getElementById('h2hShotsFtAway').innerText = avg(sFtAway);
            document.getElementById('h2hShotsFtTotal').innerText = `(${( (sFtHome + sFtAway) / count ).toFixed(2)} Total)`;
        }

        function renderH2HMatchesList(dbMatches, apiH2H, currentEventId) {
            const container = document.getElementById('h2hMatchesList');
            
            let filteredDb = dbMatches.filter(m => m.sofascore_event_id != currentEventId && m.status === 'finished');
            let filteredApi = [];
            if (apiH2H && apiH2H.events) {
                filteredApi = apiH2H.events.filter(e => e.id != currentEventId && e.status?.type === 'finished');
            }

            if (filteredDb.length === 0 && filteredApi.length === 0) {
                container.innerHTML = '<div class="loading-spinner">Nenhum confronto anterior encerrado entre essas equipes.</div>';
                return;
            }

            if (filteredDb.length > 0) {
                container.innerHTML = filteredDb.map(m => {
                    const matchDate = m.start_timestamp ? formatBrasiliaTime(m.start_timestamp, true) : (m.match_date || '');
                    return `
                        <div class="period-row" style="padding: 0.75rem 1rem;">
                            <span><b>${escapeHtml(m.home_team_name)}</b> ${m.home_score_ft ?? '-'} : ${m.away_score_ft ?? '-'} <b>${escapeHtml(m.away_team_name)}</b></span>
                            <span style="color: var(--text-muted); font-size: 0.8rem;">${matchDate} (${escapeHtml(m.season_name || '')})</span>
                        </div>
                    `;
                }).join('');
            } else if (filteredApi.length > 0) {
                container.innerHTML = filteredApi.map(evt => {
                    const hName = evt.homeTeam ? evt.homeTeam.name : '';
                    const aName = evt.awayTeam ? evt.awayTeam.name : '';
                    const hScore = evt.homeScore ? evt.homeScore.current : '-';
                    const aScore = evt.awayScore ? evt.awayScore.current : '-';
                    const dStr = evt.startTimestamp ? formatBrasiliaTime(evt.startTimestamp, true) : '';
                    return `
                        <div class="period-row" style="padding: 0.75rem 1rem;">
                            <span><b>${escapeHtml(hName)}</b> ${hScore} : ${aScore} <b>${escapeHtml(aName)}</b></span>
                            <span style="color: var(--text-muted); font-size: 0.8rem;">${dStr}</span>
                        </div>
                    `;
                }).join('');
            }
        }

        async function loadOverallTeamStats(homeTeamId, awayTeamId) {
            try {
                const resp = await fetch(`api.php?action=get_team_stats&home_team_id=${homeTeamId}&away_team_id=${awayTeamId}`);
                const result = await resp.json();

                if (result.success) {
                    const h = result.home_stats;
                    const a = result.away_stats;

                    document.getElementById('ovGoalsHtHome').innerText = h.goals ? h.goals.avg_ht : '-';
                    document.getElementById('ovGoalsHtAway').innerText = a.goals ? a.goals.avg_ht : '-';
                    document.getElementById('ovGoalsStHome').innerText = h.goals ? h.goals.avg_st : '-';
                    document.getElementById('ovGoalsStAway').innerText = a.goals ? a.goals.avg_st : '-';
                    document.getElementById('ovGoalsFtHome').innerText = h.goals ? h.goals.avg_ft : '-';
                    document.getElementById('ovGoalsFtAway').innerText = a.goals ? a.goals.avg_ft : '-';

                    document.getElementById('ovCornersHtHome').innerText = h.corners ? h.corners.avg_ht : '-';
                    document.getElementById('ovCornersHtAway').innerText = a.corners ? a.corners.avg_ht : '-';
                    document.getElementById('ovCornersStHome').innerText = h.corners ? h.corners.avg_st : '-';
                    document.getElementById('ovCornersStAway').innerText = a.corners ? a.corners.avg_st : '-';
                    document.getElementById('ovCornersFtHome').innerText = h.corners ? h.corners.avg_ft : '-';
                    document.getElementById('ovCornersFtAway').innerText = a.corners ? a.corners.avg_ft : '-';

                    document.getElementById('ovYellowHtHome').innerText = h.yellow_cards ? h.yellow_cards.avg_ht : '-';
                    document.getElementById('ovYellowHtAway').innerText = a.yellow_cards ? a.yellow_cards.avg_ht : '-';
                    document.getElementById('ovYellowStHome').innerText = h.yellow_cards ? h.yellow_cards.avg_st : '-';
                    document.getElementById('ovYellowStAway').innerText = a.yellow_cards ? a.yellow_cards.avg_st : '-';
                    document.getElementById('ovYellowFtHome').innerText = h.yellow_cards ? h.yellow_cards.avg_ft : '-';
                    document.getElementById('ovYellowFtAway').innerText = a.yellow_cards ? a.yellow_cards.avg_ft : '-';

                    document.getElementById('ovShotsHtHome').innerText = h.shots_on_target ? h.shots_on_target.avg_ht : '-';
                    document.getElementById('ovShotsHtAway').innerText = a.shots_on_target ? a.shots_on_target.avg_ht : '-';
                    document.getElementById('ovShotsStHome').innerText = h.shots_on_target ? h.shots_on_target.avg_st : '-';
                    document.getElementById('ovShotsStAway').innerText = a.shots_on_target ? a.shots_on_target.avg_st : '-';
                    document.getElementById('ovShotsFtHome').innerText = h.shots_on_target ? h.shots_on_target.avg_ft : '-';
                    document.getElementById('ovShotsFtAway').innerText = a.shots_on_target ? a.shots_on_target.avg_ft : '-';

                    renderDetailedMatches(h.matches || [], a.matches || []);
                }
            } catch (err) {
                console.error("Erro ao carregar estatísticas dos times:", err);
            }
        }

        function renderDetailedMatches(homeMatches, awayMatches) {
            const container = document.getElementById('detailedMatchesContainer');
            const allMatches = [...homeMatches, ...awayMatches];

            if (allMatches.length === 0) {
                container.innerHTML = '<div class="loading-spinner">Nenhuma partida gravada no MySQL para esses times. Sincronize a liga na tela de Favoritos para calcular os totais exatos!</div>';
                return;
            }

            container.innerHTML = allMatches.map(m => {
                const dateStr = m.start_timestamp ? formatBrasiliaTime(m.start_timestamp, true) : (m.match_date || '');
                return `
                    <div class="period-row" style="padding: 0.75rem 1rem;">
                        <span><b>${escapeHtml(m.home_team_name)}</b> ${m.home_score_ft ?? '-'} : ${m.away_score_ft ?? '-'} <b>${escapeHtml(m.away_team_name)}</b></span>
                        <span style="color: var(--text-muted); font-size: 0.8rem;">
                            Escanteios: ${m.home_corners_ft ?? 0}/${m.away_corners_ft ?? 0} | Cartões: ${m.home_yellow_cards_ft ?? 0}/${m.away_yellow_cards_ft ?? 0} (${dateStr})
                        </span>
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

        function toggleAccordion(accId) {
            const content = document.getElementById(accId);
            const icon = document.getElementById('accIcon');
            if (content.classList.contains('active')) {
                content.classList.remove('active');
                icon.innerText = '▼';
            } else {
                content.classList.add('active');
                icon.innerText = '▲';
            }
        }

        function escapeHtml(str) {
            return str.replace(/'/g, "\\'").replace(/"/g, "&quot;");
        }
    </script>
</body>
</html>
