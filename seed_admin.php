<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
config('app');

$db = \App\Core\Database::connection();
$email = 'admin@crea.com';
$senha = '123456';
$hash = password_hash($senha, PASSWORD_DEFAULT);

// Verifica se já existe
$stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo_pessoa, perfil_acesso, conta_ativa) VALUES ('Administrador Mestre', ?, ?, 'FISICA', 'ADMIN_CREA', 1)");
    $stmt->execute([$email, $hash]);
    $userId = $db->lastInsertId();
    $stmt = $db->prepare("INSERT INTO administradores (id_usuario) VALUES (?)");
    $stmt->execute([$userId]);
    echo "Admin criado com sucesso! Email: $email | Senha: $senha\n";
} else {
    echo "Admin ja existe! Email: $email | Senha: $senha (se nao foi alterada)\n";
}
