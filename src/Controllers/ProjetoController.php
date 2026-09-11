<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Projeto;
use App\Repositories\ProjetoRepository;

// RF03 - projetos exibidos dentro de um portfolio. As competencias utilizadas
// sao sincronizadas via tabela `projeto_competencias`.
class ProjetoController
{
    public function __construct(
        private readonly ProjetoRepository $projetos = new ProjetoRepository()
    ) {
    }

    // Lista os projetos de um portfolio (?id_portfolio=).
    public function index(Request $request): void
    {
        $idPortfolio = (int) $request->input('id_portfolio');

        Response::json(['data' => $this->projetos->listByPortfolio($idPortfolio)]);
    }

    public function show(Request $request): void
    {
        $projeto = $this->projetos->findById((int) $request->input('id'));

        if ($projeto === null) {
            Response::json(['message' => 'Projeto nao encontrado.'], 404);
            return;
        }

        Response::json(['data' => $projeto]);
    }

    public function store(Request $request): void
    {
        $projeto = $this->fromRequest($request);
        $id = $this->projetos->save($projeto);

        $competencias = $request->input('competencias');
        if (is_array($competencias)) {
            $this->projetos->sincronizarCompetencias($id, $competencias);
        }

        Response::json(['message' => 'Projeto criado.', 'id' => $id], 201);
    }

    public function update(Request $request): void
    {
        $id = (int) $request->input('id');

        if ($this->projetos->findById($id) === null) {
            Response::json(['message' => 'Projeto nao encontrado.'], 404);
            return;
        }

        $projeto = $this->fromRequest($request);
        $projeto->id = $id;
        $this->projetos->save($projeto);

        $competencias = $request->input('competencias');
        if (is_array($competencias)) {
            $this->projetos->sincronizarCompetencias($id, $competencias);
        }

        Response::json(['message' => 'Projeto atualizado.']);
    }

    public function destroy(Request $request): void
    {
        $this->projetos->delete((int) $request->input('id'));

        Response::json(['message' => 'Projeto removido.']);
    }

    private function fromRequest(Request $request): Projeto
    {
        return new Projeto(
            idPortfolio: (int) $request->input('id_portfolio'),
            titulo: (string) $request->input('titulo', ''),
            descricao: $request->input('descricao'),
            linksReferencia: (array) $request->input('links_referencia', []),
            dataInicio: $request->input('data_inicio'),
            dataFim: $request->input('data_fim'),
            resultadosMencionaveis: (array) $request->input('resultados_mencionaveis', []),
        );
    }
}
