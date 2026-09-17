<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\DemonstracaoInteresse;
use App\Repositories\DemonstracaoInteresseRepository;
use App\Services\NotificacaoService;

// RF04/RF05 - manifestacoes de interesse de pessoas fisicas sobre demandas
// publicadas por empresas. Unica por (demanda, usuario) pela constraint do banco.
class DemonstracaoInteresseController
{
    public function __construct(
        private readonly DemonstracaoInteresseRepository $interesses = new DemonstracaoInteresseRepository(),
        private readonly NotificacaoService $notificacaoService = new NotificacaoService()
    ) {
    }

    // Interesses de uma demanda (?id_demanda=) para a empresa dona da vaga.
    public function porDemanda(Request $request): void
    {
        $idDemanda = (int) $request->input('id');

        Response::json(['data' => $this->interesses->listByDemanda($idDemanda)]);
    }

    // Interesses enviados pelo usuario autenticado.
    public function meusInteresses(Request $request): void
    {
        $usuarioId = auth_id();

        Response::json(['data' => $this->interesses->listByUsuario($usuarioId)]);
    }

    public function store(Request $request): void
    {
        $usuarioId = auth_id();
        $idDemanda = (int) $request->input('id_demanda');

        if ($this->interesses->findByDemandaUsuario($idDemanda, $usuarioId) !== null) {
            Response::json(['message' => 'Voce ja demonstrou interesse nesta demanda.'], 409);
            return;
        }

        $id = $this->interesses->save(new DemonstracaoInteresse(
            idDemanda: $idDemanda,
            idUsuario: $usuarioId,
            titulo: (string) $request->input('titulo', ''),
            mensagem: (string) $request->input('mensagem', ''),
        ));

        $this->notificacaoService->notificarPainel($usuarioId, 'Sua demonstracao de interesse foi enviada.');

        Response::json(['message' => 'Interesse registrado.', 'id' => $id], 201);
    }

    // A empresa move o interesse pelo funil (EM_ANALISE, ACEITA, RECUSADA).
    public function atualizarStatus(Request $request): void
    {
        $id = (int) $request->input('id');
        $status = (string) $request->input('status', DemonstracaoInteresse::STATUS_EM_ANALISE);

        $interesse = $this->interesses->findById($id);
        if ($interesse === null) {
            Response::json(['message' => 'Demonstracao de interesse nao encontrada.'], 404);
            return;
        }

        $this->interesses->atualizarStatus($id, $status);
        $this->notificacaoService->notificarPainel(
            $interesse->idUsuario,
            "Sua demonstracao de interesse mudou para: {$status}."
        );

        Response::json(['message' => 'Status atualizado.', 'status' => $status]);
    }

    // O proprio autor cancela a manifestacao.
    public function cancelar(Request $request): void
    {
        $id = (int) $request->input('id');
        $usuarioId = auth_id();

        $interesse = $this->interesses->findById($id);
        if ($interesse === null || $interesse->idUsuario !== $usuarioId) {
            Response::json(['message' => 'Demonstracao de interesse nao encontrada.'], 404);
            return;
        }

        $this->interesses->atualizarStatus($id, DemonstracaoInteresse::STATUS_CANCELADA);

        Response::json(['message' => 'Interesse cancelado.']);
    }
}
