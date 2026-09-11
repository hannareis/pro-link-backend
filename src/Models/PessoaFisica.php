<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `pessoa_fisica`: dados exclusivos de um usuario tipo_pessoa = FISICA.
// A PK e o proprio id do usuario (relacao 1:1 com `usuarios`).
class PessoaFisica
{
    public function __construct(
        public int $idUsuario = 0,
        public string $cpf = '',
        public bool $visibilidadePublica = true,
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
    ) {
    }
}
