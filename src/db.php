<?php
date_default_timezone_set('America/Sao_Paulo');

/**
 * Retorna uma instância de conexão PDO com o MySQL.
 *
 * @param int $maxRetries Número de tentativas de conexão durante o boot do banco
 * @param int $retryDelaySeconds Tempo entre tentativas em segundos
 * @return PDO
 * @throws PDOException
 */
function getPDOConnection(int $maxRetries = 5, int $retryDelaySeconds = 2): PDO {
    $host = getenv('DB_HOST') ?: 'db';
    $port = getenv('DB_PORT') ?: '3306';
    $dbname = getenv('DB_NAME') ?: 'app_db';
    $user = getenv('DB_USER') ?: 'app_user';
    $password = getenv('DB_PASS') ?: 'app_password';
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
