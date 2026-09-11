<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `profissionais`: dados do profissional registrado no Confea/Crea.
// PK = id do usuario (relacao 1:1 com `pessoa_fisica`).
class Profissional
{
    public const GRAU_TECNOLOGO = 'TECNOLOGO';
    public const GRAU_GRADUACAO = 'GRADUACAO';
    public const GRAU_POS_GRADUACAO = 'POS_GRADUACAO';
    public const GRAU_MESTRADO = 'MESTRADO';
    public const GRAU_DOUTORADO = 'DOUTORADO';
    public const GRAU_POS_DOUTORADO = 'POS_DOUTORADO';

    public function __construct(
        public int $idUsuario = 0,
        public string $numeroRegistroConfeaCrea = '',
        public string $categoriaProfissional = '',
        public ?int $anosExperiencia = null,
        public ?int $empresaAtualId = null,
        public string $grauAcademico = self::GRAU_GRADUACAO,
        public bool $registroValidado = false,
        public ?string $registroValidadoEm = null,
        public bool $registroAtivo = true,
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
    ) {
    }
}
