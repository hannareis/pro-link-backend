<?php

declare(strict_types=1);

namespace App\Models;

// RF05 - manifestacao de interesse formal entre as partes (check-in ao check-out).
class CartaVirtual
{
    public const STATUS_CRIADA = 'criada';
    public const STATUS_ENVIADA = 'enviada';

    public function __construct(
        public ?int $id = null,
        public int $remetenteId = 0,
        public int $destinatarioId = 0,
        public string $conteudo = '',
        // Cartas publicas ficam visiveis no perfil; privadas ficam restritas as partes.
        public bool $publica = false,
        // 'criada' apos o check-in; 'enviada' apos o check-out (disparo via SMTP).
        public string $statusEnvio = self::STATUS_CRIADA,
        // Exclusao logica: 'A' (ativo) ou 'X' (excluido, preserva auditoria).
        public string $status = 'A',
    ) {
    }
}
