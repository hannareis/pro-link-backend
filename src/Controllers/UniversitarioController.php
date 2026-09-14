<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Universitario;
use App\Repositories\UniversidadeRepository;
use App\Repositories\UniversitarioRepository;

// RF01/RF03 - dados academicos do usuario autenticado (vinculo com universidade,
// curso, semestre). O cadastro/renovacao ainda depende de autorizacao do ADMIN_CREA.
class UniversitarioController
{
    public function __construct(
        private readonly UniversitarioRepository $universitarios = new UniversitarioRepository(),
        private readonly UniversidadeRepository $universidades = new UniversidadeRepository()
    ) {
    }

    public function show(Request $request): void
    {
        $usuarioId = (int) ($request->input('id') ?? auth_id());
        $universitario = $this->universitarios->findByUsuarioId($usuarioId);

        if ($universitario === null) {
            Response::json(['message' => 'Perfil universitario nao encontrado.'], 404);
            return;
        }

        Response::json([
            'data' => $universitario,
            'universidade' => $this->universidades->findById($universitario->universidadeId),
        ]);
    }

    // Upsert do perfil academico do usuario autenticado.
    public function store(Request $request): void
    {
        $usuarioId = auth_id();
        $universidadeId = (int) $request->input('universidade_id');

        if ($this->universidades->findById($universidadeId) === null) {
            Response::json(['message' => 'Universidade informada nao existe.'], 422);
            return;
        }

        $this->universitarios->save(new Universitario(
            idUsuario: $usuarioId,
            universidadeId: $universidadeId,
            curso: (string) $request->input('curso', ''),
            matricula: $request->input('matricula'),

            // Luan: incluindo grau acadêmico que estava faltando!
            grau-academico: $request->input('grau_academico') !== null
                ?(string) $request->input('grau_academico')
                : null,

            semestreAtual: $request->input('semestre_atual') !== null
                ? (int) $request->input('semestre_atual')
                : null,
            previsaoFormatura: $request->input('previsao_formatura'),
            comprovanteMatricula: $request->input('comprovante_matricula'),
        ));

        Response::json(['message' => 'Perfil universitario salvo.', 'id' => $usuarioId], 201);
    }

    public function destroy(Request $request): void
    {
        $usuarioId = auth_id();
        $this->universitarios->delete($usuarioId);

        Response::json(['message' => 'Perfil universitario removido.']);
    }
}
