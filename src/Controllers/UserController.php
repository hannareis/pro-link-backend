<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Validator;
use App\Models\User;
use App\Repositories\PessoaFisicaRepository;
use App\Repositories\UserRepository;
use App\Services\CreaApiService;

// RF01 - gestao de dados cadastrais e privacidade do usuario.
class UserController
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly PessoaFisicaRepository $pessoasFisicas = new PessoaFisicaRepository(),
        private readonly CreaApiService $creaApiService = new CreaApiService()
    ) {
    }

    // Lista usuarios cadastrados (restrito ao ADMIN_CREA via RoleMiddleware). Aceita ?perfil=.
    public function index(Request $request): void
    {
        $perfil = $request->input('perfil');
        $usuarios = $this->users->all($perfil !== null ? (string) $perfil : null);

        Response::json(['data' => array_map($this->toPublicArray(...), $usuarios)]);
    }

    // Exibe o perfil publico ou o perfil do usuario autenticado.
    public function show(Request $request): void
    {
        $id = (int) $request->input('id', auth_id());

        if ($id === 0) {
            Response::json(['message' => 'Usuário não encontrado.'], 404);
            return;
        }

        $user = $this->users->findById($id);

        if ($user === null) {
            Response::json(['message' => 'Usuário não encontrado.'], 404);
            return;
        }

        $ehProprioUsuario = $id === auth_id();
        $ehAdmin = (auth_user()['perfil'] ?? null) === User::PERFIL_ADMIN_CREA;

        if (!$ehProprioUsuario && !$ehAdmin && !$this->ehVisivelPublicamente($user)) {
            Response::json(['message' => 'Perfil não disponível.'], 403);
            return;
        }

        Response::json(['data' => $this->toPublicArray($user)]);
    }

    // Atualiza dados cadastrais e preferencias de visibilidade/privacidade.
    public function update(Request $request): void
    {
        $id = auth_id();

        if ($id === 0) {
            Response::json(['message' => 'Sessão inválida.'], 401);
            return;
        }

        $user = $this->users->findById($id);

        if ($user === null) {
            Response::json(['message' => 'Usuário não encontrado.'], 404);
            return;
        }

        $novoEmail = trim((string) $request->input('email', $user->email));

        if ($novoEmail !== $user->email) {
            if (!Validator::isValidEmail($novoEmail)) {
                Response::json(['message' => 'E-mail inválido.'], 422);
                return;
            }

            if ($this->users->findByEmail($novoEmail) !== null) {
                Response::json(['message' => 'E-mail já está em uso.'], 409);
                return;
            }

            $user->email = $novoEmail;
        }

        $user->nome = trim((string) $request->input('nome', $user->nome));
        $user->telefone = trim((string) $request->input('telefone', $user->telefone));

        $novaSenha = (string) $request->input('password', '');
        if ($novaSenha !== '') {
            $user->senhaHash = Auth::hashPassword($novaSenha);
        }

        $this->users->save($user);

        Response::json(['message' => 'Dados atualizados com sucesso.', 'data' => $this->toPublicArray($user)]);
    }

    // Desativa (exclusao logica) a conta do usuario autenticado.
    public function destroy(Request $request): void
    {
        $id = auth_id();

        if ($id === 0) {
            Response::json(['message' => 'Sessão inválida.'], 401);
            return;
        }

        $this->users->desativar($id);
        Auth::logout();

        Response::json(['message' => 'Conta desativada com sucesso.']);
    }

    // Exibe o estado atual das preferencias de privacidade (LGPD) do usuario autenticado.
    public function privacySettings(Request $request): void
    {
        $id = auth_id();

        if ($id === 0) {
            Response::json(['message' => 'Sessão inválida.'], 401);
            return;
        }

        $user = $this->users->findById($id);

        if ($user === null) {
            Response::json(['message' => 'Usuário não encontrado.'], 404);
            return;
        }

        $pessoaFisica = $this->pessoasFisicas->findByUsuarioId($id);

        Response::json([
            'tipo_pessoa' => $user->tipoPessoa,
            // Apenas pessoa fisica possui a preferencia de visibilidade publica do perfil;
            // conta JURIDICA e sempre publica.
            'visibilidade_publica' => $pessoaFisica?->visibilidadePublica ?? true,
        ]);
    }

    // Atualiza a preferencia de visibilidade publica do perfil (consentimento LGPD).
    public function updatePrivacySettings(Request $request): void
    {
        $id = auth_id();

        if ($id === 0) {
            Response::json(['message' => 'Sessão inválida.'], 401);
            return;
        }

        $pessoaFisica = $this->pessoasFisicas->findByUsuarioId($id);

        if ($pessoaFisica === null) {
            Response::json(['message' => 'Preferência de visibilidade disponível apenas para pessoas físicas.'], 422);
            return;
        }

        $pessoaFisica->visibilidadePublica = (bool) $request->input(
            'visibilidade_publica',
            $pessoaFisica->visibilidadePublica
        );
        $this->pessoasFisicas->save($pessoaFisica);

        Response::json(['message' => 'Preferências de privacidade atualizadas.']);
    }

    // Conta JURIDICA e sempre publica; conta FISICA respeita `visibilidade_publica`.
    private function ehVisivelPublicamente(User $user): bool
    {
        if ($user->tipoPessoa === User::TIPO_PESSOA_JURIDICA) {
            return true;
        }

        return $this->pessoasFisicas->findByUsuarioId((int) $user->id)?->visibilidadePublica ?? true;
    }

    // Remove dados sensiveis (senha_hash) antes de expor o usuario via JSON.
    private function toPublicArray(User $user): array
    {
        return [
            'id' => $user->id,
            'nome' => $user->nome,
            'email' => $user->email,
            'telefone' => $user->telefone,
            'tipo_pessoa' => $user->tipoPessoa,
            'perfil_acesso' => $user->perfilAcesso,
            'conta_ativa' => $user->contaAtiva,
            'criado_em' => $user->criadoEm,
        ];
    }
}
