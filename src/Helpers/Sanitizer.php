<?php

declare(strict_types=1);

namespace App\Helpers;

// Sanitizacao de entrada usada pelo SanitizeInputMiddleware, para mitigar XSS.
class Sanitizer
{
    // Percorre um array (recursivamente) escapando HTML e removendo espacos das bordas.
    public static function sanitizeArray(array $input): array
    {
        $sanitized = [];

        foreach ($input as $key => $value) {
            $sanitized[$key] = is_array($value)
                ? self::sanitizeArray($value)
                : trim(htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
        }

        return $sanitized;
    }
}
