<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Curtida;
use App\Models\Post;
use App\Repositories\CurtidaPostRepository;
use App\Repositories\PostRepository;

// RF05 - criacao, edicao e interacao com posts (curtidas).
// Refatorado com assistência de Inteligência Artificial para alinhamento aos padrões arquiteturais do projeto.
class PostController
{
    public function __construct(
        private readonly PostRepository $postRepository = new PostRepository(),
        private readonly CurtidaPostRepository $curtidaPostRepository = new CurtidaPostRepository()
    ) {
    }

    public function index(Request $request): void
    {
        $userId = $request->input('usuario_id');
        
        if ($userId) {
            $posts = $this->postRepository->findByUserId((int) $userId);
        } else {
            $posts = $this->postRepository->all();
        }

        Response::json(['data' => $posts]);
    }

    // [IA]: Adequação para obtenção do usuário via auth_id() e envio de resposta JSON com status 201.
    public function store(Request $request): void
    {
        $userId = auth_id();

        $post = new Post(
            userId: $userId,
            conteudo: (string) $request->input('conteudo', ''),
            titulo: (string) $request->input('titulo', ''),
            status: (string) $request->input('status', Post::STATUS_PUBLICO),
        );

        $id = $this->postRepository->save($post);

        Response::json(['message' => 'Post criado.', 'id' => $id], 201);
    }

    // [IA]: Implementação da consulta de post por ID com retorno dos dados ou código de erro 404.
    public function show(Request $request): void
    {
        $post = $this->postRepository->findById((int) $request->input('id'));

        if ($post === null) {
            Response::json(['message' => 'Post nao encontrado.'], 404);
            return;
        }

        Response::json(['data' => $post]);
    }

    public function index(Request $request): void
    {
        $userId = auth_id();

        Response::json(['data' => $this->postRepository->listByAutor($userId)]);
    }

    // [IA]: Padronização do método para update, validação de autor (403), registro (404) e resposta em JSON.
    public function update(Request $request): void
    {
        $id = (int) $request->input('id');
        $userId = auth_id();

        $post = $this->postRepository->findById($id);

        if ($post === null) {
            Response::json(['message' => 'Post nao encontrado.'], 404);
            return;
        }

        // Validação de permissão: assegura que apenas o autor da publicação pode alterá-la.
        if ($userId !== $post->userId) {
            Response::json(['message' => 'Sem permissao para editar este post.'], 403);
            return;
        }

        $post->titulo = (string) $request->input('titulo', $post->titulo);
        $post->conteudo = (string) $request->input('conteudo', $post->conteudo);
        $post->status = (string) $request->input('status', $post->status);
        $this->postRepository->save($post);

        Response::json(['message' => 'Post atualizado.']);
    }

    // [IA]: Ajuste da assinatura para retorno void e envio de confirmação de exclusão em formato JSON.
    public function destroy(Request $request): void
    {
        $post = $this->postRepository->findById((int) $request->input('id'));
        if ($post === null) {
            Response::json(['message' => 'Post nao encontrado.'], 404);
            return;
        }

        if (auth_id() !== $post->userId) {
            Response::json(['message' => 'Sem permissao para deletar este post.'], 403);
            return;
        }

        $this->postRepository->delete($post);

        Response::json(['message' => 'Post removido.']);
    }

    // [IA]: Adequação da lógica de curtida/descurtida com auth_id() e retorno formalizado via Response::json().
    public function likePost(Request $request): void
    {
        $userId = auth_id();
        $postId = (int) $request->input('id');

        $curtida = $this->curtidaPostRepository->findByUsuarioEPost($userId, $postId);

        if ($curtida === null) {
            $curtida = new Curtida(
                userId: $userId,
                publicavelId: $postId,
            );
            $this->curtidaPostRepository->save($curtida);

            Response::json(['message' => 'Post curtido.']);
            return;
        }

        $this->curtidaPostRepository->delete($curtida);

        Response::json(['message' => 'Curtida removida.']);
    }
}
