<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `sis_auditoria`: registro imutavel de alteracoes em dados criticos
// (antes/depois em JSON), para rastreabilidade exigida pelo CREA.
class Auditoria
{
    public const ACAO_INSERT = 'INSERT';
    public const ACAO_UPDATE = 'UPDATE';
    public const ACAO_DELETE = 'DELETE';
    public const ACAO_EXCLUSAO_LOGICA = 'EXCLUSAO_LOGICA';

    public function __construct(
        public ?int $audId = null,
        public ?int $usuId = null,
        public string $audTabela = '',
        public int $audRegistroId = 0,
        public string $audAcao = self::ACAO_INSERT,
        public ?array $audDadosAntigos = null,
        public ?array $audDadosNovos = null,
        public ?string $audIp = null,
        public ?string $audUserAgent = null,
        public ?string $audDtRegistro = null,
    ) {
    }
}
