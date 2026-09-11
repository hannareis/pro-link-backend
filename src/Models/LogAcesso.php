<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `sis_logs_acesso`: trilha de eventos de autenticacao (login/logout).
class LogAcesso
{
    public const ACAO_LOGIN_SUCESSO = 'LOGIN_SUCESSO';
    public const ACAO_LOGIN_FALHA = 'LOGIN_FALHA';
    public const ACAO_LOGOUT = 'LOGOUT';

    public function __construct(
        public ?int $logId = null,
        public ?int $usuId = null,
        public string $logAcao = self::ACAO_LOGIN_SUCESSO,
        public string $logIp = '',
        public ?string $logUserAgent = null,
        public ?string $logDtRegistro = null,
    ) {
    }
}
