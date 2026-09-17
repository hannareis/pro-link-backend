<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Projeto;
use App\Models\ProjetoImagem;
use App\Repositories\ProjetoImagemRepository;
use App\Repositories\ProjetoRepository;
use App\Services\FileUploadService;

// RF03 - projetos exibidos dentro de um portfolio. As competencias utilizadas
// sao sincronizadas via tabela `projeto_competencias`.
class ProjetoController
{
    // MIME real (via fileinfo) -> extensao aceita para as imagens da galeria (campo
    // "imagens[]", enviado por portfolioCriar.js).
    private const IMAGEM_MIMES_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    private const IMAGEM_TAMANHO_MAXIMO_BYTES = 5 * 1024 * 1024;

    // Numero maximo de imagens por projeto (mesmo limite anunciado em portfolioCriar.html).
    private const IMAGENS_MAXIMO = 10;

    public function __construct(
        private readonly ProjetoRepository $projetos = new ProjetoRepository(),
        private readonly \App\Repositories\PortfolioRepository $portfolios = new \App\Repositories\PortfolioRepository(),
        private readonly ProjetoImagemRepository $projetoImagens = new ProjetoImagemRepository(),
        private readonly FileUploadService $fileUploadService = new FileUploadService(
            self::IMAGEM_MIMES_PERMITIDOS,
            self::IMAGEM_TAMANHO_MAXIMO_BYTES
        ),
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

    // Upload de imagens (campo "imagens[]", enviado por portfolioCriar.js) segue o
    // mesmo padrao de PostController/CartaVirtualController: valida/salva os arquivos
    // no disco antes de persistir o projeto.
    public function store(Request $request): void
    {
        try {
            $imagens = $this->uploadImagens($request);
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage()], 400);
            return;
        } catch (\RuntimeException $e) {
            Response::json(['message' => $e->getMessage()], 500);
            return;
        }

        $projeto = $this->fromRequest($request);
        $id = $this->projetos->save($projeto);

        $competencias = $request->input('competencias');
        if (is_array($competencias)) {
            $this->projetos->sincronizarCompetencias($id, $competencias);
        }

        $this->salvarImagens($id, $imagens);

        Response::json(['message' => 'Projeto criado.', 'id' => $id], 201);
    }

    public function update(Request $request): void
    {
        $id = (int) $request->input('id');

        if ($this->projetos->findById($id) === null) {
            Response::json(['message' => 'Projeto nao encontrado.'], 404);
            return;
        }

        try {
            $imagens = $this->uploadImagens($request);
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage()], 400);
            return;
        } catch (\RuntimeException $e) {
            Response::json(['message' => $e->getMessage()], 500);
            return;
        }

        $projeto = $this->fromRequest($request);
        $projeto->id = $id;
        $this->projetos->save($projeto);

        $competencias = $request->input('competencias');
        if (is_array($competencias)) {
            $this->projetos->sincronizarCompetencias($id, $competencias);
        }

        // Novas imagens substituem a galeria inteira - mesmo padrao de "um novo
        // arquivo substitui o anterior" usado em CartaVirtualController/PostController.
        if ($imagens !== []) {
            $this->projetoImagens->deleteAllByProjeto($id);
            $this->salvarImagens($id, $imagens);
        }

        Response::json(['message' => 'Projeto atualizado.']);
    }

    // Valida e salva no disco cada arquivo de "imagens[]" (limitado a IMAGENS_MAXIMO),
    // sem persistir nada no banco ainda - devolve os metadados prontos para virar
    // ProjetoImagem via salvarImagens().
    private function uploadImagens(Request $request): array
    {
        $arquivos = array_slice($request->files('imagens'), 0, self::IMAGENS_MAXIMO);

        $resultados = [];
        foreach ($arquivos as $arquivo) {
            $resultados[] = $this->fileUploadService->store($arquivo, 'projetos');
        }

        return $resultados;
    }

    private function salvarImagens(int $idProjeto, array $imagens): void
    {
        foreach ($imagens as $ordem => $imagem) {
            $this->projetoImagens->save(new ProjetoImagem(
                projetoId: $idProjeto,
                nome: $imagem['nome_arquivo'],
                nomeArmazenado: $imagem['nome_armazenado'],
                tipoMime: $imagem['tipo_mime'],
                tamanho: $imagem['tamanho'],
                caminhoArmazenamento: $imagem['caminho_armazenamento'],
                ordem: $ordem,
            ));
        }
    }

    public function destroy(Request $request): void
    {
        $this->projetos->delete((int) $request->input('id'));

        Response::json(['message' => 'Projeto removido.']);
    }

    private function fromRequest(Request $request): Projeto
    {
        $idPortfolio = (int) $request->input('id_portfolio');
        if ($idPortfolio === 0) {
            $portfolio = $this->portfolios->findByUsuarioId(auth_id());
            if ($portfolio) {
                $idPortfolio = (int) $portfolio->id;
            }
        }

        return new Projeto(
            idPortfolio: $idPortfolio,
            titulo: (string) $request->input('titulo', ''),
            descricao: $request->input('descricao'),
            linksReferencia: (array) $request->input('links_referencia', []),
            dataInicio: $request->input('data_inicio'),
            dataFim: $request->input('data_fim'),
            resultadosMencionaveis: (array) $request->input('resultados_mencionaveis', []),
        );
    }
}
