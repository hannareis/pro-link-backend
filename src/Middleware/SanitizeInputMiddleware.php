<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Helpers\Sanitizer;

// Sanitiza toda entrada do usuario antes que ela chegue ao Controller.
class SanitizeInputMiddleware
{
    // Substitui $_POST e $_GET pelas versoes sanitizadas (mitiga XSS).
    public function handle(Request $request): void
    {
        $_POST = Sanitizer::sanitizeArray($_POST);
        $_GET = Sanitizer::sanitizeArray($_GET);
    }
}
