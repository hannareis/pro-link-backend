<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\NotificacaoRepository;

// RF07 - notificacoes internas exibidas no painel do usuario autenticado.
class NotificacaoController
{
    public function __construct(
        private readonly NotificacaoRepository $notificacoes = new NotificacaoRepository()
    ) {
    }

    // Lista as notificacoes do usuario autenticado. Aceita ?nao_lidas=1 para filtrar.
    public function index(Request $request): void
    {
        $apenasNaoLidas = (bool) $request->input('nao_lidas', false);

        Response::json([
            'data' => $this->notificacoes->listByUsuario(auth_id(), $apenasNaoLidas),
        ]);
    }

    // Marca uma notificacao do usuario autenticado como lida.
    public function markAsRead(Request $request): void
    {
        $id = (int) $request->input('id');
        $marcada = $this->notificacoes->marcarComoLida($id, auth_id());

        if (!$marcada) {
            Response::json(['message' => 'Notificação não encontrada.'], 404);
            return;
        }

        Response::json(['message' => 'Notificação marcada como lida.']);
    }
}
