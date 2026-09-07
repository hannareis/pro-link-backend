<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\AuditoriaService;

// RF06 - painel administrativo, separado do restante da plataforma (feed, portfolios etc).
class AdminController
{
    public function __construct(
        private readonly AuditoriaService $auditoriaService = new AuditoriaService()
    ) {
    }

    // Visao geral com indicadores gerenciais dinamicos da plataforma.
    public function dashboard(Request $request): void
    {
        // RF06 - indicadores gerenciais e visao geral da plataforma
    }

    // Gestao de perfis e autorizacao de cadastro/renovacao de universitarios e pesquisadores.
    public function manageUsers(Request $request): void
    {
        // RF06 - gestao de perfis, autorizacao de universitarios/pesquisadores
    }

    // Moderacao de conteudo do feed e tratamento de denuncias/fraudes.
    public function moderation(Request $request): void
    {
        // RF06 - tratamento de denuncias e fraudes
    }

    // Consulta aos logs de auditoria (acoes criticas, exceto dados sensiveis de negociacoes).
    public function auditLogs(Request $request): void
    {
        // RF06 - logs de auditoria rastreaveis
    }
}
