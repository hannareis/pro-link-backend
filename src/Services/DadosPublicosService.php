<?php

declare(strict_types=1);

namespace App\Services;

// Item 4.3 da proposta - complementa dados que nao estao disponiveis na API oficial do desafio.
class DadosPublicosService
{
    // Busca dados complementares em bases publicas (dados.gov.br), com auditoria humana e
    // respeito a LGPD. Stub proposital por enquanto: a integracao real (HttpClient contra o
    // portal CKAN, config('dados_publicos.base_url')) fica para quando for priorizada.
    public function buscarComplementar(string $termo): array
    {
        return [];
    }
}
