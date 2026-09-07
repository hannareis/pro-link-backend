<?php

declare(strict_types=1);

namespace App\Helpers;

// Validacoes de formato usadas antes de consultar a API do CREA-AM (RF02) ou persistir dados.
class Validator
{
    // Verifica se o CPF (apos remover mascara) tem 11 digitos.
    public static function isValidCpf(string $cpf): bool
    {
        return (bool) preg_match('/^\d{11}$/', preg_replace('/\D/', '', $cpf));
    }

    // Verifica se o CNPJ (apos remover mascara) tem 14 digitos.
    public static function isValidCnpj(string $cnpj): bool
    {
        return (bool) preg_match('/^\d{14}$/', preg_replace('/\D/', '', $cnpj));
    }

    // Verifica se o e-mail tem um formato valido.
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
