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
     * Translate all strings in batch using Claude AI (OPTIMIZED)
     *
     * @param array $strings Array of French strings to translate
     * @param string $target_lang Target language code
     * @return array Array of translated strings
     */
    public static function translate_batch_with_ai($strings, $target_lang) {
        $lang_config = Language_Config::get($target_lang);

        if (!$lang_config) {
            return $strings;
        }

        $api_key = get_option('acs_claude_api_key', '');

        if (empty($api_key)) {
            Logger::warning('Claude API key not configured');
            return $strings;
        }

        // Prepare JSON input for batch translation
        $json_input = json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt = sprintf(
            "You are a professional translator. Translate the following JSON object from French to %s.\n\n" .
            "IMPORTANT INSTRUCTIONS:\n" .
            "1. Keep the SAME JSON structure (same keys)\n" .
            "2. Only translate the VALUES (right side), NOT the keys (left side)\n" .
            "3. Preserve tone, context, and any special characters or emojis\n" .
            "4. Return ONLY the translated JSON, no explanations\n" .
            "5. Keep placeholders like %%s, %%d unchanged\n\n" .
            "JSON to translate:\n%s",
            $lang_config['native_name'],
            $json_input
        );

        Logger::info("Translating {$target_lang} with batch API call");

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode([
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 16000, // Increased for batch translations
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]),
            'timeout' => 120, // 2 minutes for batch
        ]);

        if (is_wp_error($response)) {
            Logger::error('Batch translation API error', [
                'error' => $response->get_error_message(),
                'lang' => $target_lang
            ]);
            return $strings;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['content'][0]['text'])) {
            $translated_text = trim($body['content'][0]['text']);

            // Extract JSON from potential markdown code blocks
            if (preg_match('/```json\s*(.*?)\s*```/s', $translated_text, $matches)) {
                $translated_text = $matches[1];
            } elseif (preg_match('/```\s*(.*?)\s*```/s', $translated_text, $matches)) {
                $translated_text = $matches[1];
            }

            $translated_array = json_decode($translated_text, true);

            if (is_array($translated_array) && count($translated_array) > 0) {
                Logger::info("Successfully translated {$target_lang} with " . count($translated_array) . " keys");
                return $translated_array;
            } else {
                Logger::error('Failed to decode batch translation JSON', [
                    'lang' => $target_lang,
                    'response_preview' => substr($translated_text, 0, 500)
                ]);
            }
        }

        // Fallback to original strings if batch failed
        Logger::warning("Batch translation failed for {$target_lang}, keeping original strings");
        return $strings;
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
     * Generate all translations for a language (OPTIMIZED with batch translation)
     *
     * @param string $lang_code Language code
     * @return array Translations
     */
    public static function generate_translations($lang_code) {
        $strings = self::get_translatable_strings();
        $translations = [];

        Logger::info("Generating translations for language: {$lang_code} (using batch mode)");

        // If target is English, use the English translation directly
        if ($lang_code === 'en') {
            $translations = $strings;
        } else {
            // Use BATCH AI translation (1 API call instead of 461)
            $translations = self::translate_batch_with_ai($strings, $lang_code);
        }

        // Save translations to file
        $result = self::save_translations($lang_code, $translations);

        if ($result) {
            Logger::info("Successfully saved {$lang_code} translations: " . count($translations) . " keys");
        } else {
            Logger::error("Failed to save {$lang_code} translations to file");
        }

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

        // Create directory if it doesn't exist
        if (!file_exists($languages_dir)) {
            $created = wp_mkdir_p($languages_dir);
            if (!$created) {
                Logger::error("Failed to create translations directory: {$languages_dir}");
                return false;
            }
            Logger::info("Created translations directory: {$languages_dir}");
        }

        // Verify directory is writable
        if (!is_writable($languages_dir)) {
            Logger::error("Translations directory is not writable: {$languages_dir}");
            return false;
        }

        $file = $languages_dir . "/translations-{$lang_code}.json";
        $content = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $result = file_put_contents($file, $content);

        if ($result === false) {
            Logger::error("Failed to write translation file: {$file}");
            return false;
        }

        return true;
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
