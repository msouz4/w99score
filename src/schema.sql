CREATE TABLE IF NOT EXISTS favorite_leagues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    category_name VARCHAR(100) DEFAULT NULL,
    logo_url VARCHAR(500) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sofascore_event_id INT UNIQUE NOT NULL,
    tournament_id INT NOT NULL,
    season_id INT NOT NULL,
    season_name VARCHAR(100) DEFAULT NULL,
    round VARCHAR(50) DEFAULT NULL,
    start_timestamp BIGINT DEFAULT NULL,
    match_date DATETIME DEFAULT NULL,
    status VARCHAR(50) DEFAULT NULL,
    home_team_id INT DEFAULT NULL,
    home_team_name VARCHAR(150) NOT NULL,
    home_team_logo VARCHAR(500) DEFAULT NULL,
    away_team_id INT DEFAULT NULL,
    away_team_name VARCHAR(150) NOT NULL,
    away_team_logo VARCHAR(500) DEFAULT NULL,
    
    -- Gols (HT = 1st Half, FT = Full Time)
    home_score_ht INT DEFAULT NULL,
    away_score_ht INT DEFAULT NULL,
    home_score_ft INT DEFAULT NULL,
    away_score_ft INT DEFAULT NULL,
    
    -- Escanteios (HT e FT)
    home_corners_ht INT DEFAULT NULL,
    away_corners_ht INT DEFAULT NULL,
    home_corners_ft INT DEFAULT NULL,
    away_corners_ft INT DEFAULT NULL,
    
    -- Cartões Amarelos (HT e FT)
    home_yellow_cards_ht INT DEFAULT NULL,
    away_yellow_cards_ht INT DEFAULT NULL,
    home_yellow_cards_ft INT DEFAULT NULL,
    away_yellow_cards_ft INT DEFAULT NULL,
    
    -- Finalizações / Total Shots (HT e FT)
    home_shots_ht INT DEFAULT NULL,
    away_shots_ht INT DEFAULT NULL,
    home_shots_ft INT DEFAULT NULL,
    away_shots_ft INT DEFAULT NULL,
    
    -- Chutes a Gol / Shots on Target (HT e FT) [compatibilidade]
    home_shots_on_target_ht INT DEFAULT NULL,
    away_shots_on_target_ht INT DEFAULT NULL,
    home_shots_on_target_ft INT DEFAULT NULL,
    away_shots_on_target_ft INT DEFAULT NULL,
    
    -- Flag de Controle de Qualidade
    is_stats_incomplete TINYINT(1) DEFAULT 0,
    incomplete_reason VARCHAR(255) DEFAULT NULL,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_synced_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_tournament (tournament_id),
    INDEX idx_season (season_id),
    INDEX idx_match_date (match_date),
    INDEX idx_status (status),
    INDEX idx_stats_incomplete (is_stats_incomplete)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    last_login_at DATETIME DEFAULT NULL,
    last_login_ip VARCHAR(45) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_favorite_teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    team_id INT NOT NULL,
    team_name VARCHAR(150) NOT NULL,
    team_logo VARCHAR(500) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_team_unique (user_id, team_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_team_id (team_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_access_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    request_method VARCHAR(10) DEFAULT 'GET',
    page_url VARCHAR(255) NOT NULL,
    is_api TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_time (user_id, created_at),
    INDEX idx_ip (ip_address),
    INDEX idx_is_api (is_api),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



