<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `notificacoes`: alerta interno exibido no painel do usuario (RF07).
class Notificacao
{
    public function __construct(
        public ?int $id = null,
        public int $idUsuario = 0,
        public string $mensagem = '',
        public bool $lida = false,
        public ?string $criadoEm = null,
    ) {
    }
}
