<?php
require __DIR__ . '/../app/Libraries/PersonPhoto.php';
use App\Libraries\PersonPhoto;
$directory = sys_get_temp_dir() . '/person-photo-' . bin2hex(random_bytes(8));
mkdir($directory);
$source = $directory . '/input.png';
try {
    $image = imagecreatetruecolor(1400, 700);
    imagepng($image, $source);
    imagedestroy($image);
    $one = PersonPhoto::save($source, $directory);
    $two = PersonPhoto::save($source, $directory);
    if (! preg_match('/^[a-f0-9]{32}\.jpg$/', $one) || $one === $two) {
        throw new RuntimeException('Invalid random filename');
    }
    $info = getimagesize($directory . '/' . $one);
    if ($info[2] !== IMAGETYPE_JPEG || $info[0] !== 1200 || $info[1] !== 600) {
        throw new RuntimeException('JPEG conversion or dimensions failed');
    }
    file_put_contents($source, '<?php echo "not an image";');
    try {
        PersonPhoto::save($source, $directory);
        throw new RuntimeException('Invalid file accepted');
    } catch (InvalidArgumentException $expected) {
    }
    foreach (['(51) 99999-9999' => '5551999999999', '+55 (51) 3333-4444' => '555133334444',
        '+1 202 555 0123' => '12025550123', '' => null, 'abc' => null] as $input => $expected) {
        if (PersonPhoto::whatsapp($input) !== $expected) {
            throw new RuntimeException('WhatsApp formatting failed');
        }
    }
    echo "Passed photo conversion, random filenames, invalid upload and WhatsApp checks.\n";
} finally {
    foreach (glob($directory . '/*') as $file) {
        unlink($file);
    }
    rmdir($directory);
}