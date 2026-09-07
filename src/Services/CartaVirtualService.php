<?php

declare(strict_types=1);

namespace App\Services;

// RF05 - regra de negocio do fluxo de Cartas Virtuais, do check-in ao check-out.
class CartaVirtualService
{
    public function __construct(
        private readonly NotificacaoService $notificacaoService = new NotificacaoService()
    ) {
    }

    // Check-in: redige a carta e valida as credenciais das partes envolvidas.
    public function checkin(array $dadosCarta): int
    {
        // RF05 - redige a carta e valida as credenciais das partes
        return 0;
    }

    // Check-out: persiste a carta e dispara o e-mail ao destinatario via NotificacaoService.
    public function checkout(int $cartaVirtualId): bool
    {
        // RF05 - persiste a carta e dispara o e-mail via NotificacaoService
        return false;
    }
}
