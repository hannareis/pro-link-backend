<?php

declare(strict_types=1);

namespace App\Services;

// RF07 - notificacoes internas (painel) e externas (SMTP) da plataforma.
class NotificacaoService
{
    // Dispara um e-mail via SMTP, usado no check-out das Cartas Virtuais e alertas administrativos.
    public function enviarEmail(string $destinatario, string $assunto, string $corpo): bool
    {
        // RF07 - disparo via protocolo SMTP (check-out das Cartas Virtuais, alertas administrativos)
        return false;
    }

    // Registra uma notificacao interna exibida no painel do usuario (feed, validacoes, match).
    public function notificarPainel(int $usuarioId, string $mensagem): void
    {
        // RF07 - notificacao interna (feed, validacoes de acervo, sugestoes de match)
    }
}
