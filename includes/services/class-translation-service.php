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
            Logger::error("Configuration langue introuvable: {$target_lang}");
            return $text;
        }

        $api_key = get_option('acs_claude_api_key', '');

        if (empty($api_key)) {
            Logger::error('Clé API Claude non configurée');
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
            Logger::error('❌ API Error (single translation)', [
                'error' => $response->get_error_message(),
                'text_preview' => substr($text, 0, 50)
            ]);
            return $text;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Check for API errors
        if (isset($body['error'])) {
            Logger::error('❌ Claude API Error (single)', [
                'message' => $body['error']['message'] ?? 'Unknown',
                'type' => $body['error']['type'] ?? 'unknown',
                'text_preview' => substr($text, 0, 50)
            ]);
            return $text;
        }

        if (isset($body['content'][0]['text'])) {
            return trim($body['content'][0]['text']);
        }

        Logger::warning("⚠️ Pas de contenu dans réponse API", ['text_preview' => substr($text, 0, 50)]);
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
        Logger::info("🚀 [DÉBUT] Traduction batch pour langue: {$target_lang}");

        $lang_config = Language_Config::get($target_lang);

        if (!$lang_config) {
            Logger::error("❌ [ERREUR] Configuration langue introuvable pour: {$target_lang}");
            return $strings;
        }
        Logger::info("✓ Configuration langue trouvée: " . $lang_config['native_name']);

        $api_key = get_option('acs_claude_api_key', '');

        if (empty($api_key)) {
            Logger::error('❌ [ERREUR] Clé API Claude non configurée');
            return $strings;
        }
        Logger::info("✓ Clé API Claude configurée (longueur: " . strlen($api_key) . " caractères)");

        // Prepare JSON input for batch translation
        $num_strings = count($strings);
        Logger::info("✓ Préparation de {$num_strings} chaînes à traduire");

        $json_input = json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $json_size = strlen($json_input);
        Logger::info("✓ JSON préparé ({$json_size} octets, ~" . round($json_size/1024) . " KB)");

        // Log quelques exemples
        $sample_keys = array_slice(array_keys($strings), 0, 3);
        foreach ($sample_keys as $key) {
            Logger::info("📝 Exemple à traduire: '{$key}' → '{$strings[$key]}'");
        }

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

        Logger::info("🌐 [API] Envoi requête à Claude API (model: claude-3-5-sonnet-20240620)");
        Logger::info("🌐 [API] Timeout configuré: 120 secondes");
        Logger::info("🌐 [API] Max tokens: 8192");

        $request_start = microtime(true);

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode([
                'model' => 'claude-3-5-sonnet-20240620',
                'max_tokens' => 8192,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]),
            'timeout' => 120,
        ]);

        $request_duration = round(microtime(true) - $request_start, 2);
        Logger::info("🌐 [API] Réponse reçue en {$request_duration} secondes");

        if (is_wp_error($response)) {
            Logger::error('❌ [ERREUR API] ' . $response->get_error_message(), [
                'lang' => $target_lang,
                'duration' => $request_duration
            ]);
            return $strings;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        Logger::info("🌐 [API] Code HTTP: {$http_code}");

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            Logger::error("❌ [ERREUR API] " . ($body['error']['message'] ?? 'Erreur inconnue'), [
                'type' => $body['error']['type'] ?? 'unknown',
                'lang' => $target_lang,
                'error_full' => json_encode($body['error'])
            ]);
            Logger::error("Réponse API complète: " . json_encode($body));
            return $strings;
        }

        if (isset($body['content'][0]['text'])) {
            $translated_text = trim($body['content'][0]['text']);
            $response_size = strlen($translated_text);
            Logger::info("✓ Texte traduit reçu ({$response_size} octets, ~" . round($response_size/1024) . " KB)");

            // Extract JSON from response
            // Try to find JSON in markdown code blocks first
            if (preg_match('/```json\s*(.*?)\s*```/s', $translated_text, $matches)) {
                Logger::info("✓ JSON extrait des balises markdown ```json");
                $translated_text = $matches[1];
            } elseif (preg_match('/```\s*(.*?)\s*```/s', $translated_text, $matches)) {
                Logger::info("✓ JSON extrait des balises markdown ```");
                $translated_text = $matches[1];
            } else {
                // No markdown, try to extract raw JSON object
                // Find the first { and last } to extract the complete JSON object
                $first_brace = strpos($translated_text, '{');
                $last_brace = strrpos($translated_text, '}');

                if ($first_brace !== false && $last_brace !== false && $last_brace > $first_brace) {
                    $json_only = substr($translated_text, $first_brace, $last_brace - $first_brace + 1);
                    Logger::info("✓ JSON extrait directement (du caractère {$first_brace} au {$last_brace})");
                    $translated_text = $json_only;
                } else {
                    Logger::info("✓ JSON sans balises markdown (utilisation brute)");
                }
            }

            Logger::info("🔍 Décodage JSON...");
            $translated_array = json_decode($translated_text, true);

            if (is_array($translated_array) && count($translated_array) > 0) {
                $translated_count = count($translated_array);
                Logger::info("✅ [SUCCÈS] {$translated_count} traductions décodées avec succès");

                // Log quelques exemples de traductions
                $sample_translated = array_slice($translated_array, 0, 3, true);
                foreach ($sample_translated as $key => $value) {
                    Logger::info("✓ Traduction OK: '{$key}' → '{$value}'");
                }

                // Vérifier si c'est vraiment traduit
                if (isset($translated_array['Bienvenue'])) {
                    $bienvenue_value = $translated_array['Bienvenue'];
                    if ($bienvenue_value === 'Bienvenue') {
                        Logger::warning("⚠️ [ATTENTION] 'Bienvenue' n'a PAS été traduit (toujours en français!)");
                    } else {
                        Logger::info("✓ Vérification: 'Bienvenue' → '{$bienvenue_value}' (BIEN TRADUIT)");
                    }
                }

                return $translated_array;
            } else {
                Logger::error('❌ [ERREUR] Impossible de décoder le JSON de traduction', [
                    'lang' => $target_lang,
                    'json_error' => json_last_error_msg(),
                    'response_preview' => substr($translated_text, 0, 500)
                ]);
                Logger::error("Réponse complète Claude: " . $translated_text);
            }
        } else {
            Logger::error("❌ [ERREUR] Pas de contenu texte dans la réponse API", [
                'lang' => $target_lang,
                'body_keys' => array_keys($body)
            ]);
        }

        // Fallback to original strings if batch failed
        Logger::warning("⚠️ [ÉCHEC] Traduction batch échouée pour {$target_lang}, conservation des chaînes originales");
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
     * Generate translations chunk (portion) for a language
     *
     * @param string $lang_code Language code
     * @param int $offset Starting position
     * @param int $limit Number of translations to generate
     * @return array Result with translated chunk and progress info
     */
    public static function generate_translations_chunk($lang_code, $offset = 0, $limit = 50) {
        $strings = self::get_translatable_strings();
        $total_strings = count($strings);

        // If target is English, use the English translation directly
        if ($lang_code === 'en') {
            return [
                'success' => true,
                'translations' => $strings,
                'total' => $total_strings,
                'processed' => $total_strings,
                'completed' => true
            ];
        }

        // Get existing translations or start fresh
        $translations_file = ACS_TRANSLATIONS_DIR . "/translations-{$lang_code}.json";
        $all_translations = [];
        if (file_exists($translations_file)) {
            $content = file_get_contents($translations_file);
            $all_translations = json_decode($content, true) ?: [];
        }

        // Get chunk of strings to translate
        $strings_array = array_slice($strings, $offset, $limit, true);
        $chunk_size = count($strings_array);
        $errors = 0;

        Logger::info("📦 [CHUNK] Traduction {$lang_code}: de {$offset} à " . ($offset + $chunk_size) . " sur {$total_strings}");

        // Translate this chunk
        foreach ($strings_array as $french_key => $english_value) {
            // Translate from French to target language
            $translated = self::translate_with_ai($french_key, $lang_code);

            // Check if translation failed
            if ($translated === $french_key) {
                $errors++;
                Logger::warning("⚠️ Traduction échouée: '{$french_key}'");
            }

            $all_translations[$french_key] = $translated;

            // Small delay to avoid rate limiting (50ms)
            usleep(50000);
        }

        // Save updated translations
        $result = self::save_translations($lang_code, $all_translations);

        $processed = $offset + $chunk_size;
        $completed = ($processed >= $total_strings);

        if ($completed) {
            Logger::info("✅ [TERMINÉ] {$lang_code}: {$total_strings} traductions ({$errors} erreurs)");
        }

        return [
            'success' => $result,
            'translations' => $all_translations,
            'total' => $total_strings,
            'processed' => $processed,
            'completed' => $completed,
            'errors' => $errors,
            'chunk_size' => $chunk_size
        ];
    }

    /**
     * Generate all translations for a language (ONE BY ONE with Haiku)
     * DEPRECATED: Use generate_translations_chunk for better timeout handling
     *
     * @param string $lang_code Language code
     * @return array Translations
     */
    public static function generate_translations($lang_code) {
        $strings = self::get_translatable_strings();
        $translations = [];

        $total_strings = count($strings);
        Logger::info("🚀 [DÉBUT] Génération traductions pour {$lang_code}: {$total_strings} chaînes (mode: UNE PAR UNE)");

        // If target is English, use the English translation directly
        if ($lang_code === 'en') {
            $translations = $strings;
            Logger::info("✓ Langue anglaise: utilisation directe du fichier source");
        } else {
            // Translate ONE BY ONE from French to target language
            $current = 0;
            $errors = 0;

            foreach ($strings as $french_key => $english_value) {
                $current++;

                // Log progress every 50 strings
                if ($current % 50 === 0 || $current === 1) {
                    Logger::info("📊 Progression: {$current}/{$total_strings} (" . round(($current/$total_strings)*100) . "%)");
                }

                // Translate from French to target language
                $translated = self::translate_with_ai($french_key, $lang_code);

                // Check if translation failed (returned same text)
                if ($translated === $french_key) {
                    $errors++;
                    if ($errors <= 5) {
                        Logger::warning("⚠️ Traduction échouée pour: '{$french_key}'");
                    }
                }

                $translations[$french_key] = $translated;

                // Small delay to avoid rate limiting (50ms)
                usleep(50000);
            }

            Logger::info("✅ [TERMINÉ] {$current} traductions générées ({$errors} erreurs)");
        }

        // Save translations to file
        $result = self::save_translations($lang_code, $translations);

        if ($result) {
            Logger::info("✓ Fichier sauvegardé: {$lang_code} ({$total_strings} clés)");
        } else {
            Logger::error("❌ Échec sauvegarde fichier {$lang_code}");
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
