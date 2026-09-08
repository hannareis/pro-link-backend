<?php

declare(strict_types= 1);

namespace App\Models;

use DateTime;

class Anexo
{
    public function __construct(
        public string $nome = "",
        public string $tipoMIME = "", // image/png, video/mp4, etc.
        public int $tamanho = 0,
        public string $caminhoArmazenamento = "",
        public ?DateTime $dataUpload = null,
    ){}
}