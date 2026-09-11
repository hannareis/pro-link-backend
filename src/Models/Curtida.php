<?php

declare(strict_types= 1);

namespace App\Models;

use DateTime;

class Curtida
{
    public function __construct(
        public ?int $userId = null,
        public ?int $publicavelId = null,
        public ?DateTime $data = null
    ) {
    }
}