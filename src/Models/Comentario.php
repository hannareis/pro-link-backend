<?php

declare(strict_types= 1);

namespace App\Models;

use DateTime;

// RF05 - Comunicação inicial entre empresas, instituições e profissionais. Além de perfis universitários.
class Comentario extends Publicavel
{
    public function __construct(
        ?int $id, int $userId, DateTime $dataDePostagem, DateTime $dataEdicao, string $conteudo, string $status,
        public ?int $postId = null,
        public ?int $comentarioPaiId = null,
    ) {
        parent::__construct($id, $userId, $dataDePostagem, $dataEdicao, $conteudo, $status);
    }
}