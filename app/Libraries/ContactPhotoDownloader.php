<?php
namespace App\Libraries;

use RuntimeException;

class ContactPhotoDownloader
{
    public function download(string $url): string
    {
        $parts = parse_url($url);
        // Google Contacts photos only. No redirects to arbitrary/internal hosts.
        if ($parts === false || ($parts['scheme'] ?? '') !== 'https'
            || ! preg_match('/^lh[0-9]+\.googleusercontent\.com$/i', $parts['host'] ?? '')
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            throw new RuntimeException('URL de fotografia não suportada.');
        }
        $temp = tempnam(sys_get_temp_dir(), 'contact-photo-');
        if ($temp === false) {
            throw new RuntimeException('Não foi possível criar arquivo temporário.');
        }
        $handle = fopen($temp, 'wb');
        $curl = curl_init($url);
        $size = 0;
        try {
            curl_setopt_array($curl, [
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use ($handle, &$size): int {
                    $size += strlen($chunk);
                    if ($size > 5 * 1024 * 1024) {
                        return 0;
                    }
                    return fwrite($handle, $chunk);
                },
            ]);
            $ca = trim((string) env('contacts.caBundle', env('ror.caBundle', ini_get('curl.cainfo'))));
            if ($ca !== '') {
                curl_setopt($curl, CURLOPT_CAINFO, $ca);
            }
            if (curl_exec($curl) === false || curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) {
                throw new RuntimeException('Fotografia indisponível ou download inválido.');
            }
            fclose($handle);
            $handle = null;
            return PersonPhoto::save($temp, FCPATH . 'repository/photo');
        } finally {
            curl_close($curl);
            if (is_resource($handle)) {
                fclose($handle);
            }
            unlink($temp);
        }
    }
}