<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Post;
use App\Repositories\PostRepository;
use App\Services\RecomendacaoService;

// RF04 - feed interativo de compartilhamento de experiencias, curado por recomendacao.
class FeedController
{
    public function __construct(
        private readonly PostRepository $posts = new PostRepository(),
        private readonly RecomendacaoService $recomendacaoService = new RecomendacaoService()
    ) {
    }

    // Mapa do filtro "Nivel/Formacao" do widget de filtros do feed para o enum
    // grau_academico (profissionais/universitarios). "graduando" nao mapeia para um
    // grau especifico: filtra por estudantes (tipo_conta) em vez de nivel academico.
    private const GRAU_POR_FILTRO = [
        'tecnico' => 'TECNOLOGO',
        'graduado' => 'GRADUACAO',
        'especialista' => 'POS_GRADUACAO',
        'mestrado' => 'MESTRADO',
        'doutorado' => 'DOUTORADO',
    ];

    // Lista publicacoes do feed, priorizando as aderentes ao interesse do usuario.
    // Aceita os filtros do widget (RF04): area (nome da especialidade), grau
    // (nivel/formacao) e ordem (cronologia asc/desc).
    public function index(Request $request): void
    {
        $usuarioId = auth_id();
        $area = (string) $request->input('area', '') ?: null;
        $grauFiltro = (string) $request->input('grau', '');
        $grau = self::GRAU_POR_FILTRO[$grauFiltro] ?? null;
        $tipoConta = $grauFiltro === 'graduando' ? \App\Models\User::TIPO_CONTA_ESTUDANTE : null;
        $ordem = (string) $request->input('ordem', 'desc');
        $busca = (string) $request->input('busca', '') ?: null;

        $publicacoes = $usuarioId !== 0
            ? $this->recomendacaoService->curarFeed($usuarioId, especialidade: $area, grauAcademico: $grau, ordem: $ordem, tipoConta: $tipoConta, busca: $busca)
            : $this->posts->all(especialidade: $area, grauAcademico: $grau, ordem: $ordem, tipoConta: $tipoConta, busca: $busca);

        Response::json(['data' => $publicacoes]);
    }

    // Publica uma nova experiencia/post no feed.
    public function store(Request $request): void
    {
        $usuarioId = auth_id();

        if ($usuarioId === 0) {
            Response::json(['message' => 'Sessão inválida.'], 401);
            return;
        }

        $titulo = trim((string) $request->input('titulo', ''));
        $conteudo = trim((string) $request->input('conteudo', ''));

        if ($titulo === '' || $conteudo === '') {
            Response::json(['message' => 'Título e conteúdo são obrigatórios.'], 422);
            return;
        }

        $post = new Post(
            userId: $usuarioId,
            conteudo: $conteudo,
            status: (string) $request->input('status', Post::STATUS_PUBLICO),
            titulo: $titulo,
        );

        $id = $this->posts->save($post);

        Response::json(['message' => 'Publicação criada.', 'id' => $id], 201);
    }
}
