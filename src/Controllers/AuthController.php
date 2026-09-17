<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\PasswordResetToken;
use App\Models\PessoaFisica;
use App\Repositories\PasswordResetTokenRepository;
use App\Repositories\ProfissionalRepository;
use App\Repositories\UniversidadeRepository;
use App\Repositories\UniversitarioRepository;
use App\Repositories\UserRepository;
use App\Repositories\PessoaFisicaRepository;
use App\Repositories\PessoaJuridicaRepository;
use App\Services\CreaApiService;
use App\Services\FileUploadService;
use App\Services\NotificacaoService;

use DateTime;

// RF01 - autenticacao, cadastro e recuperacao de acesso dos 6 perfis de usuario.
class AuthController
{
    // MIME real (via fileinfo) -> extensao aceita para o comprovante de matricula.
    private const COMPROVANTE_MIMES_PERMITIDOS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    private const COMPROVANTE_TAMANHO_MAXIMO_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private readonly UserRepository $userRepository = new UserRepository(),
        private readonly CreaApiService $creaApiService = new CreaApiService(),
        private readonly PessoaFisicaRepository $pessoaFisicaRepository = new PessoaFisicaRepository(),
        private readonly PessoaJuridicaRepository $pessoaJuridicaRepository = new PessoaJuridicaRepository(),
        private readonly ProfissionalRepository $profissionalRepository = new ProfissionalRepository(),
        private readonly UniversitarioRepository $universitarioRepository = new UniversitarioRepository(),
        private readonly UniversidadeRepository $universidadeRepository = new UniversidadeRepository(),
        private readonly PasswordResetTokenRepository $passwordResetTokenRepository = new PasswordResetTokenRepository(),
        private readonly NotificacaoService $notificacaoService = new NotificacaoService()
    ) {
    }

    // Exibe o formulario de login.
    public function showLogin(Request $request): void
    {
        (new View())->render('auth/login');
    }

    // Fornece o token CSRF da sessao atual em JSON, para clientes SPA que nao
    // renderizam os formularios via View (Anexo I, item 8.5-a).
    public function csrfToken(Request $request): void
    {
        Response::json(['csrf_token' => csrf_token()]);
    }

    // Valida credenciais (Core\Auth) e abre a sessao do usuario.
    public function login(Request $request): void
    {
        $user = $this->userRepository->findByUsername((string) $request->input('username'));
        $identifier = (string) ($request->input('username') ?? $request->input('email'));
        $password = (string) ($request->input('password') ?? $request->input('senha'));

        $user = $this->userRepository->findByEmail($identifier);

        if ($user === null || !Auth::verifyPassword((string) $request->input('password'), $user->senhaHash));
        if ($user === null || !Auth::verifyPassword($password, $user->senhaHash)) {
            Response::json(['message' => 'Credenciais invalidas.'], 401);
            return;
        }

        Auth::login($user);
        Response::json([
            'message' => 'Logado com sucesso',
            'user' => [
                'id' => $user->id,
                'nome' => $user->nome,
                'email' => $user->email,
                'perfil' => $user->perfilAcesso
            ]
        ]);
    }

    // Cria um novo usuario em um dos 6 perfis, com aceite de Termos de Uso/Politica de Privacidade.
    public function register(Request $request): void
    {
        $nome = (string) ($request->input('name') ?? $request->input('nome'));
        $email = (string) $request->input('email');
        $senha = (string) ($request->input('password') ?? $request->input('senha'));
        $telefone = (string) ($request->input('phone') ?? $request->input('telefone'));

        $profileTypeHtml = (string) $request->input('profile_type');

        // Mapeamento do tipo de pessoa baseado no profile_type
        // No banco de dados, o campo é ENUM('FISICA', 'JURIDICA')
        $tipoPessoa = ($profileTypeHtml === 'empresa') ? 'JURIDICA' : 'FISICA';

        // Mapeamento do Perfil de Acesso baseado no HTML Select
        // No banco de dados, o campo é ENUM('USUARIO', 'ADMIN_CREA')
        $perfilAcesso = 'USUARIO';

        // Mapeamento do tipo de conta (usuarios.tipo_conta), usado para exibicao no
        // feed e regras de acesso - antes ficava sempre em COMUM (default do model).
        $tipoConta = match ($profileTypeHtml) {
            'profissional' => \App\Models\User::TIPO_CONTA_PROFISSIONAL,
            'universitario' => \App\Models\User::TIPO_CONTA_ESTUDANTE,
            'empresa' => \App\Models\User::TIPO_CONTA_EMPRESA,
            default => \App\Models\User::TIPO_CONTA_COMUM,
        };

        // O formulario de cadastro tem um unico campo "document_number" para CPF/CNPJ,
        // reaproveitado conforme o tipo de pessoa (cadastreSe.html troca o label/placeholder).
        $documentNumber = (string) $request->input('document_number', '');
        $cpf = $tipoPessoa === 'FISICA' ? $documentNumber : '';
        $cnpj = $tipoPessoa === 'JURIDICA' ? $documentNumber : '';

        if (empty($nome) || empty($email) || empty($senha)) {
            Response::json(['message' => 'Nome, e-mail e senha são obrigatórios.'], 400);
            return;
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            Response::json(['message' => 'E-mail já está em uso.'], 409);
            return;
        }

        // Erro caso o CPF/CNPJ já esteja cadastrado.

        if ($cpf !== '' && $this->pessoaFisicaRepository->findByCpf($cpf)) {
            Response::json(['message' => 'CPF já está em uso.', 409]);
            return;
        }

        if ($cnpj !== '' && $this->pessoaJuridicaRepository->findByCnpj($cnpj)) {
            Response::json(['message' => 'CNPJ já está em uso.', 409]);
            return;
        }

        $user = new \App\Models\User(
            id: null,
            nome: $nome,
            email: $email,
            senhaHash: Auth::hashPassword($senha),
            telefone: $telefone,
            tipoPessoa: $tipoPessoa,
            perfilAcesso: $perfilAcesso,
            tipoConta: $tipoConta,
            contaAtiva: true,
            ultimoLoginEm: null,
            tentativasLogin: 0,
            bloqueadoAte: null,
            criadoEm: null,
            atualizadoEm: null
        );

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $userId = $this->userRepository->save($user);

            if ($tipoPessoa === 'FISICA') {
                $this->pessoaFisicaRepository->save(new PessoaFisica(
                    idUsuario: $userId,
                    cpf: $cpf
                ));
            }

            $this->attachProfile($userId, $profileTypeHtml, $request);

            $pdo->commit();
        } catch (\InvalidArgumentException $e) {
            $pdo->rollBack();
            Response::json(['message' => $e->getMessage()], 400);
            return;
        } catch (\RuntimeException $e) {
            // Ex: falha de I/O ao salvar o comprovante de matricula (FileUploadService).
            $pdo->rollBack();
            Response::json(['message' => $e->getMessage()], 500);
            return;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Response::json(['message' => 'Cadastro realizado com sucesso', 'user_id' => $userId], 201);
    }

    public function promote(Request $request): void
    {
        $userId = (int) $request->user()['id'];
        $tipoContaAlvo = (string) $request->input('profile_type');

        //TODO checar se o usuário já tem esse perfil, para não duplicar

        try {
            $this->attachProfile($userId, $tipoContaAlvo, $request);
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage()], 400);
            return;
        } catch (\RuntimeException $e) {
            Response::json(['message' => $e->getMessage()], 500);
            return;
        }

        Response::json(['message' => 'Perfil atualizado com sucesso'], 201);
    }

    private function attachProfile(int $userId, string $tipoPerfil, Request $request): void
    {
        match ($tipoPerfil) {
            'profissional' => $this->createProfissionalPerfil($userId, $request),
            'universitario' => $this->createUniversitarioPerfil($userId, $request),
            'empresa' => $this->createPessoaJuridicaPerfil($userId, $request),
            default => null,
        };
    }

    private function createProfissionalPerfil(int $userId, Request $request): void
    {
        $userProfissional = new \App\Models\Profissional(
            idUsuario: $userId,
            numeroRegistroConfeaCrea: (string) $request->input('numero_registro_confea_crea', ''),
            categoriaProfissional: (string) $request->input('categoria_profissional', ''),
            anosExperiencia: (int) $request->input('anos_experiencia') ?: null,
            grauAcademico: (string) $request->input('grau_academico'),
        );
        $this->profissionalRepository->save($userProfissional);
    }

    private function createUniversitarioPerfil(int $userId, Request $request): void
    {
        //valida o formato da data recebida
        $dataRecebida = (string) $request->input('previsao_formatura');
        $data = DateTime::createFromFormat('Y-m-d', $dataRecebida);
        $valida = $data && $data->format('Y-m-d') === $dataRecebida;
        if (!$valida) Response::json(['message' => 'Data inválida']);

        $userUniversitario = new \App\Models\Universitario(
            idUsuario: $userId,
            universidadeId: (int) $request->input('universidade_id', 0),
            curso: (string) $request->input('curso', ''),
            grau_academico: (string) $request->input('student_level', 'GRADUACAO'),
            matricula: (string) $request->input('matricula') ?: null,
            semestreAtual: (int) $request->input('semestre_atual') ?: null,
            previsaoFormatura: $valida ? $dataRecebida : null,
            comprovanteMatricula: $this->storeComprovanteMatricula($request->file('comprovante_matricula')),
        );
        $this->universitarioRepository->save($userUniversitario);
    }

    // Retorna null se nao houver arquivo (campo opcional); lanca InvalidArgumentException
    // se um arquivo foi enviado mas e invalido, para o cadastro ser bloqueado com 400.
    private function storeComprovanteMatricula(?array $file): ?string
    {
        $uploadService = new FileUploadService(
            self::COMPROVANTE_MIMES_PERMITIDOS,
            self::COMPROVANTE_TAMANHO_MAXIMO_BYTES
        );

        return $uploadService->store($file, 'comprovantes_matricula')['caminho_armazenamento'] ?? null;
    }

    private function createPessoaJuridicaPerfil(int $userId, Request $request): void
    {
        $userEmpresa = new \App\Models\PessoaJuridica(
            idUsuario: $userId,
            cnpj: (string) $request->input('document_number', ''),
            razaoSocial: (string) $request->input('name', ''),
            nomeFantasia: (string) $request->input('nome_fantasia') ?: null,
        );
        $this->pessoaJuridicaRepository->save($userEmpresa);
    }

    // Encerra a sessao atual.
    public function logout(Request $request): void
    {
        Auth::logout();
        Response::json(['message' => 'Logout concluído']);
    }

    // Processa o pedido de recuperacao de senha: gera um token de uso unico e
    // dispara o e-mail com o link de redefinicao via SMTP (RF07).
    public function recoverPassword(Request $request): void
    {
        $email = (string) $request->input('email');
        if (empty($email)) {
            Response::json(['message' => 'E-mail obrigatório'], 400);
            return;
        }

        $user = $this->userRepository->findByEmail($email);

        // A resposta e sempre a mesma independente do e-mail existir, para nao
        // permitir que terceiros descubram quais e-mails estao cadastrados.
        if ($user !== null) {
            $tokenPlano = bin2hex(random_bytes(32));

            $this->passwordResetTokenRepository->save(new PasswordResetToken(
                idUsuario: (int) $user->id,
                tokenHash: hash('sha256', $tokenPlano),
                expiraEm: date('Y-m-d H:i:s', time() + 1800),
            ));

            $link = rtrim((string) config('app.url'), '/') . '/reset-password?token=' . $tokenPlano;

            $this->notificacaoService->enviarEmail(
                $user->email,
                'Redefinição de senha - Pro-Link',
                sprintf(
                    '<p>Olá, %s.</p>'
                    . '<p>Recebemos um pedido de redefinição de senha para esta conta. '
                    . 'O link abaixo é válido por 30 minutos:</p>'
                    . '<p><a href="%s">%s</a></p>'
                    . '<p>Se você não solicitou essa alteração, ignore este e-mail.</p>',
                    htmlspecialchars($user->nome),
                    $link,
                    $link
                )
            );
        }

        Response::json(['message' => 'Se o e-mail existir, um link de recuperação foi enviado.']);
    }

    // Consome o token enviado por e-mail e grava a nova senha do usuario.
    public function resetPassword(Request $request): void
    {
        $token = (string) $request->input('token');
        $novaSenha = (string) ($request->input('password') ?? $request->input('senha'));

        if (empty($token) || empty($novaSenha)) {
            Response::json(['message' => 'Token e nova senha são obrigatórios.'], 400);
            return;
        }

        $resetToken = $this->passwordResetTokenRepository->findValidoByHash(hash('sha256', $token));
        $user = $resetToken !== null ? $this->userRepository->findById($resetToken->idUsuario) : null;

        if ($resetToken === null || $user === null) {
            Response::json(['message' => 'Token inválido ou expirado.'], 400);
            return;
        }

        $user->senhaHash = Auth::hashPassword($novaSenha);
        $this->userRepository->save($user);
        $this->passwordResetTokenRepository->marcarUsado((int) $resetToken->id);

        Response::json(['message' => 'Senha redefinida com sucesso.']);
    }
}
