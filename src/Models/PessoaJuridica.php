<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `pessoa_juridica`: dados exclusivos de um usuario tipo_pessoa = JURIDICA
// (empresas). Relacao 1:1 com `usuarios`.
class PessoaJuridica
{
    public function __construct(
        public int $idUsuario = 0,
        public string $cnpj = '',
        public string $razaoSocial = '',
        public ?string $nomeFantasia = null,
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
    ) {
    }
}
