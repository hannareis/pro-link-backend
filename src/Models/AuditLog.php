<?php

declare(strict_types=1);

namespace App\Models;

// RF06 - registro de auditoria de uma acao critica realizada por um usuario.
class AuditLog
{
    public function __construct(
        public ?int $id = null,
        public int $userId = 0,
        public string $acao = '',
        public array $contexto = [],
        public string $criadoEm = '',
    ) {
    }
}
