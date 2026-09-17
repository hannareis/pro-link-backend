<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PostRepository;
use App\Repositories\ProfissionalRepository;

// RF04 - Agente de Recomendacao baseado em NLP, usado nas demandas e na curadoria do feed.
class RecomendacaoService
{
    public function __construct(
        private readonly PostRepository $posts = new PostRepository(),
        private readonly ProfissionalRepository $profissionais = new ProfissionalRepository()
    ) {
    }

    // Sugere areas de atuacao do Confea/Crea a partir do escopo textual de uma demanda.
    public function sugerirAreas(string $escopoTextual): array
    {
        // RF04 - NLP: cruzamento semantico exclusivamente com areas do Confea/Crea
        // Indicativo apenas, sujeito a aprovacao humana do responsavel da publicacao
        return [];
    }

    // Seleciona publicacoes aderentes ao interesse e historico tecnico do usuario: sem um
    // modelo de NLP disponivel, usa como proxy as competencias cadastradas no portfolio
    // profissional, priorizando posts de autores com pelo menos uma competencia em comum.
    public function curarFeed(int $usuarioId): array
    {
        $publicacoes = $this->posts->all();
        $competenciasUsuario = $this->idsCompetencias($usuarioId);

        if ($competenciasUsuario === []) {
            // Sem competencias cadastradas (perfil nao profissional, ou portfolio ainda
            // incompleto): nao ha base para curadoria, mantem a ordem cronologica.
            return $publicacoes;
        }

        $aderentes = [];
        $demais = [];

        foreach ($publicacoes as $post) {
            $ehAderente = $post->userId !== null
                && array_intersect($competenciasUsuario, $this->idsCompetencias($post->userId)) !== [];

            if ($ehAderente) {
                $aderentes[] = $post;
            } else {
                $demais[] = $post;
            }
        }

        return [...$aderentes, ...$demais];
    }

    // Ids das competencias do profissional vinculado ao usuario (vazio se nao for profissional).
    private function idsCompetencias(int $usuarioId): array
    {
        return array_column($this->profissionais->competenciasDoProfissional($usuarioId), 'id_competencia');
    }
}
