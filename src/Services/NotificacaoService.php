<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notificacao;
use App\Repositories\NotificacaoRepository;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

// RF07 - notificacoes internas (painel) e externas (SMTP) da plataforma.
class NotificacaoService
{
    public function __construct(
        private readonly NotificacaoRepository $notificacoes = new NotificacaoRepository()
    ) {
    }

    // Dispara um e-mail via SMTP (Anexo I, item 8.3.1 do Edital), usado em
    // alertas administrativos e recuperacao de senha.
    public function enviarEmail(string $destinatario, string $assunto, string $corpo): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = (string) config('mail.host');
            $mail->Port = (int) config('mail.port');
            $mail->SMTPAuth = true;
            $mail->Username = (string) config('mail.username');
            $mail->Password = (string) config('mail.password');
            $mail->SMTPSecure = (string) config('mail.encryption');
            $mail->CharSet = 'UTF-8';

            $mail->setFrom((string) config('mail.from_address'), (string) config('mail.from_name'));
            $mail->addAddress($destinatario);

            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $corpo;
            $mail->AltBody = strip_tags($corpo);

            return $mail->send();
        } catch (PHPMailerException) {
            return false;
        }
    }

    // Registra uma notificacao interna exibida no painel do usuario (feed, validacoes, match).
    public function notificarPainel(int $usuarioId, string $mensagem): void
    {
        $this->notificacoes->save(new Notificacao(idUsuario: $usuarioId, mensagem: $mensagem));
    }
}
