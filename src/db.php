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
