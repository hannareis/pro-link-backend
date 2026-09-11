<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Competencia;
use App\Repositories\CompetenciaRepository;

// Catalogo de competencias tecnicas. Leitura publica (montagem de portfolios e
// demandas); escrita restrita ao ADMIN_CREA via RoleMiddleware.
class CompetenciaController
{
    public function __construct(
        private readonly CompetenciaRepository $competencias = new CompetenciaRepository()
    ) {
    }

    public function index(Request $request): void
    {
        $incluirInativas = (bool) $request->input('todas', false);

        Response::json(['data' => $this->competencias->all(!$incluirInativas)]);
    }

    public function store(Request $request): void
    {
        $id = $this->competencias->save(new Competencia(
            nome: (string) $request->input('nome', ''),
            descricao: $request->input('descricao'),
            ativo: (bool) $request->input('ativo', true),
        ));

        Response::json(['message' => 'Competencia cadastrada.', 'id' => $id], 201);
    }

    public function update(Request $request): void
    {
        $id = (int) $request->input('id');

        if ($this->competencias->findById($id) === null) {
            Response::json(['message' => 'Competencia nao encontrada.'], 404);
            return;
        }

        $this->competencias->save(new Competencia(
            id: $id,
            nome: (string) $request->input('nome', ''),
            descricao: $request->input('descricao'),
            ativo: (bool) $request->input('ativo', true),
        ));

        Response::json(['message' => 'Competencia atualizada.']);
    }

    // Competencia referenciada por FK RESTRICT: desativa em vez de apagar.
    public function destroy(Request $request): void
    {
        $this->competencias->desativar((int) $request->input('id'));

        Response::json(['message' => 'Competencia desativada.']);
    }
}
