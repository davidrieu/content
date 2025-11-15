<?php
/**
 * Language Configuration
 *
 * @package ACS\Data
 */

namespace ACS\Data;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Language_Config class
 */
class Language_Config {

    /**
     * Get all supported languages
     *
     * @return array
     */
    public static function get_all() {
        return [
            'fr' => [
                'name' => 'Français',
                'native_name' => 'Français',
                'code' => 'fr',
                'flag' => '🇫🇷',
                'direction' => 'ltr',
                'popular' => true,
            ],
            'en' => [
                'name' => 'English',
                'native_name' => 'English',
                'code' => 'en',
                'flag' => '🇬🇧',
                'direction' => 'ltr',
                'popular' => true,
            ],
            'es' => [
                'name' => 'Spanish',
                'native_name' => 'Español',
                'code' => 'es',
                'flag' => '🇪🇸',
                'direction' => 'ltr',
                'popular' => true,
            ],
            'de' => [
                'name' => 'German',
                'native_name' => 'Deutsch',
                'code' => 'de',
                'flag' => '🇩🇪',
                'direction' => 'ltr',
                'popular' => true,
            ],
            'it' => [
                'name' => 'Italian',
                'native_name' => 'Italiano',
                'code' => 'it',
                'flag' => '🇮🇹',
                'direction' => 'ltr',
                'popular' => true,
            ],
            'pt' => [
                'name' => 'Portuguese',
                'native_name' => 'Português',
                'code' => 'pt',
                'flag' => '🇵🇹',
                'direction' => 'ltr',
                'popular' => true,
            ],
            'nl' => [
                'name' => 'Dutch',
                'native_name' => 'Nederlands',
                'code' => 'nl',
                'flag' => '🇳🇱',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'pl' => [
                'name' => 'Polish',
                'native_name' => 'Polski',
                'code' => 'pl',
                'flag' => '🇵🇱',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'ru' => [
                'name' => 'Russian',
                'native_name' => 'Русский',
                'code' => 'ru',
                'flag' => '🇷🇺',
                'direction' => 'ltr',
                'popular' => true,
            ],
            'ja' => [
                'name' => 'Japanese',
                'native_name' => '日本語',
                'code' => 'ja',
                'flag' => '🇯🇵',
                'direction' => 'ltr',
                'popular' => true,
            ],
            'zh' => [
                'name' => 'Chinese',
                'native_name' => '中文',
                'code' => 'zh',
                'flag' => '🇨🇳',
                'direction' => 'ltr',
                'popular' => true,
            ],
            'ar' => [
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'code' => 'ar',
                'flag' => '🇸🇦',
                'direction' => 'rtl',
                'popular' => true,
            ],
            'ko' => [
                'name' => 'Korean',
                'native_name' => '한국어',
                'code' => 'ko',
                'flag' => '🇰🇷',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'tr' => [
                'name' => 'Turkish',
                'native_name' => 'Türkçe',
                'code' => 'tr',
                'flag' => '🇹🇷',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'hi' => [
                'name' => 'Hindi',
                'native_name' => 'हिन्दी',
                'code' => 'hi',
                'flag' => '🇮🇳',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'sv' => [
                'name' => 'Swedish',
                'native_name' => 'Svenska',
                'code' => 'sv',
                'flag' => '🇸🇪',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'da' => [
                'name' => 'Danish',
                'native_name' => 'Dansk',
                'code' => 'da',
                'flag' => '🇩🇰',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'no' => [
                'name' => 'Norwegian',
                'native_name' => 'Norsk',
                'code' => 'no',
                'flag' => '🇳🇴',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'fi' => [
                'name' => 'Finnish',
                'native_name' => 'Suomi',
                'code' => 'fi',
                'flag' => '🇫🇮',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'cs' => [
                'name' => 'Czech',
                'native_name' => 'Čeština',
                'code' => 'cs',
                'flag' => '🇨🇿',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'ro' => [
                'name' => 'Romanian',
                'native_name' => 'Română',
                'code' => 'ro',
                'flag' => '🇷🇴',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'el' => [
                'name' => 'Greek',
                'native_name' => 'Ελληνικά',
                'code' => 'el',
                'flag' => '🇬🇷',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'th' => [
                'name' => 'Thai',
                'native_name' => 'ไทย',
                'code' => 'th',
                'flag' => '🇹🇭',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'vi' => [
                'name' => 'Vietnamese',
                'native_name' => 'Tiếng Việt',
                'code' => 'vi',
                'flag' => '🇻🇳',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'id' => [
                'name' => 'Indonesian',
                'native_name' => 'Bahasa Indonesia',
                'code' => 'id',
                'flag' => '🇮🇩',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'ms' => [
                'name' => 'Malay',
                'native_name' => 'Bahasa Melayu',
                'code' => 'ms',
                'flag' => '🇲🇾',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'he' => [
                'name' => 'Hebrew',
                'native_name' => 'עברית',
                'code' => 'he',
                'flag' => '🇮🇱',
                'direction' => 'rtl',
                'popular' => false,
            ],
            'uk' => [
                'name' => 'Ukrainian',
                'native_name' => 'Українська',
                'code' => 'uk',
                'flag' => '🇺🇦',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'bn' => [
                'name' => 'Bengali',
                'native_name' => 'বাংলা',
                'code' => 'bn',
                'flag' => '🇧🇩',
                'direction' => 'ltr',
                'popular' => false,
            ],
            'fa' => [
                'name' => 'Persian',
                'native_name' => 'فارسی',
                'code' => 'fa',
                'flag' => '🇮🇷',
                'direction' => 'rtl',
                'popular' => false,
            ],
        ];
    }

    /**
     * Get popular languages only
     *
     * @return array
     */
    public static function get_popular() {
        return array_filter(self::get_all(), function($lang) {
            return $lang['popular'];
        });
    }

    /**
     * Get language names
     *
     * @return array
     */
    public static function get_names() {
        $languages = self::get_all();
        $names = [];

        foreach ($languages as $code => $lang) {
            $names[$code] = $lang['name'];
        }

        return $names;
    }

    /**
     * Get language native names
     *
     * @return array
     */
    public static function get_native_names() {
        $languages = self::get_all();
        $names = [];

        foreach ($languages as $code => $lang) {
            $names[$code] = $lang['native_name'];
        }

        return $names;
    }

    /**
     * Get language by code
     *
     * @param string $code
     * @return array|null
     */
    public static function get($code) {
        $languages = self::get_all();
        return $languages[$code] ?? null;
    }

    /**
     * Check if language is supported
     *
     * @param string $code
     * @return bool
     */
    public static function is_supported($code) {
        $languages = self::get_all();
        return isset($languages[$code]);
    }
}
