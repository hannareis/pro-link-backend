<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Comentario;
use App\Repositories\ComentarioRepository;

// RF05 - comentarios em posts e respostas a outros comentarios.
// Refatorado com assistência de Inteligência Artificial para alinhamento aos padrões arquiteturais do projeto.
class ComentarioController
{
    public function __construct(
        private readonly ComentarioRepository $comentarioRepository = new ComentarioRepository()
    ) {
    }

    // [IA]: Implementação da consulta de comentários vinculados a um post com retorno em formato JSON.
    public function index(Request $request): void
    {
        $idPost = (int) $request->input('id_post');

        Response::json(['data' => $this->comentarioRepository->listById($idPost)]);
    }

    // [IA]: Implementação da busca de comentário por ID com validação de existência (404).
    public function show(Request $request): void
    {
        $comentario = $this->comentarioRepository->findById((int) $request->input('id'));

        if ($comentario === null) {
            Response::json(['message' => 'Comentario nao encontrado.'], 404);
            return;
        }

        Response::json(['data' => $comentario]);
    }

    // [IA]: Adequação para identificação do autor via auth_id() e emissão de resposta JSON com status 201.
    public function store(Request $request): void
    {
        $userId = auth_id();

        $comentario = new Comentario(
            userId: $userId,
            conteudo: (string) $request->input('conteudo', ''),
            status: (string) $request->input('status', ''),
            postId: (int) $request->input('id_post'),
            comentarioPaiId: $request->input('id_comentario') !== null
                ? (int) $request->input('id_comentario')
                : null,
        );

        $id = $this->comentarioRepository->save($comentario);

        Response::json(['message' => 'Comentario criado.', 'id' => $id], 201);
    }

    // [IA]: Adequação do fluxo de atualização com verificação prévia de registro e resposta em formato JSON.
    public function update(Request $request): void
    {
        $id = (int) $request->input('id');

        $comentario = $this->comentarioRepository->findById($id);

        if ($comentario === null) {
            Response::json(['message' => 'Comentario nao encontrado.'], 404);
            return;
        }

        $comentario->conteudo = (string) $request->input('conteudo', $comentario->conteudo);
        $comentario->status = (string) $request->input('status', $comentario->status);
        $this->comentarioRepository->save($comentario);

        Response::json(['message' => 'Comentario atualizado.']);
    }

    // [IA]: Ajuste da assinatura para tipo void e envio de confirmação de exclusão em formato JSON.
    public function destroy(Request $request): void
    {
        $this->comentarioRepository->delete(
            $this->comentarioRepository->findById((int) $request->input('id'))
        );

        Response::json(['message' => 'Comentario removido.']);
    }
}
