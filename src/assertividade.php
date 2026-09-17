<?php require_once __DIR__ . '/auth.php'; requireAuth(); ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>w99score - Assertividade & Backtest de Oportunidades</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #090d16;
            --bg-nav: rgba(15, 23, 42, 0.85);
            --card-bg: rgba(30, 41, 59, 0.65);
            --card-hover-bg: rgba(30, 41, 59, 0.95);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-purple: #8b5cf6;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --accent-glow: rgba(16, 185, 129, 0.25);
            --amber-gold: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 50% 0%, #064e3b 0%, #090d16 70%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
            background: linear-gradient(135deg, var(--accent-green), var(--accent-blue));
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
            background: linear-gradient(to right, #ffffff, #6ee7b7);
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

        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            width: 100%;
            flex: 1;
        }

        .header-section {
            margin-bottom: 2rem;
        }

        .page-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.15);
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            background: linear-gradient(to right, #ffffff, #6ee7b7, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 1rem;
            max-width: 900px;
            line-height: 1.5;
        }

        /* Top KPI Row */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.25rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .kpi-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .kpi-value {
            font-size: 1.6rem;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
            color: white;
            line-height: 1.2;
        }

        .kpi-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* Control / Filter Bar */
        .controls-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            position: relative;
            z-index: 100;
        }

        .filter-controls-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-input-group {
            position: relative;
            flex: 1;
            min-width: 260px;
        }

        .search-input-group input {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--card-border);
            padding: 0.65rem 1rem 0.65rem 2.4rem;
            border-radius: 10px;
            color: white;
            font-family: inherit;
            font-size: 0.88rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-input-group input:focus {
            border-color: #34d399;
        }

        .search-input-group .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }



        .filter-selectors {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-select {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--card-border);
            padding: 0.65rem 1rem;
            border-radius: 10px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            outline: none;
            cursor: pointer;
        }

        .filter-select option {
            background: #0f172a;
            color: white;
        }

        .btn-refresh {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: white;
            padding: 0.65rem 1rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .btn-refresh:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #34d399;
        }

        /* Market Breakdown Section */
        .section-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .breakdown-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .breakdown-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 1rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .breakdown-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .breakdown-market-name {
            font-weight: 700;
            font-size: 0.92rem;
            color: white;
        }

        .breakdown-win-rate {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            font-size: 1.1rem;
            color: #34d399;
        }

        .breakdown-counts {
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            gap: 0.75rem;
            font-family: 'JetBrains Mono', monospace;
        }

        .breakdown-bar {
            width: 100%;
            height: 6px;
            background: rgba(239, 68, 68, 0.3);
            border-radius: 9999px;
            overflow: hidden;
            display: flex;
        }

        .breakdown-bar-green {
            height: 100%;
            background: #10b981;
            border-radius: 9999px;
            transition: width 0.5s ease;
        }

        /* Feed Grid of Audited Matches */
        .audited-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .audited-grid {
                grid-template-columns: 1fr;
            }
            .page-container {
                padding: 1rem;
            }
        }

        /* Card Auditado */
        .audit-card {
            background: var(--card-bg);
            backdrop-filter: blur(14px);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 1.25rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .audit-card:hover {
            transform: translateY(-3px);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .audit-card.is-green {
            border-left: 4px solid #10b981;
        }

        .audit-card.is-red {
            border-left: 4px solid #ef4444;
        }

        .audit-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .tournament-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--card-border);
            padding: 0.3rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #cbd5e1;
        }

        .result-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.03em;
        }

        .result-badge.green {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.5);
            color: #34d399;
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.25);
        }

        .result-badge.red {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #f87171;
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.25);
        }

        .opp-matchup {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 0.75rem;
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 14px;
            padding: 0.9rem 1rem;
        }

        .team-box {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .team-box.home {
            justify-content: flex-end;
            text-align: right;
        }

        .team-box.away {
            justify-content: flex-start;
            text-align: left;
        }

        .team-logo {
            width: 32px;
            height: 32px;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            padding: 2px;
        }

        .team-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: white;
            line-height: 1.2;
        }

        .ft-score-display {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.1rem;
            font-weight: 800;
            color: #f8fafc;
            background: rgba(0, 0, 0, 0.4);
            padding: 0.3rem 0.6rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .audit-prediction-banner {
            background: rgba(15, 23, 42, 0.5);
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .audit-pred-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .audit-market-tag {
            font-size: 0.98rem;
            font-weight: 800;
            color: white;
        }

        .audit-confidence {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            font-size: 0.85rem;
            color: #38bdf8;
        }

        .audit-actual-box {
            background: rgba(0, 0, 0, 0.25);
            padding: 0.6rem 0.8rem;
            border-radius: 8px;
            font-size: 0.82rem;
            font-family: 'JetBrains Mono', monospace;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .loading-container {
            text-align: center;
            padding: 4rem 1rem;
            color: var(--text-muted);
            grid-column: 1 / -1;
        }

        .spinner {
            width: 42px;
            height: 42px;
            border: 3px solid rgba(16, 185, 129, 0.2);
            border-top-color: #34d399;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            background: rgba(30, 41, 59, 0.4);
            border: 1px dashed var(--card-border);
            border-radius: 18px;
            padding: 3.5rem 1.5rem;
            max-width: 600px;
            margin: 2rem auto;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <div class="page-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="page-badge">
                <svg class="svg-icon" style="width: 14px; height: 14px;" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                <span>Auditoria Transparente & Backtest Preditivo</span>
            </div>
            <h1 class="page-title">Assertividade & Win Rate dos Palpites</h1>
            <p class="page-subtitle">
                Validação em tempo real do algoritmo em partidas finalizadas. Veja exatamente quantos GREENs e REDs o sistema obteve ao prever oportunidades de alta confiança.
            </p>
        </div>

        <!-- Controls Card -->
        <div class="controls-card">
            <div class="filter-controls-row">
                <!-- Input de Pesquisa Local por Time ou Palpite -->
                <div class="search-input-group" style="min-width: 200px; flex: 1;">
                    <span class="search-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    </span>
                    <input type="text" id="backtestSearchInput" placeholder="Filtrar resultado por time/palpite..." oninput="applyLocalFilters()">
                </div>

                <div class="filter-selectors">
                    <label style="display: inline-flex; align-items: center; gap: 0.45rem; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); padding: 0.5rem 0.85rem; border-radius: 12px; cursor: pointer; user-select: none; font-size: 0.85rem; font-weight: 600; color: #fbbf24;">
                        <input type="checkbox" id="chkOnlyFavorites" onchange="if(rawBacktestData!==null) fetchBacktestStats()" style="accent-color: #f59e0b; width: 16px; height: 16px; cursor: pointer;">
                        <span>⭐ Apenas Meus Favoritos</span>
                    </label>

                    <!-- Seletor Padrão de Ligas (Nome + Ano) -->
                    <select class="filter-select" id="leagueSelect" style="max-width: 280px;">
                        <option value="0">🏆 Todas as Ligas Concluídas</option>
                    </select>

                    <select class="filter-select" id="confidenceSelect">
                        <option value="40">Todas as Oportunidades (40%+)</option>
                        <option value="60">Confiança 60%+</option>
                        <option value="70">Confiança 70%+</option>
                        <option value="75">Confiança 75%+ (Alta)</option>
                        <option value="80" selected>🎯 Confiança 80%+ (Ouro)</option>
                    </select>

                    <select class="filter-select" id="marketSelect">
                        <option value="all">Todos os Mercados</option>
                        <option value="ambos_marcam">Ambos Marcam</option>
                        <option value="gols">Mercado de Gols</option>
                        <option value="cantos">Mercado de Cantos</option>
                        <option value="cartoes">Mercado de Cartões</option>
                        <option value="finalizacoes">Mercado de Finalizações</option>
                        <option value="favorito_vence">Favorito Vence</option>
                    </select>

                    <select class="filter-select" id="dateRangeSelect">
                        <option value="month" selected>Jogos deste Mês (Últimos 30d)</option>
                        <option value="7days">Últimos 7 Dias</option>
                        <option value="all">Todas as Partidas Concluídas</option>
                    </select>

                    <button class="btn-refresh" onclick="fetchBacktestStats()" title="Filtrar e Gerar Relatório" style="background: linear-gradient(135deg, #10b981, #059669); border: none; color: white; padding: 0.65rem 1.4rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);">
                        <svg class="svg-icon" viewBox="0 0 24 24"><path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/></svg>
                        <span>Filtrar</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Top KPIs (Abaixo do Filtro) -->
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-icon-wrap" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                    <svg class="svg-icon" style="width: 24px; height: 24px;" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14l-5-5 1.41-1.41L11 14.17l6.59-6.59L19 9l-8 8z"/>
                    </svg>
                </div>
                <div>
                    <div class="kpi-value" id="kpiWinRate" style="color: #34d399;">--%</div>
                    <div class="kpi-label">Win Rate (Taxa de Acerto)</div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon-wrap" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                    <svg class="svg-icon" style="width: 24px; height: 24px;" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <div>
                    <div class="kpi-value" id="kpiGreens" style="color: #10b981;">--</div>
                    <div class="kpi-label">Total GREENs (🟢 Ganhas)</div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon-wrap" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                    <svg class="svg-icon" style="width: 24px; height: 24px;" viewBox="0 0 24 24">
                        <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
                    </svg>
                </div>
                <div>
                    <div class="kpi-value" id="kpiReds" style="color: #f87171;">--</div>
                    <div class="kpi-label">Total REDs (🔴 Perdidas)</div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon-wrap" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                    <svg class="svg-icon" style="width: 24px; height: 24px;" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                    </svg>
                </div>
                <div>
                    <div class="kpi-value" id="kpiTotalPredictions" style="color: #38bdf8;">--</div>
                    <div class="kpi-label">Oportunidades Auditadas</div>
                </div>
            </div>
        </div>

        <!-- Market Breakdown Section -->
        <div class="section-title">
            <svg class="svg-icon" style="color: #34d399;" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
            <span>Assertividade por Mercado</span>
        </div>
        <div class="breakdown-grid" id="marketBreakdownContainer">
            <!-- Renderizado via JS -->
        </div>

        <!-- Audited Predictions Grid -->
        <div class="section-title">
            <svg class="svg-icon" style="color: #38bdf8;" viewBox="0 0 24 24"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0011 15.9V19H7v2h10v-2h-4v-3.1c2.04-.4 3.61-2.01 3.99-4.06C19.39 11.45 21 9.4 21 7V6c0-1.1-.9-1-2-1zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>
            <span>Partidas Finalizadas Auditadas</span>
        </div>

        <div class="audited-grid" id="auditedContainer">
            <!-- Renderizado via JS -->
        </div>
    </div>

    <script>
        let rawBacktestData = null;
        let filteredPredictions = [];
        let availableLeagues = [];

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
            window.onFavoritesUpdated = function() {
                if (rawBacktestData !== null) {
                    fetchBacktestStats();
                }
            };
            fetchLeagues();
            renderInitialWelcomeState();
        });

        async function fetchLeagues() {
            try {
                const res = await fetch('api.php?action=get_backtest_leagues');
                const json = await res.json();
                if (json.success && Array.isArray(json.data)) {
                    availableLeagues = json.data;
                    const select = document.getElementById('leagueSelect');
                    let html = `<option value="0">🏆 Todas as Ligas Concluídas</option>`;
                    availableLeagues.forEach(l => {
                        html += `<option value="${l.tournament_id}">⚽ ${escapeHtml(l.league_label)}</option>`;
                    });
                    select.innerHTML = html;
                }
            } catch (e) {
                console.error('Erro ao carregar lista de ligas:', e);
            }
        }

        function getChosenTournamentId() {
            const select = document.getElementById('leagueSelect');
            return select ? (parseInt(select.value) || 0) : 0;
        }

        function renderInitialWelcomeState() {
            updateKpis({ win_rate: '--', total_greens: '--', total_reds: '--', total_predictions: '--' });

            const breakdownContainer = document.getElementById('marketBreakdownContainer');
            breakdownContainer.innerHTML = `
                <div style="grid-column: 1/-1; color: var(--text-muted); font-size: 0.88rem; text-align: center; padding: 1.5rem; background: rgba(30,41,59,0.3); border-radius: 14px; border: 1px dashed var(--card-border);">
                    Selecione a liga e os filtros desejados acima e clique no botão <strong>Filtrar</strong> para visualizar a assertividade por mercado.
                </div>
            `;

            const auditedContainer = document.getElementById('auditedContainer');
            auditedContainer.innerHTML = `
                <div class="empty-state">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📊</div>
                    <div style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">Pronto para Auditar</div>
                    <div style="font-size: 0.9rem; color: var(--text-muted); max-width: 460px; margin: 0 auto;">
                        Escolha a liga (com nome e temporada/ano), mercado e taxa de confiança nos filtros acima e clique no botão <strong>Filtrar</strong> para gerar o relatório.
                    </div>
                </div>
            `;
        }

        async function fetchBacktestStats() {
            const container = document.getElementById('auditedContainer');
            container.innerHTML = `
                <div class="loading-container">
                    <div class="spinner"></div>
                    <div>Auditando estatísticas em jogos finalizados...</div>
                </div>
            `;

            const minConf = document.getElementById('confidenceSelect').value;
            const market = document.getElementById('marketSelect').value;
            const dateRange = document.getElementById('dateRangeSelect').value;
            const tournamentId = getChosenTournamentId();
            const onlyFavs = document.getElementById('chkOnlyFavorites') && document.getElementById('chkOnlyFavorites').checked ? '1' : '0';

            try {
                const url = `api.php?action=get_backtest_stats&market=${encodeURIComponent(market)}&date_range=${encodeURIComponent(dateRange)}&min_confidence=${encodeURIComponent(minConf)}&tournament_id=${encodeURIComponent(tournamentId)}&only_favorites=${onlyFavs}`;
                const response = await fetch(url);
                const result = await response.json();

                if (result.success && result.data) {
                    rawBacktestData = result.data;
                    applyLocalFilters();
                } else {
                    updateKpis({ win_rate: 0, total_greens: 0, total_reds: 0, total_predictions: 0 });
                    renderMarketBreakdown([]);
                    renderEmptyState('Não foi possível carregar o histórico de backtest no momento.');
                }
            } catch (err) {
                updateKpis({ win_rate: 0, total_greens: 0, total_reds: 0, total_predictions: 0 });
                renderMarketBreakdown([]);
                renderEmptyState('Erro ao comunicar com o servidor.');
            }
        }

        function updateKpis(kpis) {
            if (!kpis) return;
            document.getElementById('kpiWinRate').innerText = `${kpis.win_rate}%`;
            document.getElementById('kpiGreens').innerText = kpis.total_greens;
            document.getElementById('kpiReds').innerText = kpis.total_reds;
            document.getElementById('kpiTotalPredictions').innerText = kpis.total_predictions;
        }


        function renderMarketBreakdown(breakdown) {
            const container = document.getElementById('marketBreakdownContainer');
            if (!breakdown || breakdown.length === 0) {
                container.innerHTML = `<div style="grid-column: 1/-1; color: var(--text-muted); font-size: 0.88rem;">Sem estatísticas de mercado para este filtro.</div>`;
                return;
            }

            container.innerHTML = breakdown.map(item => {
                const total = item.total || 0;
                const greens = item.greens || 0;
                const reds = item.reds || 0;
                const winRate = item.win_rate || 0;

                return `
                    <div class="breakdown-card">
                        <div class="breakdown-card-top">
                            <span class="breakdown-market-name">${escapeHtml(item.market_name)}</span>
                            <span class="breakdown-win-rate">${winRate}%</span>
                        </div>
                        <div class="breakdown-bar">
                            <div class="breakdown-bar-green" style="width: ${winRate}%;"></div>
                        </div>
                        <div class="breakdown-counts">
                            <span style="color: #10b981;">🟢 ${greens} GREENs</span>
                            <span style="color: #f87171;">🔴 ${reds} REDs</span>
                            <span style="color: var(--text-muted); margin-left: auto;">Total: ${total}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function applyLocalFilters() {
            if (!rawBacktestData || !rawBacktestData.predictions) return;
            const query = (document.getElementById('backtestSearchInput').value || '').toLowerCase().trim();

            filteredPredictions = rawBacktestData.predictions.filter(item => {
                if (query) {
                    const matchText = `${item.tournament_name || ''} ${item.home_team?.name || ''} ${item.away_team?.name || ''} ${item.market_name || ''} ${item.market_tag || ''}`.toLowerCase();
                    if (!matchText.includes(query)) return false;
                }
                return true;
            });

            // Recalcular KPIs dinamicamente com base nas partidas filtradas
            const totalPredictions = filteredPredictions.length;
            const totalGreens = filteredPredictions.filter(item => item.is_green).length;
            const totalReds = totalPredictions - totalGreens;
            const winRate = totalPredictions > 0 ? Math.round((totalGreens / totalPredictions) * 100) : 0;

            updateKpis({
                win_rate: winRate,
                total_greens: totalGreens,
                total_reds: totalReds,
                total_predictions: totalPredictions
            });

            // Recalcular Breakdowns por Mercado dinamicamente
            const marketMap = {};
            filteredPredictions.forEach(item => {
                const mName = item.market_name || 'Outros';
                if (!marketMap[mName]) {
                    marketMap[mName] = { market_name: mName, total: 0, greens: 0, reds: 0 };
                }
                marketMap[mName].total++;
                if (item.is_green) {
                    marketMap[mName].greens++;
                } else {
                    marketMap[mName].reds++;
                }
            });

            const breakdownList = Object.values(marketMap).map(m => {
                m.win_rate = m.total > 0 ? Math.round((m.greens / m.total) * 100) : 0;
                return m;
            });
            breakdownList.sort((a, b) => b.total - a.total);

            renderMarketBreakdown(breakdownList);
            renderAuditedMatches(filteredPredictions);
        }

        function renderAuditedMatches(items) {
            const container = document.getElementById('auditedContainer');
            if (!items || items.length === 0) {
                renderEmptyState('Nenhuma partida finalizada atende ao filtro selecionado.');
                return;
            }

            container.innerHTML = items.map(item => {
                const ts = item.start_timestamp;
                const timeFormatted = ts ? formatBrasiliaTime(ts, true) : (item.match_date || '');
                const isGreen = item.is_green;

                const isHomeFav = window.userFavoriteTeamIds && window.userFavoriteTeamIds.includes(parseInt(item.home_team.id));
                const isAwayFav = window.userFavoriteTeamIds && window.userFavoriteTeamIds.includes(parseInt(item.away_team.id));

                return `
                    <div class="audit-card ${isGreen ? 'is-green' : 'is-red'}">
                        <div>
                            <!-- Header do Card -->
                            <div class="audit-card-header">
                                <span class="tournament-badge">
                                    <svg class="svg-icon" style="width: 14px; height: 14px;" viewBox="0 0 24 24"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0011 15.9V19H7v2h10v-2h-4v-3.1c2.04-.4 3.61-2.01 3.99-4.06C19.39 11.45 21 9.4 21 7V6c0-1.1-.9-1-2-1zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>
                                    ${escapeHtml(item.tournament_name)} (${timeFormatted})
                                </span>
                                <span class="result-badge ${isGreen ? 'green' : 'red'}">
                                    ${isGreen ? '🟢 GREEN' : '🔴 RED'}
                                </span>
                            </div>

                            <!-- Matchup Times com Placar Real -->
                            <div class="opp-matchup" style="margin-top: 1rem;">
                                <div class="team-box home">
                                    <div>
                                        <div class="team-title" style="display:flex;align-items:center;gap:4px;">
                                            <span>${escapeHtml(item.home_team.name)}</span>
                                            <button type="button" style="background:none;border:none;cursor:pointer;font-size:0.9rem;" title="Favoritar ${escapeHtml(item.home_team.name)}" onclick="event.stopPropagation(); toggleFavoriteGlobal(${item.home_team.id}, '${escapeHtml(item.home_team.name.replace(/'/g, "\\'"))}', '${item.home_team.logo}')">
                                                ${isHomeFav ? '⭐' : '☆'}
                                            </button>
                                        </div>
                                    </div>
                                    <img class="team-logo" src="${item.home_team.logo}" alt="" onerror="this.style.opacity=0.3">
                                </div>

                                <div class="ft-score-display">
                                    ${item.home_team.score_ft ?? 0} - ${item.away_team.score_ft ?? 0}
                                </div>

                                <div class="team-box away">
                                    <img class="team-logo" src="${item.away_team.logo}" alt="" onerror="this.style.opacity=0.3">
                                    <div>
                                        <div class="team-title" style="display:flex;align-items:center;gap:4px;">
                                            <span>${escapeHtml(item.away_team.name)}</span>
                                            <button type="button" style="background:none;border:none;cursor:pointer;font-size:0.9rem;" title="Favoritar ${escapeHtml(item.away_team.name)}" onclick="event.stopPropagation(); toggleFavoriteGlobal(${item.away_team.id}, '${escapeHtml(item.away_team.name.replace(/'/g, "\\'"))}', '${item.away_team.logo}')">
                                                ${isAwayFav ? '⭐' : '☆'}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Banner da Previsão vs Resultado Real -->
                            <div class="audit-prediction-banner" style="margin-top: 1rem;">
                                <div class="audit-pred-row">
                                    <span class="audit-market-tag">${escapeHtml(item.market_tag)}</span>
                                    <span class="audit-confidence">${item.confidence}% Confiança</span>
                                </div>

                                <div class="audit-actual-box">
                                    <span style="color: var(--text-muted);">Resultado Real da Partida:</span>
                                    <span style="color: white; font-weight: 700;">${escapeHtml(item.actual_summary)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderEmptyState(message) {
            const container = document.getElementById('auditedContainer');
            container.innerHTML = `
                <div class="empty-state">
                    <div style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">Nenhum Dado Auditado</div>
                    <div style="font-size: 0.9rem; color: var(--text-muted);">${escapeHtml(message)}</div>
                </div>
            `;
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>
</body>

</html>
