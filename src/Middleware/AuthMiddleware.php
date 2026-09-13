<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

// Bloqueia rotas que exigem usuario autenticado.
class AuthMiddleware
{
    // Retorna erro 401 se nao houver usuario na sessao (para consumo via API/SPA).
    public function handle(Request $request): void
    {
        if ($request->user() === null) {
            Response::json(['error' => 'Não autorizado'], 401);
            exit;
        }
    }
}
