<?php
/**
 * Translation Service - Automatic AI Translation
 *
 * @package ACS\Services
 */

namespace ACS\Services;

use ACS\Data\Language_Config;
use ACS\Utils\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Translation_Service class
 */
class Translation_Service {

    /**
     * Get all translatable strings from the app
     * Loads from the source JSON file in the plugin directory
     *
     * @return array
     */
    public static function get_translatable_strings() {
        // Load from the source JSON file
        $source_file = ACS_PLUGIN_DIR . '/languages/translations-en.json';

        if (file_exists($source_file)) {
            $content = file_get_contents($source_file);
            $translations = json_decode($content, true);

            if (is_array($translations)) {
                return $translations;
            }
        }

        // Fallback to empty array if file doesn't exist
        Logger::warning('Source translation file not found: ' . $source_file);
        return [];
    }

    /**
     * Translate a string to target language using Claude AI
     *
     * @param string $text Original text in French
     * @param string $target_lang Target language code
     * @return string Translated text
     */
    public static function translate_with_ai($text, $target_lang) {
        $lang_config = Language_Config::get($target_lang);

        if (!$lang_config) {
            return $text;
        }

        $api_key = get_option('acs_claude_api_key', '');

        if (empty($api_key)) {
            return $text;
        }

        $prompt = sprintf(
            "Translate the following text from French to %s. Keep the same tone and context. Only return the translation, nothing else.\n\nText to translate: \"%s\"",
            $lang_config['native_name'],
            $text
        );

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode([
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 1024,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            Logger::error('Translation API error', ['error' => $response->get_error_message()]);
            return $text;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['content'][0]['text'])) {
            return trim($body['content'][0]['text']);
        }

        return $text;
    }

    /**
     * Get or generate translation file for a language
     *
     * @param string $lang_code Language code
     * @return array Translations
     */
    public static function get_translations($lang_code) {
        // Check if translation file exists
        $translations_file = ACS_TRANSLATIONS_DIR . "/translations-{$lang_code}.json";

        if (file_exists($translations_file)) {
            $content = file_get_contents($translations_file);
            return json_decode($content, true);
        }

        // Generate translations if they don't exist
        return self::generate_translations($lang_code);
    }

    /**
     * Generate all translations for a language
     *
     * @param string $lang_code Language code
     * @return array Translations
     */
    public static function generate_translations($lang_code) {
        $strings = self::get_translatable_strings();
        $translations = [];

        Logger::info("Generating translations for language: {$lang_code}");

        foreach ($strings as $french => $english) {
            // If target is English, use the English translation directly
            if ($lang_code === 'en') {
                $translations[$french] = $english;
            } else {
                // Use AI translation for other languages
                $translated = self::translate_with_ai($french, $lang_code);
                $translations[$french] = $translated;

                // Small delay to avoid rate limiting
                usleep(100000); // 0.1 second
            }
        }

        // Save translations to file
        self::save_translations($lang_code, $translations);

        return $translations;
    }

    /**
     * Save translations to file
     *
     * @param string $lang_code Language code
     * @param array $translations Translations
     * @return bool Success
     */
    public static function save_translations($lang_code, $translations) {
        $languages_dir = ACS_TRANSLATIONS_DIR;

        if (!file_exists($languages_dir)) {
            mkdir($languages_dir, 0755, true);
        }

        $file = $languages_dir . "/translations-{$lang_code}.json";
        $content = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return file_put_contents($file, $content) !== false;
    }

    /**
     * Translate a string
     *
     * @param string $string String to translate
     * @param string $lang_code Language code (defaults to user's language)
     * @return string Translated string
     */
    public static function __($string, $lang_code = null) {
        if (!$lang_code) {
            $lang_code = get_user_meta(get_current_user_id(), 'acs_user_language', true);
        }

        if (!$lang_code || $lang_code === 'fr') {
            return $string;
        }

        $translations = self::get_translations($lang_code);

        return $translations[$string] ?? $string;
    }
}
