<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\CartaVirtualService;

// RF05 - manifestacao de interesse via Cartas Virtuais, do check-in ao check-out.
class CartaVirtualController
{
    public function __construct(
        private readonly CartaVirtualService $cartaVirtualService = new CartaVirtualService()
    ) {
    }

    // Check-in: redige a carta e valida as credenciais das partes envolvidas.
    public function checkin(Request $request): void
    {
        // RF05 - redige a carta e valida as credenciais das partes
    }

    // Check-out: persiste a carta e dispara o e-mail automatico ao destinatario.
    public function checkout(Request $request): void
    {
        // RF05 - persiste a carta e aciona o disparo via SMTP
    }

    // Lista as cartas enviadas e recebidas pelo usuario autenticado.
    public function index(Request $request): void
    {
        // Lista cartas enviadas/recebidas pelo usuario
    }
}
