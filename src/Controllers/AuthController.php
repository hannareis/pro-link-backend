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
        $user = $this->userRepository->findByEmail((string) $request->input('email'));

        if ($user === null || !Auth::verifyPassword((string) $request->input('senha'), $user->senhaHash)) {
            Response::json(['message' => 'Credenciais invalidas.'], 401);
            return;
        }

        Auth::login($user);
        Response::redirect('/feed');
    }

    // Cria um novo usuario em um dos 6 perfis, com aceite de Termos de Uso/Politica de Privacidade.
    public function register(Request $request): void
    {
        // RF01 - CRUD dos 6 perfis + aceite de Termos de Uso / Politica de Privacidade
        // Depende de UserRepository::save() (persistencia), ainda pendente de implementacao.
    }

    // Encerra a sessao atual.
    public function logout(Request $request): void
    {
        Auth::logout();
        Response::redirect('/login');
    }
}
