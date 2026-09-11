<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Experiencia;
use App\Repositories\ExperienciaRepository;

// RF03 - experiencias (posicoes/servicos) dentro de um portfolio, opcionalmente
// vinculadas a projetos via `projeto_experiencia`.
class ExperienciaController
{
    public function __construct(
        private readonly ExperienciaRepository $experiencias = new ExperienciaRepository()
    ) {
    }

    public function index(Request $request): void
    {
        $idPortfolio = (int) $request->input('id_portfolio');

        Response::json(['data' => $this->experiencias->listByPortfolio($idPortfolio)]);
    }

    public function show(Request $request): void
    {
        $experiencia = $this->experiencias->findById((int) $request->input('id'));

        if ($experiencia === null) {
            Response::json(['message' => 'Experiencia nao encontrada.'], 404);
            return;
        }

        Response::json([
            'data' => $experiencia,
            'projetos' => $this->experiencias->projetosDaExperiencia($experiencia->id),
        ]);
    }

    public function store(Request $request): void
    {
        $id = $this->experiencias->save($this->fromRequest($request));

        Response::json(['message' => 'Experiencia criada.', 'id' => $id], 201);
    }

    public function update(Request $request): void
    {
        $id = (int) $request->input('id');

        if ($this->experiencias->findById($id) === null) {
            Response::json(['message' => 'Experiencia nao encontrada.'], 404);
            return;
        }

        $experiencia = $this->fromRequest($request);
        $experiencia->id = $id;
        $this->experiencias->save($experiencia);

        Response::json(['message' => 'Experiencia atualizada.']);
    }

    public function destroy(Request $request): void
    {
        $this->experiencias->delete((int) $request->input('id'));

        Response::json(['message' => 'Experiencia removida.']);
    }

    // Vincula/desvincula um projeto a esta experiencia (tabela `projeto_experiencia`).
    public function linkProjeto(Request $request): void
    {
        $this->experiencias->vincularProjeto(
            (int) $request->input('id_projeto'),
            (int) $request->input('id_experiencia'),
        );

        Response::json(['message' => 'Projeto vinculado a experiencia.']);
    }

    public function unlinkProjeto(Request $request): void
    {
        $this->experiencias->desvincularProjeto(
            (int) $request->input('id_projeto'),
            (int) $request->input('id_experiencia'),
        );

        Response::json(['message' => 'Vinculo removido.']);
    }

    private function fromRequest(Request $request): Experiencia
    {
        return new Experiencia(
            idPortfolio: (int) $request->input('id_portfolio'),
            tituloPosicaoServico: (string) $request->input('titulo_posicao_servico', ''),
            organizacaoCliente: $request->input('organizacao_cliente'),
            organizacaoId: $request->input('organizacao_id') !== null
                ? (int) $request->input('organizacao_id')
                : null,
            descricaoAtividades: $request->input('descricao_atividades'),
            dataInicio: $request->input('data_inicio'),
            dataFim: $request->input('data_fim'),
        );
    }
}
