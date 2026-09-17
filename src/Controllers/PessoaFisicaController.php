<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Models\User;
use App\Repositories\PessoaFisicaRepository;
use App\Repositories\PortfolioRepository;
use App\Repositories\ProfissionalRepository;
use App\Repositories\UserRepository;
use App\Services\CreaApiService;



class PessoaFisicaController
{
    public function __construct(
        private readonly UserRepository $userRepository = new UserRepository(),
        private readonly PessoaFisicaRepository $pessoaFisicaRepository = new PessoaFisicaRepository()
    ) {

    }

    public function show(Request $request): void
    {
        $userId = auth_id();
        $user = $this->userRepository->findById($userId) ?: null;

        if ($user === null) Response::json(['message' => 'Usuario nao encontrado.', 404]);

        $pf = $this->pessoaFisicaRepository->findByUsuarioId($userId);

        if ($pf === null) Response::json(['message' => 'PF nao encontrada.', 404]);

        Response::json([
            'usuario' => [
                'cpf' => $pf->cpf
            ]
        ]);
    }
}