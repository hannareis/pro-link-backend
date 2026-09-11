<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Denuncia;
use App\Repositories\DenunciaRepository;
use App\Services\AuditoriaService;

// RF06 - denuncias de conteudo/usuario. Qualquer usuario autenticado abre uma
// denuncia; a analise (analisar / index) e restrita ao ADMIN_CREA via RoleMiddleware.
class DenunciaController
{
    public function __construct(
        private readonly DenunciaRepository $denuncias = new DenunciaRepository(),
        private readonly AuditoriaService $auditoriaService = new AuditoriaService()
    ) {
    }

    // Fila de moderacao; aceita ?status= para filtrar.
    public function index(Request $request): void
    {
        $status = $request->input('status');

        Response::json(['data' => $this->denuncias->all($status !== null ? (string) $status : null)]);
    }

    public function show(Request $request): void
    {
        $denuncia = $this->denuncias->findById((int) $request->input('id'));

        if ($denuncia === null) {
            Response::json(['message' => 'Denuncia nao encontrada.'], 404);
            return;
        }

        Response::json(['data' => $denuncia]);
    }

    public function store(Request $request): void
    {
        $denuncianteId = auth_id();

        $id = $this->denuncias->save(new Denuncia(
            idDenunciante: $denuncianteId ?: null,
            idDenunciado: $request->input('id_denunciado') !== null
                ? (int) $request->input('id_denunciado')
                : null,
            motivo: (string) $request->input('motivo', ''),
        ));

        $this->auditoriaService->registrar($denuncianteId, 'denuncia.criada', ['denuncia_id' => $id]);

        Response::json(['message' => 'Denuncia registrada.', 'id' => $id], 201);
    }

    // ADMIN_CREA conclui a analise com parecer e status final.
    public function analisar(Request $request): void
    {
        $id = (int) $request->input('id');
        $moderadorId = auth_id();
        $status = (string) $request->input('status_denuncia', Denuncia::STATUS_EM_ANALISE);

        if ($this->denuncias->findById($id) === null) {
            Response::json(['message' => 'Denuncia nao encontrada.'], 404);
            return;
        }

        $this->denuncias->analisar($id, $moderadorId, $status, $request->input('observacao_moderador'));
        $this->auditoriaService->registrar($moderadorId, 'denuncia.analisada', [
            'denuncia_id' => $id,
            'status' => $status,
        ]);

        Response::json(['message' => 'Denuncia analisada.', 'status' => $status]);
    }
}
