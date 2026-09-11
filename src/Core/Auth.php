<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

// Autenticacao baseada em sessao com cookie httpOnly (Anexo I, item 8.5-a/b).
class Auth
{
    // Configura os parametros do cookie de sessao. Deve ser chamado ANTES de session_start().
    public static function configureSessionCookie(): void
    {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (bool) config('security.session_secure_cookie'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    // Gera o hash seguro de uma senha em texto plano.
    public static function hashPassword(string $plainPassword): string
    {
        return password_hash($plainPassword, (int) config('security.password_algo'));
    }

    // Verifica a senha em texto plano contra o hash armazenado no banco.
    public static function verifyPassword(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    // Abre a sessao autenticada apos as credenciais terem sido validadas.
    // session_regenerate_id evita fixacao de sessao em cada novo login.
    public static function login(User $user): void
    {
        $_SESSION['user'] = [
            'id' => $user->id,
            'nome' => $user->nome,
            'email' => $user->email,
            // Mantem a chave 'perfil' na sessao (lida por RoleMiddleware); valor
            // vem da coluna `perfil_acesso` do estrutura.sql.
            'perfil' => $user->perfilAcesso,
        ];
        session_regenerate_id(true);
    }

    // Encerra a sessao autenticada.
    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
