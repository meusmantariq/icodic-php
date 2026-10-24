<?php
declare(strict_types=1);
namespace App\Core;

final class Upload
{
    /** Validates and stores an uploaded image. Returns the public relative URL (/uploads/...). */
    public static function image(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new \RuntimeException('No file uploaded or upload error.');
        $max = (int)Config::get('uploads.max_bytes', 5242880);
        if ($file['size'] > $max) throw new \RuntimeException('File too large (max ' . round($max / 1048576) . ' MB).');
        $allowed = Config::get('uploads.allowed', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) throw new \RuntimeException('File type not allowed.');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $okMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        if (!in_array($mime, $okMime, true)) throw new \RuntimeException('File content is not a valid image.');
        if ($mime === 'image/svg+xml') {
            $svg = file_get_contents($file['tmp_name']);
            if (preg_match('/<script|on\w+\s*=|javascript:/i', $svg)) throw new \RuntimeException('SVG contains scripting and was rejected.');
        }
        $dir = PUBLIC_PATH . '/uploads/' . date('Y/m');
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new \RuntimeException('Upload directory not writable.');
        $name = date('d') . '_' . bin2hex(random_bytes(8)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) throw new \RuntimeException('Could not save file.');
        return '/uploads/' . date('Y/m') . '/' . $name;
    }
}
