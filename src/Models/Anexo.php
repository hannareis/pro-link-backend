<?php

declare(strict_types= 1);

namespace App\Models;

class Anexo
{
    public const STATUS_PUBLICO = 'PUBLICO';
    public const STATUS_PRIVADO = 'PRIVADO';
    public const STATUS_ARQUIVADO = 'ARQUIVADO';
    public const STATUS_SUSPENSO = 'SUSPENSO_PELO_CREA';

    public function __construct(
        public ?int $id = null,
        public ?int $postId = null,
        public string $nome = "",
        public string $nomeArmazenado = "",
        public string $tipoMime = "", // image/png, video/mp4, etc.
        public string $status = self::STATUS_PUBLICO,
        public int $tamanho = 0,
        public string $caminhoArmazenamento = "",
        public string $hash = "",
        public ?string $dataUpload = null
    ){}
}