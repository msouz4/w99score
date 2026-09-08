<?php
date_default_timezone_set('America/Sao_Paulo');

function loadEnvIfAvailable(): void {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    $paths = [__DIR__ . '/../.env', __DIR__ . '/.env'];
    foreach ($paths as $path) {
        if (file_exists($path) && is_readable($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $val) = explode('=', $line, 2);
                    $key = trim($key);
                    $val = trim($val, " \t\n\r\0\x0B\"'");
                    if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                        putenv("{$key}={$val}");
                        $_ENV[$key] = $val;
                    }
                }
            }
            break;
        }
    }
}

/**
 * Retorna uma instância de conexão PDO com o MySQL.
 *
 * @param int $maxRetries Número de tentativas de conexão durante o boot do banco
 * @param int $retryDelaySeconds Tempo entre tentativas em segundos
 * @return PDO
 * @throws PDOException
 */
function getPDOConnection(int $maxRetries = 2, int $retryDelaySeconds = 1): PDO {
    loadEnvIfAvailable();

    $host = getenv('DB_HOST') ?: (getenv('MYSQL_HOST') ?: 'db');
    $port = getenv('DB_PORT') ?: '3306';
    $dbname = getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: 'app_db');
    $user = getenv('DB_USER') ?: (getenv('MYSQL_USER') ?: 'app_user');
    $password = getenv('DB_PASS') ?: (getenv('MYSQL_PASSWORD') ?: 'app_password');
    $charset = 'utf8mb4';

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 3,
    ];

    // Tenta conectar ao host configurado. Se o host for 'db' e falhar, tenta '127.0.0.1' (Hostinger VPS sem docker)
    $hostsToTry = [$host];
    if ($host === 'db') {
        $hostsToTry[] = '127.0.0.1';
        $hostsToTry[] = 'localhost';
    }

    $lastException = null;

    foreach ($hostsToTry as $currentHost) {
        $dsn = "mysql:host={$currentHost};port={$port};dbname={$dbname};charset={$charset}";
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                $attempts++;
                $pdo = new PDO($dsn, $user, $password, $options);
                $pdo->exec("SET time_zone = '-03:00'");
                return $pdo;
            } catch (\PDOException $e) {
                $lastException = $e;
                if ($attempts < $maxRetries) {
                    sleep($retryDelaySeconds);
                }
            }
        }
    }

    throw new \PDOException("Erro ao conectar via PDO: " . ($lastException ? $lastException->getMessage() : 'Desconhecido'));
}
