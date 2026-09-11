<?php

declare(strict_types= 1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Comentario;
use App\Repositories\ComentarioRepository;
use App\Services\NotificacaoService;
use App\Core\Response;

use DateTime;

class ComentarioController
{
    public function __construct(
        private readonly NotificacaoService $notificacaoService = new NotificacaoService(),
        private readonly ComentarioRepository $comentarioRepository = new ComentarioRepository()
    ) {
    }

    // Atrela o comentário ao Post/Comentário alvo
    public function store(Request $request): void
    {
        $userId = (int) $request->user()['id'];

        $conteudo = (string) $request->input('conteudo', '');
        $status = (string) $request->input('status', '');
        $postId = (int) $request->input('id_post', '');
        $comentarioPaiId = (int) $request->input('id_comentario');

        $comentario = new Comentario
        (
            id: null,
            userId: $userId,
            dataDePostagem: new DateTime(),
            dataEdicao: new DateTime(),
            conteudo: $conteudo,
            status: $status,
            postId: $postId,
            comentarioPaiId: $comentarioPaiId
        );

        $id = $this->comentarioRepository->save($comentario);

        Response::redirect("/posts/comentarios/$id");
        return;
    }

    // Deleta o comentário do Post/Comentário alvo
    public function delete(Request $request): bool
    {
        $comentarioId = (int) $request->input('comentario_id', '');

        return $this->comentarioRepository->delete($this->comentarioRepository->findById($comentarioId));
    }

    // // Edita o comentário no Post/Comentário alvo
    public function editComment(Request $request): void
    {
        $comentarioId = (int) $request->input('id_comentario', '');

        $this->comentarioRepository->save($this->comentarioRepository->findById($comentarioId));

        Response::redirect("/posts/comentarios/$comentarioId");
        return;

    }
}