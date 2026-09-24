<?php
namespace App\Libraries;

use InvalidArgumentException;
use RuntimeException;

class PersonPhoto
{
    public static function save(string $source, string $directory): string
    {
        $size = @filesize($source);
        $info = @getimagesize($source);
        if ($size === false || $size > 5 * 1024 * 1024 || $info === false
            || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 16000000) {
            throw new InvalidArgumentException('Envie uma imagem JPG, PNG ou WebP de até 5 MB e 16 megapixels.');
        }
        $image = @imagecreatefromstring(file_get_contents($source));
        if ($image === false) {
            throw new InvalidArgumentException('A imagem não pôde ser lida.');
        }
        $scale = min(1, 1200 / max($info[0], $info[1]));
        $width = max(1, (int) round($info[0] * $scale));
        $height = max(1, (int) round($info[1] * $scale));
        $output = imagecreatetruecolor($width, $height);
        try {
            imagefill($output, 0, 0, imagecolorallocate($output, 255, 255, 255));
            imagecopyresampled($output, $image, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
            if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new RuntimeException('Não foi possível preparar o diretório de fotografias.');
            }
            // Random input prevents guessing filenames from sequential person IDs.
            $name = md5(random_bytes(32)) . '.jpg';
            $path = $directory . DIRECTORY_SEPARATOR . $name;
            if (! imagejpeg($output, $path, 90)) {
                if (is_file($path)) {
                    unlink($path);
                }
                throw new RuntimeException('Não foi possível salvar a fotografia.');
            }
            return $name;
        } finally {
            imagedestroy($image);
            imagedestroy($output);
        }
    }

    public static function whatsapp(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with(trim($phone), '+')) {
            return preg_match('/^[1-9][0-9]{7,14}$/', $digits) ? $digits : null;
        }
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55' . $digits;
        }
        return preg_match('/^[1-9][0-9]{9,14}$/', $digits) ? $digits : null;
    }
}