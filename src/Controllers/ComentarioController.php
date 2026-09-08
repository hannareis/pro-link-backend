<?php

declare(strict_types= 1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\ComentarioRepository;
use App\Services\NotificacaoService;

class ComentarioController
{
    public function __construct(
        private readonly NotificacaoService $notificacaoService = new NotificacaoService(),
        private readonly ComentarioRepository $comentarioRepository = new ComentarioRepository()
    ) {
    }

    // Atrela o comentário ao Post/Comentário alvo
    public function addComment(Request $request): void
    {
        
    }

    // Deleta o comentário do Post/Comentário alvo
    public function deleteComment(Request $request): void
    {
        
    }

    // // Edita o comentário no Post/Comentário alvo
    public function editComment(Request $request): void
    {

    }

    // Lista os comentários do Post/Comentário alvo

    public function listComments(Request $request): array
    {
        return [];
    }
}