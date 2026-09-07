<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\CreaApiService;

// RF01 - gestao de dados cadastrais e privacidade do usuario.
class UserController
{
    public function __construct(
        private readonly CreaApiService $creaApiService = new CreaApiService()
    ) {
    }

    // Exibe o perfil publico ou o perfil do usuario autenticado.
    public function show(Request $request): void
    {
        // Exibe perfil do usuario autenticado ou publico
    }

    // Atualiza dados cadastrais e preferencias de visibilidade/privacidade.
    public function update(Request $request): void
    {
        // Atualiza dados cadastrais e preferencias de privacidade
    }

    // Painel de gestao de consentimento LGPD (Termos de Uso, Politica de Privacidade).
    public function privacySettings(Request $request): void
    {
        // Painel de gestao de consentimento LGPD
    }
}
