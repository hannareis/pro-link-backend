<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `administradores`: usuarios com perfil_acesso = ADMIN_CREA que atuam
// na moderacao/validacao. Relacao 1:1 com `usuarios`.
class Administrador
{
    public function __construct(
        public int $idUsuario = 0,
        public ?string $criadoEm = null,
    ) {
    }
}
