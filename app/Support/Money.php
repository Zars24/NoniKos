<?php

namespace App\Support;

class Money
{
    /**
     * Normalise user input ("1.500.000", "1500000", "Rp 1.500.000", "1500000,50") to integer rupiah.
     */
    public static function parse(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) round($value);
        }

        $clean = preg_replace('/[^0-9,.\-]/', '', (string) $value);

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            // Assume dot = thousands, comma = decimals.
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (str_contains($clean, ',')) {
            $clean = str_replace(',', '.', $clean);
        } elseif (substr_count($clean, '.') > 1) {
            $clean = str_replace('.', '', $clean);
        }

        return (int) round((float) $clean);
    }

    public static function format(int|float|null $amount): string
    {
        return 'Rp '.number_format((float) ($amount ?? 0), 0, ',', '.');
    }
}
