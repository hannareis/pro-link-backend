<?php

declare(strict_types=1);

namespace App\Models;

// RF05 - Comunicação inicial entre empresas, instituições e profissionais. Além de perfis universitários.
class Post extends Publicavel
{
    public function __construct(
        ?int $id = null, int $userId = 0, ?string $dataDePostagem = null, ?string $dataEdicao = null, string $conteudo = '', string $status = Publicavel::STATUS_PUBLICO,
        public string $titulo = "",
    ) {
        parent::__construct($id, $userId, $dataDePostagem, $dataEdicao, $conteudo, $status);
    }
}