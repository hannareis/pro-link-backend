<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

// Protecao contra CSRF em requisicoes que alteram estado (POST).
class CsrfMiddleware
{
    // Compara o token enviado no formulario com o token da sessao (comparacao segura).
    public function handle(Request $request): void
    {
        if ($request->server['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $token = $request->input('_csrf');

        if (!hash_equals($_SESSION['_csrf_token'] ?? '', (string) $token)) {
            http_response_code(419);
            exit('Token CSRF invalido.');
        }
    }
}
