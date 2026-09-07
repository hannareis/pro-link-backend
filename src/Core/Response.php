<?php

declare(strict_types=1);

namespace App\Core;

// Helpers estaticos para os dois tipos de resposta HTTP usados pela aplicacao.
class Response
{
    // Envia uma resposta JSON com o status HTTP informado.
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    // Redireciona o navegador para outra rota via header Location.
    public static function redirect(string $location): void
    {
        header("Location: {$location}");
    }
}
