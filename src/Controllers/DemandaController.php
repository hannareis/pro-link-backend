<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\DemandaRepository;
use App\Services\RecomendacaoService;

// RF04 - gestao e compatibilidade de demandas cadastradas por empresas/terceiros.
class DemandaController
{
    public function __construct(
        private readonly RecomendacaoService $recomendacaoService = new RecomendacaoService(),
        private readonly DemandaRepository $demandaRepository = new DemandaRepository(),
    ) {
    }

    // Lista demandas compativeis com o historico tecnico do profissional autenticado.
    public function index(Request $request): void
    {
        $profissionalId = auth_id();

        $demandas = $this->recomendacaoService->listarDemandasCompativeis($profissionalId);

        if (empty($demandas)) {
            $demandas = $this->demandaRepository->all();
        }

        Response::json(['data' => $demandas]);
    }

    // Exibe o detalhe de uma demanda especifica.
    public function show(Request $request): void
    {
        $id = (int) $request->input('id');

        $demanda = $this->demandaRepository->findById($id);

        if ($demanda === null) {
            Response::json(['message' => 'Demanda nao encontrada.'], 404);
            return;
        }

        Response::json(['data' => $demanda]);
    }

    // Cadastra uma nova demanda, acionando o Agente de Recomendacao de Area (NLP).
    public function store(Request $request): void
    {
        $id = $this->demandaRepository->save($this->fromRequest($request));

        Response::json(['message' => 'Demanda criada.', 'id' => $id], 201);
    }

    public function update(Request $request): void
    {
        $id = (int) $request->input('id');

        if ($this->demandaRepository->findById($id) === null) {
            Response::json(['message' => 'Demanda nao encontrada.'], 404);
            return;
        }

        $this->demandaRepository->save($this->fromRequest($request));

        Response::json(['message' => 'Demanda atualizada.']);
    }

    public function destroy(Request $request): void
    {
        $this->demandaRepository->delete((int) $request->input('id'));

        Response::json(['message' => 'Demanda removida.']);
    }

    public function fromRequest(Request $request): Demanda
    {
        return new Demanda(
            empresaId: auth_id(),
            titulo: $request->input('titulo'),
            descricao: $request->input('descricao'),
            area: $request->input('area'),
            tipo: $request->input('tipo'),
            cidade: $request->input('cidade'),
            uf: $request->input('uf'),
            modalidade: $request->input('modalidade'),
            status: $request->input('status'),
            dataPublicacao: $request->input('data_publicacao'),
            dataFechamento: $request->input('data_fechamento'),
            criadoEm: $request->input('criado_em'),
            atualizadoEm: $request->input('atualizado_em'),
        );
    }
}
