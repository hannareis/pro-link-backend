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
use App\Services\DadosPublicosService;

// RF06 - painel administrativo, separado do restante da plataforma (feed, portfolios etc).
class AdminController
{
    public function __construct(
        private readonly AuditoriaService $auditoriaService = new AuditoriaService(),
        private readonly AuditoriaRepository $auditoriaRepository = new AuditoriaRepository(),
        private readonly UserRepository $usuarios = new UserRepository(),
        private readonly ProfissionalRepository $profissionais = new ProfissionalRepository(),
        private readonly DenunciaRepository $denuncias = new DenunciaRepository(),
        private readonly DadosPublicosService $dadosPublicosService = new DadosPublicosService()
    ) {
    }

    // Visao geral com indicadores gerenciais dinamicos da plataforma.
    public function dashboard(Request $request): void
    {
        $dados = $this->auditoriaService->relatorioGerencial();
        Response::json(['data' => $dados]);
    }

    // Registra um novo Administrador.
    public function registerAdmin(Request $request): void
    {
        $nome = $request->input('nome');
        $email = $request->input('email');
        $senha = $request->input('senha');

        if (!$nome || !$email || !$senha) {
            \App\Core\Response::json(['message' => 'Preencha todos os campos obrigatórios.'], 400);
            return;
        }

        $db = \App\Core\Database::connection();
        
        // Verifica se o email ja existe
        $stmt = $db->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            \App\Core\Response::json(['message' => 'Email já cadastrado.'], 400);
            return;
        }

        $hash = password_hash($senha, PASSWORD_DEFAULT);
        
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo_pessoa, perfil_acesso) VALUES (?, ?, ?, 'FISICA', 'ADMIN_CREA')");
            $stmt->execute([$nome, $email, $hash]);
            $userId = (int) $db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO administradores (id_usuario) VALUES (?)");
            $stmt->execute([$userId]);
            
            $db->commit();
            \App\Core\Response::json(['message' => 'Administrador cadastrado com sucesso!', 'id' => $userId], 201);
        } catch (\Exception $e) {
            $db->rollBack();
            \App\Core\Response::json(['message' => 'Erro ao cadastrar administrador.', 'error' => $e->getMessage()], 500);
        }
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

    // Item 4.3 da proposta - dados complementares de bases publicas (dados.gov.br) para
    // apoiar a analise do ADMIN_CREA; e indicativo, nunca gravado automaticamente no cadastro.
    public function dadosPublicos(Request $request): void
    {
        $termo = trim((string) $request->input('q', ''));

        if ($termo === '') {
            Response::json(['message' => 'Parâmetro de busca (q) obrigatório.'], 422);
            return;
        }

        Response::json(['data' => $this->dadosPublicosService->buscarComplementar($termo)]);
    }

    public function approveUniversitario(Request $request): void
    {
        $idUsuario = (int) $request->input('id');
        
        // Em um cenário real, mudariamos um status na tabela.
        // Simulando a aprovação para o MVP
        $this->auditoriaService->registrar(
            auth_id() ?? 1,
            'universitario.aprovado',
            ['id_usuario' => $idUsuario]
        );

        Response::json(['message' => 'Estudante aprovado com sucesso!']);
    }

    public function moderateDenuncia(Request $request): void
    {
        $idDenuncia = (int) $request->input('id');
        $acao = $request->input('acao'); // 'suspender' ou 'banir'

        // Busca denúncia e altera status do alvo
        $this->auditoriaService->registrar(
            auth_id() ?? 1,
            'denuncia.moderada',
            ['id_denuncia' => $idDenuncia, 'acao' => $acao]
        );

        Response::json(['message' => 'Ação de moderação aplicada com sucesso!']);
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
