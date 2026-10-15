<?php
namespace App\Libraries;

class EmailAttachmentPreview
{
    public static function mime(string $name, string $content): ?string
    {
        $detected = (new \finfo(FILEINFO_MIME_TYPE))->buffer($content);
        if ($detected === 'application/pdf' && str_starts_with($content, '%PDF-')) return 'application/pdf';
        if (in_array($detected, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) return $detected;
        // Active documents (HTML, SVG, scripts) are never served as executable content.
        if (in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), ['txt', 'csv', 'log', 'json', 'xml', 'html', 'htm', 'svg', 'md'], true)
            && !str_contains($content, "\0") && mb_check_encoding($content, 'UTF-8')) return 'text/plain; charset=UTF-8';
        return null;
    }
}
