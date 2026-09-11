<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Especialidade;
use App\Repositories\EspecialidadeRepository;

// Catalogo de especialidades. Leitura publica; escrita restrita ao ADMIN_CREA.
class EspecialidadeController
{
    public function __construct(
        private readonly EspecialidadeRepository $especialidades = new EspecialidadeRepository()
    ) {
    }

    public function index(Request $request): void
    {
        $incluirInativas = (bool) $request->input('todas', false);

        Response::json(['data' => $this->especialidades->all(!$incluirInativas)]);
    }

    public function store(Request $request): void
    {
        $id = $this->especialidades->save(new Especialidade(
            nome: (string) $request->input('nome', ''),
            descricao: $request->input('descricao'),
            ativo: (bool) $request->input('ativo', true),
        ));

        Response::json(['message' => 'Especialidade cadastrada.', 'id' => $id], 201);
    }

    public function update(Request $request): void
    {
        $id = (int) $request->input('id');

        if ($this->especialidades->findById($id) === null) {
            Response::json(['message' => 'Especialidade nao encontrada.'], 404);
            return;
        }

        $this->especialidades->save(new Especialidade(
            id: $id,
            nome: (string) $request->input('nome', ''),
            descricao: $request->input('descricao'),
            ativo: (bool) $request->input('ativo', true),
        ));

        Response::json(['message' => 'Especialidade atualizada.']);
    }

    public function destroy(Request $request): void
    {
        $this->especialidades->desativar((int) $request->input('id'));

        Response::json(['message' => 'Especialidade desativada.']);
    }
}
