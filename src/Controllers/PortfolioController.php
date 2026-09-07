<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\CreaApiService;

// RF03 - portfolio profissional/academico/empresarial, vitrine principal do usuario.
class PortfolioController
{
    public function __construct(
        private readonly CreaApiService $creaApiService = new CreaApiService()
    ) {
    }

    // Exibe o portfolio com suas abas: resumo, competencias, ARTs/CATs, formacao, projetos.
    public function show(Request $request): void
    {
        // RF03 - vitrine com abas: resumo, competencias, ARTs/CATs, formacao, projetos
    }

    // Cria/atualiza o portfolio; universitarios exigem validacao de um Responsavel Tecnico.
    public function store(Request $request): void
    {
        // RF03 - universitarios exigem validacao de Responsavel Tecnico
    }

    // Atualiza a visibilidade/conteudo de um portfolio existente.
    public function update(Request $request): void
    {
    }
}
