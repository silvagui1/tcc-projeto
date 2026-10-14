<?php

namespace App\Support;

/**
 * Formatação de documentos e telefones guardados só com dígitos.
 */
class Formatar
{
    /**
     * "11987654321" → "(11) 98765-4321"; "1133334444" → "(11) 3333-4444".
     */
    public static function telefone(?string $digitos): string
    {
        $digitos = preg_replace('/\D/', '', (string) $digitos);

        return match (strlen($digitos)) {
            11 => preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $digitos),
            10 => preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $digitos),
            default => $digitos,
        };
    }

    /**
     * "12345678000190" → "12.345.678/0001-90".
     */
    public static function cnpj(?string $digitos): string
    {
        $digitos = preg_replace('/\D/', '', (string) $digitos);

        return strlen($digitos) === 14
            ? preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $digitos)
            : $digitos;
    }
}
