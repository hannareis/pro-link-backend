<?php

declare(strict_types=1);

namespace App\Models;

// RF01 - usuario da plataforma, em um dos 6 perfis previstos no edital.
class User
{
    // Os 6 perfis definidos na proposta (item 5.1).
    public const PERFIL_PUBLICO = 'publico';
    public const PERFIL_EMPRESA = 'empresa';
    public const PERFIL_PROFISSIONAL = 'profissional';
    public const PERFIL_UNIVERSITARIO = 'universitario';
    public const PERFIL_TERCEIRO = 'terceiro';
    public const PERFIL_ADMIN = 'admin';

    // Exclusao logica: 'A' (ativo) ou 'X' (excluido, preserva auditoria).
    public const STATUS_ATIVO = 'A';
    public const STATUS_EXCLUIDO = 'X';

    public function __construct(
        public ?int $id = null,
        public string $nome = '',
        public string $email = '',
        public string $senhaHash = '',
        public string $perfil = self::PERFIL_PUBLICO,
        // Concedido apos validacao positiva na API do CREA-AM (RF01/RF02).
        public bool $seloVerificacao = false,
        public string $status = self::STATUS_ATIVO,
    ) {
    }
}
