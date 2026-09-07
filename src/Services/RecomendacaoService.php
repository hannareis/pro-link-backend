<?php

declare(strict_types=1);

namespace App\Services;

// RF04 - Agente de Recomendacao baseado em NLP, usado nas demandas e na curadoria do feed.
class RecomendacaoService
{
    // Sugere areas de atuacao do Confea/Crea a partir do escopo textual de uma demanda.
    public function sugerirAreas(string $escopoTextual): array
    {
        // RF04 - NLP: cruzamento semantico exclusivamente com areas do Confea/Crea
        // Indicativo apenas, sujeito a aprovacao humana do responsavel da publicacao
        return [];
    }

    // Seleciona projetos/publicacoes aderentes ao interesse e historico tecnico do usuario.
    public function curarFeed(int $usuarioId): array
    {
        // RF04 - recomenda projetos/publicacoes aderentes ao historico tecnico do usuario
        return [];
    }
}
