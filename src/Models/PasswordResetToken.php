<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `tokens_redefinicao_senha` do estrutura.sql: token de uso unico para
// redefinicao de senha (RF01), enviado por e-mail via NotificacaoService (RF07).
class PasswordResetToken
{
    public function __construct(
        public ?int $id = null,
        public int $idUsuario = 0,
        public string $tokenHash = '',
        public string $expiraEm = '',
        public ?string $usadoEm = null,
        public ?string $criadoEm = null,
    ) {
    }

    public function estaExpirado(): bool
    {
        return strtotime($this->expiraEm) <= time();
    }

    public function foiUsado(): bool
    {
        return $this->usadoEm !== null;
    }
}
