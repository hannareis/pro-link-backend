<?php

declare(strict_types=1);

namespace App\Models;

use DateTime;

// RF05 - Comunicação inicial entre empresas, instituições e profissionais. Além de perfis universitários.
class Post extends Publicavel
{
    public function __construct(
        int $id, int $userId, DateTime $dataDePostagem, string $conteudo,
        public string $titulo = "",
        public array $anexos = [],
    ) {
        parent::__construct($id, $userId, $dataDePostagem, $conteudo);
    }
}