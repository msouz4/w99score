<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>w99score - Melhores Oportunidades de Apostas</title>
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
            --card-active-border: rgba(56, 189, 248, 0.5);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-purple: #8b5cf6;
            --accent-blue: #3b82f6;
            --accent-cyan: #06b6d4;
            --accent-glow: rgba(56, 189, 248, 0.25);
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

        /* Hero Header */
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
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.3);
            color: #38bdf8;
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            box-shadow: 0 2px 10px rgba(56, 189, 248, 0.15);
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            background: linear-gradient(to right, #ffffff, #93c5fd, #c4b5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 1rem;
            max-width: 850px;
            line-height: 1.5;
        }

        /* Top KPI Stats */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .kpi-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 1.1rem 1.25rem;
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
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .kpi-value {
            font-size: 1.4rem;
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

        /* Market Selector Tabs */
        .market-nav-section {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .market-pills {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
            scrollbar-width: thin;
        }

        .market-pill-btn {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            padding: 0.6rem 1.1rem;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .market-pill-btn:hover {
            color: white;
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .market-pill-btn.active {
            color: white;
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.25), rgba(139, 92, 246, 0.25));
            border-color: #38bdf8;
            box-shadow: 0 0 16px rgba(56, 189, 248, 0.2);
        }

        .market-pill-btn.active .svg-icon {
            fill: #38bdf8;
        }

        /* Filter Controls */
        .filter-controls-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding-top: 1rem;
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
            padding: 0.6rem 1rem 0.6rem 2.4rem;
            border-radius: 10px;
            color: white;
            font-family: inherit;
            font-size: 0.88rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-input-group input:focus {
            border-color: #38bdf8;
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
            padding: 0.6rem 1rem;
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
            padding: 0.6rem 1rem;
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
            border-color: #38bdf8;
        }

        /* Opportunities Grid */
        .opportunities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .opportunities-grid {
                grid-template-columns: 1fr;
            }
            .page-container {
                padding: 1rem;
            }
        }

        /* Opportunity Card */
        .opp-card {
            background: var(--card-bg);
            backdrop-filter: blur(14px);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 1.25rem;
            transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .opp-card:hover {
            transform: translateY(-4px);
            border-color: rgba(56, 189, 248, 0.4);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4), 0 0 20px rgba(56, 189, 248, 0.1);
        }

        .opp-card-glow {
            position: absolute;
            top: 0;
            right: 0;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, var(--glow-color, rgba(56, 189, 248, 0.15)) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Card Header */
        .opp-card-header {
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

        .match-time-badge {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-family: 'JetBrains Mono', monospace;
        }

        /* Matchup Teams Row */
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

        .team-role-tag {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 500;
            display: block;
        }

        .vs-divider {
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.08);
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            letter-spacing: 0.05em;
        }

        /* Opportunity Highlight Banner */
        .opp-highlight-banner {
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .opp-market-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .opp-market-tag {
            font-size: 1.05rem;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .confidence-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
        }

        .confidence-meter-bar {
            width: 100%;
            height: 6px;
            background: rgba(0, 0, 0, 0.35);
            border-radius: 9999px;
            overflow: hidden;
        }

        .confidence-meter-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .opp-stats-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.4rem;
            font-size: 0.78rem;
            color: #cbd5e1;
            font-family: 'JetBrains Mono', monospace;
            background: rgba(0, 0, 0, 0.2);
            padding: 0.6rem 0.75rem;
            border-radius: 8px;
        }

        .opp-stats-item {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .opp-stats-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #38bdf8;
            flex-shrink: 0;
        }

        /* Description / AI Rationale Box */
        .opp-rationale-box {
            background: rgba(15, 23, 42, 0.5);
            border-left: 3px solid #38bdf8;
            border-radius: 0 10px 10px 0;
            padding: 0.85rem 1rem;
            font-size: 0.86rem;
            line-height: 1.5;
            color: #e2e8f0;
        }

        .rationale-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #38bdf8;
            margin-bottom: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Card Action Button */
        .btn-view-analysis {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.15), rgba(139, 92, 246, 0.15));
            border: 1px solid rgba(56, 189, 248, 0.35);
            color: #f8fafc;
            padding: 0.75rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
        }

        .btn-view-analysis:hover {
            background: linear-gradient(135deg, #0284c7, #8b5cf6);
            border-color: transparent;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px var(--accent-glow);
            color: white;
        }

        /* States */
        .loading-container {
            text-align: center;
            padding: 4rem 1rem;
            color: var(--text-muted);
            grid-column: 1 / -1;
        }

        .spinner {
            width: 42px;
            height: 42px;
            border: 3px solid rgba(56, 189, 248, 0.2);
            border-top-color: #38bdf8;
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

        .empty-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem auto;
            color: var(--text-muted);
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .empty-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            line-height: 1.4;
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
                    <path d="M12 2l2.4 7.4h7.6l-6.1 4.5 2.3 7.1L12 16.5 5.8 21l2.3-7.1L2 9.4h7.6z"/>
                </svg>
                <span>Algoritmo Preditivo & Análise Estatística</span>
            </div>
            <h1 class="page-title">Melhores Oportunidades do Dia</h1>
            <p class="page-subtitle">
                O sistema cruza automaticamente o histórico geral, desempenho de mandante e visitante em tempo real e confronto direto para calcular onde estão as maiores probabilidades de acerto em apostas.
            </p>

            <!-- Top KPIs -->
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-icon-wrap" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                        <svg class="svg-icon" style="width: 22px; height: 22px;" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="kpi-value" id="kpiTotalOpps">--</div>
                        <div class="kpi-label">Oportunidades Encontradas</div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon-wrap" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                        <svg class="svg-icon" style="width: 22px; height: 22px;" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="kpi-value" id="kpiAvgConfidence">--%</div>
                        <div class="kpi-label">Confiança Média</div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon-wrap" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                        <svg class="svg-icon" style="width: 22px; height: 22px;" viewBox="0 0 24 24">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="kpi-value" id="kpiTopRating">--</div>
                        <div class="kpi-label">Maior Probabilidade Hoje</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Market Selector & Filters -->
        <div class="market-nav-section">
            <div class="market-pills" id="marketPillsContainer">
                <button class="market-pill-btn active" data-market="all" onclick="selectMarket('all', this)">
                    <svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 2l2.4 7.4h7.6l-6.1 4.5 2.3 7.1L12 16.5 5.8 21l2.3-7.1L2 9.4h7.6z"/></svg>
                    <span>Todas as Oportunidades</span>
                </button>

                <button class="market-pill-btn" data-market="ambos_marcam" onclick="selectMarket('ambos_marcam', this)">
                    <svg class="svg-icon" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                    <span>Ambos Marcam</span>
                </button>

                <button class="market-pill-btn" data-market="cantos_ht" onclick="selectMarket('cantos_ht', this)">
                    <svg class="svg-icon" viewBox="0 0 24 24"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"/></svg>
                    <span>Cantos 1º Tempo</span>
                </button>

                <button class="market-pill-btn" data-market="cantos_st" onclick="selectMarket('cantos_st', this)">
                    <svg class="svg-icon" viewBox="0 0 24 24"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"/></svg>
                    <span>Cantos 2º Tempo</span>
                </button>

                <button class="market-pill-btn" data-market="cantos_ft" onclick="selectMarket('cantos_ft', this)">
                    <svg class="svg-icon" viewBox="0 0 24 24"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"/></svg>
                    <span>Cantos Tempo Integral</span>
                </button>

                <button class="market-pill-btn" data-market="gols_ht" onclick="selectMarket('gols_ht', this)">
                    <svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
                    <span>Gols 1º Tempo</span>
                </button>

                <button class="market-pill-btn" data-market="gols_st" onclick="selectMarket('gols_st', this)">
                    <svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
                    <span>Gols 2º Tempo</span>
                </button>

                <button class="market-pill-btn" data-market="gols_ft" onclick="selectMarket('gols_ft', this)">
                    <svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
                    <span>Gols Tempo Integral</span>
                </button>

                <button class="market-pill-btn" data-market="favorito_vence" onclick="selectMarket('favorito_vence', this)">
                    <svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <span>Favorito Vence</span>
                </button>
            </div>

            <!-- Controls Row -->
            <div class="filter-controls-row">
                <div class="search-input-group">
                    <span class="search-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    </span>
                    <input type="text" id="oppSearchInput" placeholder="Filtrar por time ou liga..." oninput="applyLocalFilters()">
                </div>

                <div class="filter-selectors">
                    <select class="filter-select" id="dateFilterSelect" onchange="fetchOpportunities()">
                        <option value="today">Jogos de Hoje (Padrão)</option>
                        <option value="all">Todos os Próximos Jogos</option>
                    </select>

                    <select class="filter-select" id="confidenceFilterSelect" onchange="applyLocalFilters()">
                        <option value="0">Qualquer Confiança</option>
                        <option value="60" selected>Confiança 60%+</option>
                        <option value="75">Confiança 75%+ (Alta)</option>
                        <option value="85">Confiança 85%+ (Ouro)</option>
                    </select>

                    <button class="btn-refresh" onclick="fetchOpportunities()" title="Recalcular Estatísticas">
                        <svg class="svg-icon" viewBox="0 0 24 24"><path d="M17.65 6.35A7.958 7.958 0 0012 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
                        <span>Atualizar</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Opportunities Grid Container -->
        <div class="opportunities-grid" id="opportunitiesContainer">
            <div class="loading-container">
                <div class="spinner"></div>
                <div>Analisando estatísticas e calculando probabilidades...</div>
            </div>
        </div>
    </div>

    <script>
        let currentMarket = 'all';
        let rawOpportunities = [];
        let filteredOpportunities = [];

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
            fetchOpportunities();
        });

        function selectMarket(marketKey, btnEl) {
            currentMarket = marketKey;
            document.querySelectorAll('.market-pill-btn').forEach(b => b.classList.remove('active'));
            if (btnEl) btnEl.classList.add('active');

            fetchOpportunities();
        }

        async function fetchOpportunities() {
            const container = document.getElementById('opportunitiesContainer');
            container.innerHTML = `
                <div class="loading-container">
                    <div class="spinner"></div>
                    <div>Processando histórico geral e calculando melhores oportunidades...</div>
                </div>
            `;

            const dateFilter = document.getElementById('dateFilterSelect').value;

            try {
                const url = `api.php?action=get_opportunities&market=${encodeURIComponent(currentMarket)}&date=${encodeURIComponent(dateFilter)}&min_confidence=40`;
                const response = await fetch(url);
                const result = await response.json();

                if (result.success && result.data) {
                    rawOpportunities = result.data;
                    updateKpis(rawOpportunities);
                    applyLocalFilters();
                } else {
                    renderEmptyState('Não foi possível carregar as oportunidades no momento.');
                }
            } catch (err) {
                renderEmptyState('Erro ao comunicar com o servidor.');
            }
        }

        function applyLocalFilters() {
            const query = (document.getElementById('oppSearchInput').value || '').toLowerCase().trim();
            const minConf = parseInt(document.getElementById('confidenceFilterSelect').value || '0', 10);

            filteredOpportunities = rawOpportunities.filter(item => {
                if (item.confidence < minConf) return false;

                if (query) {
                    const matchText = `${item.tournament_name} ${item.home_team.name} ${item.away_team.name} ${item.market_name} ${item.market_tag}`.toLowerCase();
                    if (!matchText.includes(query)) return false;
                }

                return true;
            });

            renderOpportunities(filteredOpportunities);
        }

        function updateKpis(items) {
            document.getElementById('kpiTotalOpps').innerText = items.length;

            if (items.length > 0) {
                const sumConf = items.reduce((acc, cur) => acc + (cur.confidence || 0), 0);
                const avgConf = Math.round(sumConf / items.length);
                document.getElementById('kpiAvgConfidence').innerText = `${avgConf}%`;

                const topPick = items[0];
                document.getElementById('kpiTopRating').innerText = `${topPick.confidence}% (${topPick.market_tag})`;
            } else {
                document.getElementById('kpiAvgConfidence').innerText = '0%';
                document.getElementById('kpiTopRating').innerText = '--';
            }
        }

        function renderOpportunities(items) {
            const container = document.getElementById('opportunitiesContainer');

            if (!items || items.length === 0) {
                renderEmptyState('Nenhuma oportunidade atende aos critérios do filtro selecionado.', true);
                return;
            }

            container.innerHTML = items.map(item => {
                const ts = item.start_timestamp;
                const timeFormatted = ts ? formatBrasiliaTime(ts, true) : (item.match_date || '');
                const eventId = item.event_id;

                const color = item.badge_color || '#38bdf8';
                const confidence = item.confidence || 50;

                const statsHtml = (item.stat_summary && item.stat_summary.length > 0)
                    ? item.stat_summary.map(s => `
                        <div class="opp-stats-item">
                            <span class="opp-stats-dot" style="background: ${color};"></span>
                            <span>${escapeHtml(s)}</span>
                        </div>
                    `).join('')
                    : '';

                return `
                    <div class="opp-card" style="--glow-color: ${color}25;">
                        <div class="opp-card-glow"></div>

                        <div>
                            <!-- Header da Partida -->
                            <div class="opp-card-header">
                                <span class="tournament-badge">
                                    <svg class="svg-icon" style="width: 14px; height: 14px;" viewBox="0 0 24 24"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0011 15.9V19H7v2h10v-2h-4v-3.1c2.04-.4 3.61-2.01 3.99-4.06C19.39 11.45 21 9.4 21 7V6c0-1.1-.9-1-2-1zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>
                                    ${escapeHtml(item.tournament_name)}
                                </span>
                                <span class="match-time-badge">
                                    <svg class="svg-icon" style="width: 14px; height: 14px;" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                                    ${timeFormatted}
                                </span>
                            </div>

                            <!-- Matchup Times -->
                            <div class="opp-matchup" style="margin-top: 1rem;">
                                <div class="team-box home">
                                    <div>
                                        <div class="team-title">${escapeHtml(item.home_team.name)}</div>
                                        <span class="team-role-tag">Mandante</span>
                                    </div>
                                    <img class="team-logo" src="${item.home_team.logo}" alt="" onerror="this.style.opacity=0.3">
                                </div>

                                <div class="vs-divider">VS</div>

                                <div class="team-box away">
                                    <img class="team-logo" src="${item.away_team.logo}" alt="" onerror="this.style.opacity=0.3">
                                    <div>
                                        <div class="team-title">${escapeHtml(item.away_team.name)}</div>
                                        <span class="team-role-tag">Visitante</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Banner de Oportunidade -->
                            <div class="opp-highlight-banner" style="margin-top: 1rem; background: ${color}15; border-color: ${color}40;">
                                <div class="opp-market-top">
                                    <div class="opp-market-tag">
                                        <span style="color: ${color}; font-size: 1.15rem;">●</span>
                                        <span>${escapeHtml(item.market_tag)}</span>
                                    </div>
                                    <div class="confidence-badge" style="background: ${color}25; color: ${color}; border: 1px solid ${color}50;">
                                        ${confidence}% Confiança
                                    </div>
                                </div>

                                <div class="confidence-meter-bar">
                                    <div class="confidence-meter-fill" style="width: ${confidence}%; background: linear-gradient(90deg, ${color}80, ${color});"></div>
                                </div>

                                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.78rem;">
                                    <span style="color: var(--text-muted);">Nível: <strong style="color: ${color};">${escapeHtml(item.rating)}</strong></span>
                                    <span style="color: white; font-weight: 700; font-family: 'JetBrains Mono', monospace;">${escapeHtml(item.main_stat)}</span>
                                </div>

                                <!-- Resumo de Estatísticas -->
                                <div class="opp-stats-list">
                                    ${statsHtml}
                                </div>
                            </div>

                            <!-- Rationale / Análise Explicativa -->
                            <div class="opp-rationale-box" style="margin-top: 1rem; border-left-color: ${color};">
                                <div class="rationale-title" style="color: ${color};">
                                    <svg class="svg-icon" style="width: 14px; height: 14px; fill: ${color};" viewBox="0 0 24 24"><path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7zm2.85 11.1l-.85.6V16h-4v-2.3l-.85-.6C7.8 12.16 7 10.63 7 9c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.63-.8 3.16-2.15 4.1z"/></svg>
                                    <span>Por que apostar nesta oportunidade?</span>
                                </div>
                                <div>${formatMarkdownToHtml(item.description)}</div>
                            </div>
                        </div>

                        <!-- Botão de Ação -->
                        <div style="margin-top: 0.5rem;">
                            <a href="analise.php?event_id=${eventId}" class="btn-view-analysis">
                                <span>Visualizar Análise Pré-Jogo</span>
                                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M5 13h11.86l-5.43 5.43 1.42 1.42L21.14 12l-8.29-8.29-1.42 1.42L16.86 11H5v2z"/></svg>
                            </a>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderEmptyState(message, showResetBtn = false) {
            const container = document.getElementById('opportunitiesContainer');
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg class="svg-icon" style="width: 32px; height: 32px;" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    </div>
                    <div class="empty-title">Nenhuma Oportunidade Encontrada</div>
                    <div class="empty-desc">${escapeHtml(message)}</div>
                    ${showResetBtn ? `
                        <button class="btn-refresh" onclick="resetFilters()">
                            <svg class="svg-icon" viewBox="0 0 24 24"><path d="M17.65 6.35A7.958 7.958 0 0012 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
                            <span>Redefinir Filtros</span>
                        </button>
                    ` : ''}
                </div>
            `;
        }

        function resetFilters() {
            document.getElementById('oppSearchInput').value = '';
            document.getElementById('confidenceFilterSelect').value = '0';
            document.getElementById('dateFilterSelect').value = 'today';
            selectMarket('all', document.querySelector('.market-pill-btn[data-market="all"]'));
        }

        function formatMarkdownToHtml(text) {
            if (!text) return '';
            // Converte **bold** para <strong>bold</strong>
            return text.replace(/\*\*(.*?)\*\*/g, '<strong style="color: white;">$1</strong>');
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
