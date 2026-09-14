<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Universidade;
use App\Repositories\UniversidadeRepository;

// Catalogo de universidades: leitura publica, escrita restrita ao ADMIN_CREA
// (garantido pelo RoleMiddleware nas rotas).
class UniversidadeController
{
    public function __construct(
        private readonly UniversidadeRepository $universidades = new UniversidadeRepository()
    ) {
    }

    // Lista universidades ativas; aceita ?q= para busca por nome/sigla.
    public function index(Request $request): void
    {
        $termo = trim((string) $request->input('q', ''));
        $lista = $termo !== ''
            ? $this->universidades->search($termo)
            : $this->universidades->all();

        Response::json(['data' => $lista]);
    }

    public function show(Request $request): void
    {
        $universidade = $this->universidades->findById((int) $request->input('id'));

        if ($universidade === null) {
            Response::json(['message' => 'Universidade nao encontrada.'], 404);
            return;
        }

        Response::json(['data' => $universidade]);
    }

    public function store(Request $request): void
    {
        $id = $this->universidades->save($this->fromRequest($request));

        Response::json(['message' => 'Universidade cadastrada.', 'id' => $id], 201);
    }

    public function update(Request $request): void
    {
        $id = (int) $request->input('id');

        if ($this->universidades->findById($id) === null) {
            Response::json(['message' => 'Universidade nao encontrada.'], 404);
            return;
        }

        $universidade = $this->fromRequest($request);
        $universidade->id = $id;
        $this->universidades->save($universidade);

        Response::json(['message' => 'Universidade atualizada.']);
    }

    // Exclusao logica (ativa = 0): universidades com universitarios vinculados
    // nao podem ser removidas (FK RESTRICT).
    public function destroy(Request $request): void
    {
        $this->universidades->desativar((int) $request->input('id'));

        Response::json(['message' => 'Universidade desativada.']);
    }

    private function fromRequest(Request $request): Universidade
    {
        return new Universidade(
            nome: strtolower((string) $request->input('nome', '')),
            sigla: strtolower($request->input('sigla')),
            cnpj: $request->input('cnpj'),
            tipo: (string) $request->input('tipo', Universidade::TIPO_OUTRA),
            cidade: $request->input('cidade'),
            estado: $request->input('estado'),
            site: $request->input('site'),
            ativa: (bool) $request->input('ativa', true),
        );
    }
}
