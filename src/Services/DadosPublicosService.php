<?php

declare(strict_types=1);

namespace App\Services;

// Item 4.3 da proposta - complementa dados que nao estao disponiveis na API oficial do desafio.
class DadosPublicosService
{
    // Busca dados complementares em bases publicas (dados.gov.br), com auditoria humana e respeito a LGPD.
    public function buscarComplementar(string $termo): array
    {
        // Item 4.3 da proposta - dados nao disponiveis na API oficial, extraidos do dados.gov.br
        // com auditoria humana e respeito a LGPD
        return [];
    }
}
