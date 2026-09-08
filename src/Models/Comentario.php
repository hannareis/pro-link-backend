<?php

declare(strict_types= 1);

namespace App\Models;

use DateTime;

// RF05 - Comunicação inicial entre empresas, instituições e profissionais. Além de perfis universitários.
class Comentario extends Publicavel
{
    public function __construct(
        int $id, int $userId, DateTime $dataDePostagem, string $conteudo,
        public ?int $publicavelId = null, // Objeto do tipo Publicavel ao qual o comentario pertence.
    ) {
        parent::__construct($id, $userId, $dataDePostagem, $conteudo);
    }
}