<?php
namespace App\Libraries;

class EmailHtml
{
    public static function document(string $html): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try { $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        $allowed = explode(' ', 'html head body title style p div span br hr b strong i em u s strike small big sub sup blockquote pre code ul ol li dl dt dd h1 h2 h3 h4 h5 h6 table thead tbody tfoot tr td th colgroup col caption a img center font');
        foreach (iterator_to_array($dom->getElementsByTagName('*')) as $node) {
            if (!in_array(strtolower($node->tagName), $allowed, true)) {
                $node->parentNode?->removeChild($node);
                continue;
            }
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                if (!in_array($name, ['style', 'class', 'id', 'title', 'alt', 'width', 'height', 'align', 'valign', 'bgcolor', 'color', 'face', 'size', 'border', 'cellpadding', 'cellspacing', 'colspan', 'rowspan', 'href', 'src'], true)) $node->removeAttribute($attribute->name);
            }
            if ($node->hasAttribute('href')) {
                $href = trim($node->getAttribute('href'));
                if (preg_match('~^(?:https?://|mailto:|tel:)~i', $href) && !preg_match('/[\x00-\x20]/', $href)) {
                    $node->setAttribute('target', '_blank'); $node->setAttribute('rel', 'noopener noreferrer');
                } else $node->removeAttribute('href');
            }
            if ($node->hasAttribute('src') && !preg_match('~^data:image/(?:png|jpeg|gif|webp);base64,[a-z0-9+/=\s]+$~iD', $node->getAttribute('src'))) $node->removeAttribute('src');
        }
        $content = preg_replace('/<\?xml[^>]*>/', '', $dom->saveHTML());
        return '<!doctype html><html><head><meta charset="utf-8"><meta name="referrer" content="no-referrer"><meta http-equiv="Content-Security-Policy" content="default-src \'none\'; style-src \'unsafe-inline\'; img-src data:; base-uri \'none\'; form-action \'none\'"><style>body{font-family:Arial,sans-serif;overflow-wrap:anywhere;margin:16px}img,table{max-width:100%}pre{white-space:pre-wrap}</style></head><body>' . $content . '</body></html>';
    }
}
