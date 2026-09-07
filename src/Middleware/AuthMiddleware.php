<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

// Bloqueia rotas que exigem usuario autenticado.
class AuthMiddleware
{
    // Redireciona para /login se nao houver usuario na sessao.
    public function handle(Request $request): void
    {
        if ($request->user() === null) {
            Response::redirect('/login');
            exit;
        }
    }
}
