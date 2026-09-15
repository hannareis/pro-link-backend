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

    // Lista publicacoes do feed, priorizando as aderentes ao interesse do usuario.
    public function index(Request $request): void
    {
        $usuarioId = auth_id();
        $publicacoes = $usuarioId !== 0 ? $this->recomendacaoService->curarFeed($usuarioId) : $this->posts->all();

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
