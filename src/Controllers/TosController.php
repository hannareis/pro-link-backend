<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\CreaApiService;

// RF02/RF04 - Tabela de Obras e Servicos (TOS) da API oficial do CREA-AM, usada
// para ligar uma necessidade tecnica (ex: de uma demanda) a atividades
// padronizadas, que por sua vez se relacionam a capacidades de profissionais e
// empresas (Necessidade -> Atividade TOS -> Capacidade tecnica).
class TosController
{
    public function __construct(
        private readonly CreaApiService $creaApiService = new CreaApiService()
    ) {
    }

    // Pesquisa termos na Tabela TOS (paginado). Repassa a resposta da API oficial.
    public function search(Request $request): void
    {
        $termo = trim((string) $request->input('search', ''));

        if ($termo === '') {
            Response::json(['message' => 'Informe o termo de busca (search).'], 400);
            return;
        }

        $page = max(1, (int) $request->input('page', 1));
        $limit = max(1, (int) $request->input('limit', 50));

        try {
            $resposta = $this->creaApiService->pesquisarTos($termo, $page, $limit);
        } catch (\RuntimeException $e) {
            Response::json(['message' => 'Não foi possível contatar a API do CREA-AM. Tente novamente.'], 502);
            return;
        }

        if ($resposta['status'] !== 200) {
            Response::json(['message' => 'Falha ao consultar a Tabela TOS.'], 502);
            return;
        }

        Response::json($resposta['body']);
    }
}
