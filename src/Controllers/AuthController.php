<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\PessoaFisica;
use App\Repositories\ProfissionalRepository;
use App\Repositories\UniversidadeRepository;
use App\Repositories\UniversitarioRepository;
use App\Repositories\UserRepository;
use App\Repositories\PessoaFisicaRepository;
use App\Repositories\PessoaJuridicaRepository;
use App\Services\CreaApiService;

use DateTime;

// RF01 - autenticacao, cadastro e recuperacao de acesso dos 6 perfis de usuario.
class AuthController
{
    public function __construct(
        private readonly UserRepository $userRepository = new UserRepository(),
        private readonly CreaApiService $creaApiService = new CreaApiService(),
        private readonly PessoaFisicaRepository $pessoaFisicaRepository = new PessoaFisicaRepository(),
        private readonly PessoaJuridicaRepository $pessoaJuridicaRepository = new PessoaJuridicaRepository(),
        private readonly ProfissionalRepository $profissionalRepository = new ProfissionalRepository(),
        private readonly UniversitarioRepository $universitarioRepository = new UniversitarioRepository(),
        private readonly UniversidadeRepository $universidadeRepository = new UniversidadeRepository()
    ) {
    }

    // Exibe o formulario de login.
    public function showLogin(Request $request): void
    {
        (new View())->render('auth/login');
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
        $cpf = (string) $request->input('cpf', '');
        $cnpj = (string) $request->input('cnpj', '');
        
        $profileTypeHtml = (string) $request->input('profile_type');
        
        // Mapeamento do tipo de pessoa baseado no profile_type
        // No banco de dados, o campo é ENUM('FISICA', 'JURIDICA')
        $tipoPessoa = ($profileTypeHtml === 'empresa') ? 'JURIDICA' : 'FISICA';
        
        // Mapeamento do Perfil de Acesso baseado no HTML Select
        // No banco de dados, o campo é ENUM('USUARIO', 'ADMIN_CREA')
        $perfilAcesso = 'USUARIO';

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
            contaAtiva: true,
            ultimoLoginEm: null,
            tentativasLogin: 0,
            bloqueadoAte: null,
            criadoEm: null,
            atualizadoEm: null
        );

        $userId = $this->userRepository->save($user);

        if ($tipoPessoa === 'FISICA') {
            $this->pessoaFisicaRepository->save(new PessoaFisica(
                idUsuario: $userId,
                cpf: $cpf
            ));
        }

        $this->attachProfile($userId, $profileTypeHtml, $request);

        Response::json(['message' => 'Cadastro realizado com sucesso', 'user_id' => $userId], 201);
    }

    public function promote(Request $request): void
    {
        $userId = (int) $request->user()['id'];
        $tipoContaAlvo = (string) $request->input('profile_type');

        //TODO checar se o usuário já tem esse perfil, para não duplicar

        $this->attachProfile($userId, $tipoContaAlvo, $request);

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
            numeroRegistroConfeaCrea: (string) $request->input('crea-record', ''),
            categoriaProfissional: (string) $request->input('categoria_profissional', ''),
            anosExperiencia: (int) $request->input('anos_experiencia') ?: null,
            grauAcademico: (string) $request->input('grau_academico'),
        );
        $this->profissionalRepository->save($userProfissional);
    }

    private function createUniversitarioPerfil(int $userId, Request $request): void
    {
        $universidade = (string) $request->input('institution_ensino');

        //valida o formato da data recebida
        $dataRecebida = (string) $request->input('previsao_formatura');
        $data = DateTime::createFromFormat('Y-m-d', $dataRecebida);
        $valida = $data && $data->format('Y-m-d') === $dataRecebida;
        if (!$valida) Response::json(['message' => 'Data inválida']);

        $userUniversitario = new \App\Models\Universitario(
            idUsuario: $userId,
            universidadeId: $this->universidadeRepository->findByNome($universidade)?->id ?? $this->universidadeRepository->findBySigla($universidade)?->id ?? 0,
            curso: (string) $request->input('student_modality', ''),
            matricula: (string) $request->input('student_ra') ?: null,
            semestreAtual: (int) $request->input('semestre_atual') ?: null,
            previsaoFormatura: $valida ? $dataRecebida : null
        );
        $this->universitarioRepository->save($userUniversitario);
    }

    private function createPessoaJuridicaPerfil(int $userId, Request $request): void
    {
        $userEmpresa = new \App\Models\PessoaJuridica(
            idUsuario: $userId,
            cnpj: (string) $request->input('cnpj', ''),
            razaoSocial: (string) $request->input('razao_social', ''),
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

    // Processa o pedido de recuperacao de senha.
    public function recoverPassword(Request $request): void
    {
        $email = (string) $request->input('email');
        if (empty($email)) {
            Response::json(['message' => 'E-mail obrigatório'], 400);
            return;
        }
        
        // Em produção, isso dispararia um e-mail com link de recuperação.
        // Aqui simulamos o sucesso da operação.
        Response::json(['message' => 'Se o e-mail existir, um link de recuperação foi enviado.']);
    }
}
