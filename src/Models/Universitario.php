<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `universitarios`: dados academicos de uma pessoa fisica matriculada
// numa universidade. PK = id do usuario (relacao 1:1 com `pessoa_fisica`).
class Universitario
{
    public function __construct(
        public int $idUsuario = 0,
        public int $universidadeId = 0,
        public string $curso = '',
        public ?string $grau_academico = null, // Luan: faltava adicionar o grau acadêmico
        public ?string $matricula = null,
        public ?int $semestreAtual = null,
        public ?string $previsaoFormatura = null,
        public ?string $comprovanteMatricula = null,
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
    ) {
    }
}
