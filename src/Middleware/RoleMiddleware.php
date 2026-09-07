<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

// Restringe uma rota a um conjunto especifico de perfis (ex: admin).
class RoleMiddleware
{
    // Recebe a lista de perfis permitidos para a rota atual.
    public function __construct(private readonly array $allowedRoles = [])
    {
    }

    // Bloqueia com 403 se o usuario nao estiver logado ou seu perfil nao estiver na lista.
    public function handle(Request $request): void
    {
        $user = $request->user();

        if ($user === null || !in_array($user['perfil'], $this->allowedRoles, true)) {
            Response::json(['message' => 'Acesso nao autorizado para este perfil.'], 403);
            exit;
        }
    }
}
