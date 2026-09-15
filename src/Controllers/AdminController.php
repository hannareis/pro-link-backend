<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Denuncia;
use App\Models\User;
use App\Repositories\AuditoriaRepository;
use App\Repositories\DenunciaRepository;
use App\Repositories\ProfissionalRepository;
use App\Repositories\UserRepository;
use App\Services\AuditoriaService;

// RF06 - painel administrativo, separado do restante da plataforma (feed, portfolios etc).
class AdminController
{
    public function __construct(
        private readonly AuditoriaService $auditoriaService = new AuditoriaService(),
        private readonly AuditoriaRepository $auditoriaRepository = new AuditoriaRepository(),
        private readonly UserRepository $usuarios = new UserRepository(),
        private readonly ProfissionalRepository $profissionais = new ProfissionalRepository(),
        private readonly DenunciaRepository $denuncias = new DenunciaRepository()
    ) {
    }

    // Visao geral com indicadores gerenciais dinamicos da plataforma.
    public function dashboard(Request $request): void
    {
        Response::json(['data' => $this->auditoriaService->relatorioGerencial()]);
    }

    // Gestao de perfis: lista usuarios (?perfil= filtra) e a fila de profissionais
    // com registro Confea/Crea pendente de validacao pelo ADMIN_CREA.
    public function manageUsers(Request $request): void
    {
        $perfil = $request->input('perfil');
        $usuarios = $this->usuarios->all($perfil !== null ? (string) $perfil : null);

        $pendentesValidacao = array_values(array_filter(
            $this->profissionais->all(),
            fn ($p) => !$p->registroValidado
        ));

        Response::json([
            'usuarios' => array_map($this->usuarioParaArray(...), $usuarios),
            'profissionais_pendentes_validacao' => $pendentesValidacao,
        ]);
    }

    // Ativa ou desativa a conta de um usuario. A autorizacao de registro profissional
    // em si (Confea/Crea) permanece em ProfissionalController::validar.
    public function toggleUserStatus(Request $request): void
    {
        $id = (int) $request->input('id');
        $ativo = (bool) $request->input('ativo', true);

        $user = $this->usuarios->findById($id);

        if ($user === null) {
            Response::json(['message' => 'Usuário não encontrado.'], 404);
            return;
        }

        $user->contaAtiva = $ativo;
        $this->usuarios->save($user);

        $this->auditoriaService->registrar(
            auth_id(),
            $ativo ? 'usuario.ativado' : 'usuario.desativado',
            ['id' => $id]
        );

        Response::json(['message' => $ativo ? 'Conta ativada.' : 'Conta desativada.']);
    }

    // Fila de moderacao: denuncias ainda nao finalizadas (pendentes ou em analise).
    public function moderation(Request $request): void
    {
        $fila = array_values(array_filter(
            $this->denuncias->all(),
            fn ($d) => in_array($d->statusDenuncia, [Denuncia::STATUS_PENDENTE, Denuncia::STATUS_EM_ANALISE], true)
        ));

        Response::json(['data' => $fila]);
    }

    // Consulta aos logs de auditoria. Aceita ?usuario_id= ou ?tabela=&registro_id=
    // para restringir a consulta; sem filtro, retorna o feed global mais recente.
    public function auditLogs(Request $request): void
    {
        $usuarioId = $request->input('usuario_id');
        $tabela = $request->input('tabela');
        $registroId = $request->input('registro_id');

        $logs = match (true) {
            $tabela !== null && $registroId !== null
                => $this->auditoriaRepository->historico((string) $tabela, (int) $registroId),
            $usuarioId !== null => $this->auditoriaRepository->listByUsuario((int) $usuarioId),
            default => $this->auditoriaRepository->listAll(),
        };

        Response::json(['data' => $logs]);
    }

    // Remove dados sensiveis (senha_hash) antes de expor o usuario via JSON.
    private function usuarioParaArray(User $user): array
    {
        return [
            'id' => $user->id,
            'nome' => $user->nome,
            'email' => $user->email,
            'tipo_pessoa' => $user->tipoPessoa,
            'perfil_acesso' => $user->perfilAcesso,
            'conta_ativa' => $user->contaAtiva,
            'criado_em' => $user->criadoEm,
        ];
    }
}
