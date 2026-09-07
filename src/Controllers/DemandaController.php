<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\RecomendacaoService;

// RF04 - gestao e compatibilidade de demandas cadastradas por empresas/terceiros.
class DemandaController
{
    public function __construct(
        private readonly RecomendacaoService $recomendacaoService = new RecomendacaoService()
    ) {
    }

    // Lista demandas compativeis com o historico tecnico do profissional autenticado.
    public function index(Request $request): void
    {
        // RF04 - lista demandas compativeis com o historico do profissional
    }

    // Cadastra uma nova demanda, acionando o Agente de Recomendacao de Area (NLP).
    public function store(Request $request): void
    {
        // RF04 - cadastro de demanda aciona o Agente de Recomendacao de Area (NLP)
    }

    // Exibe o detalhe de uma demanda especifica.
    public function show(Request $request): void
    {
    }
}
