<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\UserRepository;
use App\Services\CreaApiService;

// RF01 - autenticacao, cadastro e recuperacao de acesso dos 6 perfis de usuario.
class AuthController
{
    public function __construct(
        private readonly UserRepository $userRepository = new UserRepository(),
        private readonly CreaApiService $creaApiService = new CreaApiService()
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
        $identifier = (string) ($request->input('username') ?? $request->input('email'));
        $password = (string) ($request->input('password') ?? $request->input('senha'));

        $user = $this->userRepository->findByEmail($identifier);

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

        if (empty($nome) || empty($email) || empty($senha)) {
            Response::json(['message' => 'Nome, e-mail e senha são obrigatórios.'], 400);
            return;
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            Response::json(['message' => 'E-mail já está em uso.'], 409);
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

        Response::json(['message' => 'Cadastro realizado com sucesso', 'user_id' => $userId], 201);
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
