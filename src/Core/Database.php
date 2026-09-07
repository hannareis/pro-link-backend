<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

// Ponto unico de acesso ao MariaDB, compartilhado por todos os Repositories.
class Database
{
    private static ?PDO $connection = null;

    // Retorna sempre a mesma conexao PDO (singleton), abrindo-a apenas na primeira chamada.
    public static function connection(): PDO
    {
        if (self::$connection === null) {
            // Monta a DSN a partir das credenciais do .env (config/database.php).
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_HOST'],
                $_ENV['DB_PORT'],
                $_ENV['DB_DATABASE']
            );

            // Ativa excecoes em erro de SQL e retorna linhas como arrays associativos.
            self::$connection = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        return self::$connection;
    }
}
