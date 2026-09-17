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
    // alertas administrativos, recuperacao de senha e cartas virtuais. $anexoCaminho e
    // um caminho absoluto no disco (ex: PATH_PUBLIC . '/' . $carta->caminhoArmazenamento);
    // anexo ausente ou inexistente e silenciosamente ignorado (nao falha o envio).
    // $replyToEmail: quando a mensagem e enviada "em nome de" outro usuario (cartas
    // virtuais), aponta o Reply-To pra essa pessoa em vez da conta SMTP fixa - ajuda
    // na entregabilidade e permite o destinatario responder direto pro remetente real.
    public function enviarEmail(
        string $destinatario,
        string $assunto,
        string $corpo,
        ?string $anexoCaminho = null,
        ?string $anexoNome = null,
        ?string $replyToEmail = null,
        ?string $replyToNome = null
    ): bool {
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

            if ($replyToEmail !== null) {
                $mail->addReplyTo($replyToEmail, $replyToNome ?? $replyToEmail);
            }

            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $corpo;
            $mail->AltBody = strip_tags($corpo);

            if ($anexoCaminho !== null && is_file($anexoCaminho)) {
                $mail->addAttachment($anexoCaminho, $anexoNome ?? basename($anexoCaminho));
            }

            return $mail->send();
        } catch (PHPMailerException $e) {
            // Sem isto, uma falha de SMTP (host/credenciais invalidas) e engolida em
            // silencio - o endpoint de recuperacao de senha sempre responde a mesma
            // mensagem por seguranca, entao sem log nao ha nenhum sinal do problema.
            error_log(sprintf('[NotificacaoService] Falha ao enviar e-mail para %s: %s', $destinatario, $mail->ErrorInfo ?: $e->getMessage()));
            return false;
        }
    }

    // Registra uma notificacao interna exibida no painel do usuario (feed, validacoes, match).
    public function notificarPainel(int $usuarioId, string $mensagem): void
    {
        $this->notificacoes->save(new Notificacao(idUsuario: $usuarioId, mensagem: $mensagem));
    }
}
