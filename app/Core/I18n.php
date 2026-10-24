<?php
declare(strict_types=1);
namespace App\Core;

final class I18n
{
    public const LANGUAGES = [
        'en' => ['label' => 'EN', 'name' => 'English', 'dir' => 'ltr'],
        'ar' => ['label' => 'AR', 'name' => 'العربية', 'dir' => 'rtl'],
        'ur' => ['label' => 'UR', 'name' => 'اردو',    'dir' => 'rtl'],
        'hi' => ['label' => 'HI', 'name' => 'हिन्दी',  'dir' => 'ltr'],
    ];
    private static ?string $lang = null;
    private static array $dict = [];

    public static function lang(): string
    {
        if (self::$lang) return self::$lang;
        $l = $_COOKIE['icodic_lang'] ?? Config::get('site.default_lang', 'en');
        return self::$lang = isset(self::LANGUAGES[$l]) ? $l : 'en';
    }
    public static function set(string $lang): void
    {
        if (!isset(self::LANGUAGES[$lang])) return;
        self::$lang = $lang; self::$dict = [];
        setcookie('icodic_lang', $lang, ['expires' => time() + 86400 * 365, 'path' => '/', 'samesite' => 'Lax']);
    }
    public static function dir(): string { return self::LANGUAGES[self::lang()]['dir']; }
    public static function t(string $key, ?string $default = null): string
    {
        $lang = self::lang();
        if (!isset(self::$dict[$lang])) {
            $f = APP_PATH . "/lang/$lang.php";
            self::$dict[$lang] = is_file($f) ? require $f : [];
            if ($lang !== 'en') self::$dict['en'] ??= require APP_PATH . '/lang/en.php';
        }
        return self::$dict[$lang][$key] ?? self::$dict['en'][$key] ?? $default ?? $key;
    }
}
