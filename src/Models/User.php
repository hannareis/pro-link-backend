<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `usuarios` do estrutura.sql: a conta de acesso (login), especializada
// por `pessoa_fisica` ou `pessoa_juridica` conforme `tipo_pessoa`.
class User
{
    // Coluna `tipo_pessoa`.
    public const TIPO_PESSOA_FISICA = 'FISICA';
    public const TIPO_PESSOA_JURIDICA = 'JURIDICA';

    // Coluna `perfil_acesso`.
    public const PERFIL_USUARIO = 'USUARIO';
    public const PERFIL_ADMIN_CREA = 'ADMIN_CREA';
    // Alias retrocompativel com rotas/middleware que ainda usam PERFIL_ADMIN.
    public const PERFIL_ADMIN = self::PERFIL_ADMIN_CREA;

    public function __construct(
        public ?int $id = null,
        public string $nome = '',
        public string $email = '',
        public string $senhaHash = '',
        public string $telefone = '',
        public string $tipoPessoa = self::TIPO_PESSOA_FISICA,
        public string $perfilAcesso = self::PERFIL_USUARIO,
        public bool $contaAtiva = true,
        public ?string $ultimoLoginEm = null,
        public int $tentativasLogin = 0,
        public ?string $bloqueadoAte = null,
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
    ) {
    }

    // Conta temporariamente bloqueada por excesso de tentativas de login.
    public function estaBloqueado(): bool
    {
        return $this->bloqueadoAte !== null && strtotime($this->bloqueadoAte) > time();
    }

    public function isAdmin(): bool
    {
        return $this->perfilAcesso === self::PERFIL_ADMIN_CREA;
    }
}
