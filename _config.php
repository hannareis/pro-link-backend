<?php

declare(strict_types=1);

// Arquivo de configuracao principal da aplicacao (Anexo I, item 8.3.1 do Edital
// Desafio CREA Pro-Link nº 03/2026). Centraliza URLs, PATHs, timezone, charset e
// os parametros de conexao com banco de dados, API do CREA-AM e SMTP - lidos do
// .env para nao expor segredos direto no codigo-fonte.

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
    define('PATH_PUBLIC', BASE_PATH . '/public');
    define('PATH_VIEWS', BASE_PATH . '/views');
    define('PATH_STORAGE', BASE_PATH . '/storage');
    define('PATH_UPLOADS', BASE_PATH . '/public/uploads');
    define('PATH_IMG', BASE_PATH . '/public/assets/img');
}

// Item 8.3.1-g/h: fuso horario e conjunto de caracteres padrao da aplicacao.
date_default_timezone_set('America/Manaus');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

return [
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'Pro-Link',
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    ],

    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'mariadb',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? 'prolink',
        'username' => $_ENV['DB_USERNAME'] ?? 'prolink',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
    ],

    // RF02 - API REST oficial do CREA-AM, acessada mediante Token de Acesso Individual.
    'crea_api' => [
        'base_url' => $_ENV['CREA_API_BASE_URL'] ?? '',
        'token' => $_ENV['CREA_API_TOKEN'] ?? '',
    ],

    // RF07 - servidor SMTP para notificacoes e check-out das Cartas Virtuais.
    'mail' => [
        'host' => $_ENV['MAIL_HOST'] ?? '',
        'port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@prolink.com',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Pro-Link',
    ],

    'security' => [
        'session_secure_cookie' => filter_var($_ENV['SESSION_SECURE_COOKIE'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'password_algo' => PASSWORD_BCRYPT,
    ],
];
