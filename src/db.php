<?php
date_default_timezone_set('America/Sao_Paulo');

/**
 * Retorna variável de ambiente ou fallback lendo arquivo .env
 */
function getAppEnv(string $key, string $default = ''): string {
    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return $val;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    
    static $envFileParsed = null;
    if ($envFileParsed === null) {
        $envFileParsed = [];
        $paths = [__DIR__ . '/../.env', __DIR__ . '/.env'];
        foreach ($paths as $p) {
            if (file_exists($p)) {
                $lines = file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) continue;
                    if (strpos($line, '=') !== false) {
                        [$k, $v] = explode('=', $line, 2);
                        $envFileParsed[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
                    }
                }
                break;
            }
        }
    }
    return $envFileParsed[$key] ?? $default;
}

/**
 * Retorna uma instância de conexão PDO com o MySQL.
 *
 * @param int $maxRetries Número de tentativas de conexão durante o boot do banco
 * @param int $retryDelaySeconds Tempo entre tentativas em segundos
 * @return PDO
 * @throws PDOException
 */
function getPDOConnection(int $maxRetries = 5, int $retryDelaySeconds = 2): PDO {
    $host = getAppEnv('DB_HOST', 'db');
    $port = getAppEnv('DB_PORT', '3306');
    $dbname = getAppEnv('DB_NAME', getAppEnv('MYSQL_DATABASE', 'app_db'));
    $user = getAppEnv('DB_USER', getAppEnv('MYSQL_USER', 'app_user'));
    $password = getAppEnv('DB_PASS', getAppEnv('MYSQL_PASSWORD', 'w99_db_#8a9b7c6d5e4f3210_Sec'));
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $attempts = 0;
    while ($attempts < $maxRetries) {
        try {
            $attempts++;
            $pdo = new PDO($dsn, $user, $password, $options);
            $pdo->exec("SET time_zone = '-03:00'");
            ensureUsersTableAndAdmin($pdo);
            return $pdo;
        } catch (\PDOException $e) {
            if ($attempts >= $maxRetries) {
                throw new \PDOException("Erro ao conectar via PDO (após {$attempts} tentativas): " . $e->getMessage(), (int)$e->getCode());
            }
            sleep($retryDelaySeconds);
        }
    }

    throw new \PDOException("Não foi possível conectar ao banco de dados MySQL.");
}

/**
 * Garante que a tabela `users` exista e seia o usuário admin inicial caso não exista nenhum admin.
 */
function ensureUsersTableAndAdmin(PDO $pdo): void {
    static $ensured = false;
    if ($ensured) return;
    $ensured = true;

    try {
        $pdo->exec("
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
        ");

        // Migração suave: adiciona colunas se não existirem na tabela users ou user_access_logs existente
        $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login_at'");
        if ($colCheck && $colCheck->rowCount() === 0) {
            @$pdo->exec("ALTER TABLE users ADD COLUMN last_login_at DATETIME DEFAULT NULL, ADD COLUMN last_login_ip VARCHAR(45) DEFAULT NULL");
        }

        $colApiCheck = $pdo->query("SHOW COLUMNS FROM user_access_logs LIKE 'is_api'");
        if ($colApiCheck && $colApiCheck->rowCount() === 0) {
            @$pdo->exec("ALTER TABLE user_access_logs ADD COLUMN is_api TINYINT(1) DEFAULT 0, ADD INDEX idx_is_api (is_api)");
        }

        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1");
        $count = (int)$stmt->fetchColumn();

        if ($count === 0) {
            $adminEmail = getAppEnv('INITIAL_ADMIN_EMAIL', 'admin@w99score.com');
            $rawPass = bin2hex(random_bytes(8)); // 16 caracteres hexadecimais aleatórios
            $hash = password_hash($rawPass, PASSWORD_BCRYPT);

            $ins = $pdo->prepare("INSERT INTO users (email, password_hash, is_admin) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE is_admin = 1");
            $ins->execute([$adminEmail, $hash]);

            // 1. Envia as credenciais diretamente para os logs do servidor/docker (error_log)
            error_log("=================================================");
            error_log("⚡ w99score - USUÁRIO ADMIN INICIAL CRIADO:");
            error_log("E-mail: {$adminEmail}");
            error_log("Senha:  {$rawPass}");
            error_log("=================================================");

            // 2. Salva em pasta /tmp (sys_get_temp_dir) garantindo permissão de escrita
            $tempLog = sys_get_temp_dir() . '/w99score_initial_admin.json';
            $payload = json_encode([
                'email' => $adminEmail,
                'password' => $rawPass,
                'generated_at' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);
            @file_put_contents($tempLog, $payload);

            // 3. Se o diretório atual for gravável, salva também em initial_admin_credentials.json
            if (is_writable(__DIR__)) {
                @file_put_contents(__DIR__ . '/initial_admin_credentials.json', $payload);
            }
        }
    } catch (\Throwable $e) {
        error_log("Erro ao inicializar tabela de usuários/admin: " . $e->getMessage());
    }
}

