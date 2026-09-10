<?php

declare(strict_types= 1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\CurtidaRepository;
use App\Repositories\PostRepository;
use App\Models\Post;
use DateTime;

class PostController
{
    public function __construct(
        private readonly CurtidaRepository $curtidaRepository = new CurtidaRepository(),
        private readonly PostRepository $postRepository = new PostRepository()
    ) {
    }
        
    // Adiciona a curtida ao repositório de curtidas atreladas ao post.
    public function likePost(Request $request): void
    {
    
    }

    // Edita o post atrelado ao usuário.
    public function editPost(Request $request): void
    {

    }

    // Adiciona um ou mais anexos ao post.
    public function addAttachment(Request $request): void
    {

    }

    // Remove um ou mais anexos do post.
    public function deleteAttachment(Request $request): void
    {

    }

    public function store(Request $request): void
    {
        $user = $request->user();
        $userId = (int) $request->user()['id'];

        $titulo = (string) $request->input('titulo', '');
        $conteudo = (string) $request->input('conteudo', '');
        $statusPost = (string) $request->input('status', '');

        $post = new Post(
            id: null,
            userId: $userId,
            dataDePostagem: new DateTime(),
            conteudo: $conteudo,
            titulo: $titulo,
            status: $statusPost
        );

        $this->postRepository->save($post);

        //TODO header();
    }

}