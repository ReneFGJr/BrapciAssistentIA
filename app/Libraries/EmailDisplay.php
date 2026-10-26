<?php
namespace App\Libraries;

class EmailDisplay
{
    public static function date($value): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '' || str_starts_with($value, '0000-')) return null;
        try {
            return new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        } catch (\Throwable $e) { return null; }
    }

    public static function dateLabel($value): string
    {
        return self::date($value)?->setTimezone(new \DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i') ?? 'Data indisponível';
    }
}
