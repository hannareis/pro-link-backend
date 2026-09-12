<?php

declare(strict_types= 1);

namespace App\Models;

class Curtida
{
    public function __construct(
        public ?int $userId = null,
        public ?int $publicavelId = null,
        public ?string $data = null
    ) {
    }
}