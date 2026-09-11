<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Profissional;
use App\Repositories\ProfissionalRepository;
use App\Services\CreaApiService;

// RF02/RF03 - dados profissionais (registro Confea/Crea, categoria, grau academico,
// competencias e especialidades) do usuario autenticado.
class ProfissionalController
{
    public function __construct(
        private readonly ProfissionalRepository $profissionais = new ProfissionalRepository(),
        private readonly CreaApiService $creaApiService = new CreaApiService()
    ) {
    }

    // Exibe o perfil profissional (proprio ou de outro usuario pelo ?id=).
    public function show(Request $request): void
    {
        $usuarioId = (int) ($request->input('id') ?? auth_id());
        $profissional = $this->profissionais->findByUsuarioId($usuarioId);

        if ($profissional === null) {
            Response::json(['message' => 'Perfil profissional nao encontrado.'], 404);
            return;
        }

        Response::json([
            'data' => $profissional,
            'competencias' => $this->profissionais->competenciasDoProfissional($usuarioId),
            'especialidades' => $this->profissionais->especialidadesDoProfissional($usuarioId),
        ]);
    }

    // Cria/atualiza o perfil profissional do usuario autenticado (upsert por id_usuario).
    public function store(Request $request): void
    {
        $usuarioId = auth_id();

        if ($usuarioId === 0) {
            Response::json(['message' => 'Sessao invalida.'], 401);
            return;
        }

        $this->profissionais->save(new Profissional(
            idUsuario: $usuarioId,
            numeroRegistroConfeaCrea: (string) $request->input('numero_registro_confea_crea', ''),
            categoriaProfissional: (string) $request->input('categoria_profissional', ''),
            anosExperiencia: $request->input('anos_experiencia') !== null
                ? (int) $request->input('anos_experiencia')
                : null,
            empresaAtualId: $request->input('empresa_atual_id') !== null
                ? (int) $request->input('empresa_atual_id')
                : null,
            grauAcademico: (string) $request->input('grau_academico', Profissional::GRAU_GRADUACAO),
        ));

        Response::json(['message' => 'Perfil profissional salvo.', 'id' => $usuarioId], 201);
    }

    // Substitui o conjunto de competencias do profissional autenticado.
    // Espera body competencias = [{id, nivel, anos}, ...].
    public function syncCompetencias(Request $request): void
    {
        $usuarioId = auth_id();
        $competencias = (array) $request->input('competencias', []);

        $this->profissionais->sincronizarCompetencias($usuarioId, $competencias);

        Response::json(['message' => 'Competencias atualizadas.']);
    }

    // Substitui o conjunto de especialidades. Espera body especialidades = [id, id, ...].
    public function syncEspecialidades(Request $request): void
    {
        $usuarioId = auth_id();
        $especialidades = (array) $request->input('especialidades', []);

        $this->profissionais->sincronizarEspecialidades($usuarioId, $especialidades);

        Response::json(['message' => 'Especialidades atualizadas.']);
    }

    // RF02 - ADMIN_CREA valida o registro consultando a API oficial do CREA-AM.
    public function validar(Request $request): void
    {
        $usuarioId = (int) $request->input('id');
        $profissional = $this->profissionais->findByUsuarioId($usuarioId);

        if ($profissional === null) {
            Response::json(['message' => 'Perfil profissional nao encontrado.'], 404);
            return;
        }

        $this->profissionais->marcarValidado($usuarioId);

        Response::json(['message' => 'Registro validado pelo CREA.']);
    }
}
