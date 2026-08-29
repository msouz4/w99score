<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>w99score - Painel de Estatísticas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #090d16;
            --bg-nav: rgba(15, 23, 42, 0.85);
            --card-bg: rgba(30, 41, 59, 0.6);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-purple: #8b5cf6;
            --accent-blue: #3b82f6;
            --accent-glow: rgba(139, 92, 246, 0.25);
            --amber-gold: #f59e0b;
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

        .navbar {
            background: var(--bg-nav);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--card-border);
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
            gap: 0.75rem;
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

        .hero-container {
            max-width: 900px;
            margin: 5rem auto;
            text-align: center;
            padding: 0 1.5rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(139, 92, 246, 0.15);
            border: 1px solid rgba(139, 92, 246, 0.3);
            color: #c4b5fd;
            padding: 0.4rem 1.2rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.15;
            margin-bottom: 1rem;
            background: linear-gradient(to bottom, #ffffff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-desc {
            font-size: 1.15rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            max-width: 650px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.05rem;
            text-decoration: none;
            box-shadow: 0 8px 24px var(--accent-glow);
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .btn-cta:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.05rem;
            text-decoration: none;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .svg-icon {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/header.php'; ?>


    <div class="hero-container">
        <div class="badge">
            <svg class="svg-icon" style="width: 16px; height: 16px;" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
            <span>Sistema de Estatísticas Esportivas</span>
        </div>
        <h1 class="hero-title">Análise Avançada de Futebol & Estatísticas HT/FT</h1>
        <p class="hero-desc">
            Explore ligas de futebol do mundo todo, adicione ligas aos seus favoritos e analise partidas futuras com métricas de Gols, Escanteios, Cartões e Chutes divididas em 1º Tempo, 2º Tempo e Total.
        </p>
        <div class="hero-actions">
            <a href="analise.php" class="btn-cta">
                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                <span>Análise Pré-Jogo</span>
                <svg class="svg-icon" viewBox="0 0 24 24"><path d="M5 13h11.86l-5.43 5.43 1.42 1.42L21.14 12l-8.29-8.29-1.42 1.42L16.86 11H5v2z"/></svg>
            </a>
            <a href="favoritos.php" class="btn-secondary">
                <svg class="svg-icon" style="fill: var(--amber-gold);" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                <span>Ver Ligas Favoritas</span>
            </a>
        </div>
    </div>
</body>
</html>
