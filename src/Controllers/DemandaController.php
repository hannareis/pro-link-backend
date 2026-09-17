<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Demanda;
use App\Repositories\DemandaRepository;
use App\Repositories\PessoaJuridicaRepository;
use App\Services\RecomendacaoService;

// RF04 - gestao e compatibilidade de demandas cadastradas por empresas/terceiros.
class DemandaController
{
    private const TIPOS_VALIDOS = [
        Demanda::TIPO_ESTAGIO, Demanda::TIPO_PROJETO, Demanda::TIPO_CONSULTORIA,
        Demanda::TIPO_ART, Demanda::TIPO_PERICIA, Demanda::TIPO_MENTORIA,
        Demanda::TIPO_PESQUISA, Demanda::TIPO_VOLUNTARIADO,
    ];

    private const MODALIDADES_VALIDAS = [
        Demanda::MODALIDADE_PRESENCIAL, Demanda::MODALIDADE_REMOTO, Demanda::MODALIDADE_HIBRIDO,
    ];

    public function __construct(
        private readonly RecomendacaoService $recomendacaoService = new RecomendacaoService(),
        private readonly DemandaRepository $demandaRepository = new DemandaRepository(),
        private readonly PessoaJuridicaRepository $pessoaJuridicaRepository = new PessoaJuridicaRepository(),
    ) {
    }

    // Busca publica de demandas, priorizando as compativeis com o historico tecnico do
    // profissional autenticado. Aceita os filtros do widget: busca (texto livre), area,
    // tipo, modalidade, prazo (curto/medio/longo, derivado de data_fechamento) e status.
    public function index(Request $request): void
    {
        $profissionalId = auth_id();
        $filtros = $this->filtrosDaRequest($request);

        $demandas = $profissionalId !== 0
            ? $this->recomendacaoService->listarDemandasCompativeis($profissionalId)
            : [];

        if (empty($demandas)) {
            $demandas = $this->demandaRepository->buscarComFiltros(...$filtros);
        }

        Response::json(['data' => $demandas]);
    }

    private function filtrosDaRequest(Request $request): array
    {
        return [
            'busca' => (string) $request->input('busca', '') ?: null,
            'area' => (string) $request->input('area', '') ?: null,
            'tipo' => (string) $request->input('tipo', '') ?: null,
            'modalidade' => (string) $request->input('modalidade', '') ?: null,
            'prazo' => (string) $request->input('prazo', '') ?: null,
            'status' => (string) $request->input('status', '') ?: null,
        ];
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

    // Cadastra uma nova demanda para a empresa autenticada.
    public function store(Request $request): void
    {
        // demandas.id_empresa referencia pessoa_juridica(id_usuario) - so uma conta
        // empresa pode publicar. Checar antes evita uma violacao de FK sem tratamento.
        if ($this->pessoaJuridicaRepository->findByUsuarioId(auth_id()) === null) {
            Response::json(['message' => 'Apenas contas de empresa podem publicar demandas.'], 403);
            return;
        }

        $erro = $this->validar($request);
        if ($erro !== null) {
            Response::json(['message' => $erro], 400);
            return;
        }

        try {
            $id = $this->demandaRepository->save($this->fromRequest($request));
        } catch (\PDOException $e) {
            Response::json(['message' => 'Não foi possível salvar a demanda. Verifique os dados enviados.'], 400);
            return;
        }

        Response::json(['message' => 'Demanda criada.', 'id' => $id], 201);
    }

    // Atualiza uma demanda existente da empresa autenticada.
    public function update(Request $request): void
    {
        $id = (int) $request->input('id');
        $demanda = $this->demandaRepository->findById($id);

        if ($demanda === null || $demanda->empresaId !== auth_id()) {
            Response::json(['message' => 'Demanda nao encontrada.'], 404);
            return;
        }

        $erro = $this->validar($request, $demanda);
        if ($erro !== null) {
            Response::json(['message' => $erro], 400);
            return;
        }

        try {
            $this->demandaRepository->save($this->fromRequest($request, $demanda));
        } catch (\PDOException $e) {
            Response::json(['message' => 'Não foi possível atualizar a demanda. Verifique os dados enviados.'], 400);
            return;
        }

        Response::json(['message' => 'Demanda atualizada.']);
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->input('id');
        $demanda = $this->demandaRepository->findById($id);

        if ($demanda === null || $demanda->empresaId !== auth_id()) {
            Response::json(['message' => 'Demanda nao encontrada.'], 404);
            return;
        }

        $this->demandaRepository->delete($id);

        Response::json(['message' => 'Demanda removida.']);
    }

    // Valida os campos obrigatorios e os enums (tipo/modalidade), usando o valor atual
    // ($atual) como fallback nos updates parciais.
    private function validar(Request $request, ?Demanda $atual = null): ?string
    {
        $titulo = trim((string) $request->input('titulo', $atual->titulo ?? ''));
        $descricao = trim((string) $request->input('descricao', $atual->descricao ?? ''));

        if ($titulo === '' || $descricao === '') {
            return 'Título e descrição são obrigatórios.';
        }

        $tipo = (string) $request->input('tipo', $atual->tipo ?? Demanda::TIPO_PROJETO);
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            return 'Tipo de demanda inválido.';
        }

        $modalidade = (string) $request->input('modalidade', $atual->modalidade ?? Demanda::MODALIDADE_PRESENCIAL);
        if (!in_array($modalidade, self::MODALIDADES_VALIDAS, true)) {
            return 'Modalidade inválida.';
        }

        return null;
    }

    // Monta a entidade a partir da Request. $atual preserva o id (e os campos nao
    // enviados) numa atualizacao parcial - sem isso, save() sempre faria um INSERT novo.
    private function fromRequest(Request $request, ?Demanda $atual = null): Demanda
    {
        return new Demanda(
            id: $atual->id ?? null,
            empresaId: $atual->empresaId ?? auth_id(),
            titulo: trim((string) $request->input('titulo', $atual->titulo ?? '')),
            descricao: trim((string) $request->input('descricao', $atual->descricao ?? '')),
            area: (string) $request->input('area', $atual->area ?? ''),
            tipo: (string) $request->input('tipo', $atual->tipo ?? Demanda::TIPO_PROJETO),
            cidade: (string) $request->input('cidade', $atual->cidade ?? ''),
            uf: (string) $request->input('uf', $atual->uf ?? ''),
            modalidade: (string) $request->input('modalidade', $atual->modalidade ?? Demanda::MODALIDADE_PRESENCIAL),
            status: (string) $request->input('status', $atual->status ?? Demanda::STATUS_ABERTA),
            dataFechamento: $request->input('data_fechamento', $atual->dataFechamento ?? null) ?: null,
        );
    }
}
