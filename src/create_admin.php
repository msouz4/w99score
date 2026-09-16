<?php
/**
 * CLI Script para criar ou redefinir a senha do usuário Administrador
 * Uso:
 *   php create_admin.php [email] [senha]
 * Exemplo:
 *   php create_admin.php admin@w99score.com minha_senha123
 */
require_once __DIR__ . '/db.php';

try {
    $pdo = getPDOConnection();
    
    $email = trim($argv[1] ?? 'admin@w99score.com');
    $rawPass = trim($argv[2] ?? '');

    if (empty($rawPass)) {
        $rawPass = bin2hex(random_bytes(8)); // 16 caracteres aleatórios
    }

    $hash = password_hash($rawPass, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("
        INSERT INTO users (email, password_hash, is_admin) 
        VALUES (?, ?, 1) 
        ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), is_admin = 1
    ");
    $stmt->execute([$email, $hash]);

    echo "\n=======================================================\n";
    echo "  ⚡ w99score - Usuário Administrador Configurado!\n";
    echo "=======================================================\n\n";
    echo "  E-mail de Acesso: {$email}\n";
    echo "  Senha de Acesso:  {$rawPass}\n\n";
    echo "=======================================================\n\n";
} catch (\Throwable $e) {
    echo "\n❌ Erro ao criar/atualizar usuário admin: " . $e->getMessage() . "\n\n";
    exit(1);
}
