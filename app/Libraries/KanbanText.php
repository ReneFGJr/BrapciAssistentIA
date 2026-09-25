<?php
namespace App\Libraries;

class KanbanText
{
    public static function render(string $text): string
    {
        $parts = preg_split('~(https://[^\s<>"\x27]+)~iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $html = '';
        foreach ($parts as $index => $part) {
            if ($index % 2 === 0) {
                $html .= esc($part);
                continue;
            }
            $url = rtrim($part, '.,;:!?)');
            $suffix = substr($part, strlen($url));
            if (filter_var($url, FILTER_VALIDATE_URL) && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https') {
                $html .= '<a href="' . esc($url, 'attr') . '" target="_blank" rel="noopener noreferrer">'
                    . esc($url) . '</a>' . esc($suffix);
            } else {
                $html .= esc($part);
            }
        }
        return $html;
    }
}