<?php

declare(strict_types= 1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\CurtidaRepository;
use App\Repositories\PostRepository;

class PostController
{
    public function __construct(
        private readonly CurtidaRepository $curtidaRepository = new CurtidaRepository(),
        private readonly PostRepository $postRepository = new postRepository()
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

}