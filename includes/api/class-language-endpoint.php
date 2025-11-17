<?php
/**
 * Language API Endpoint
 *
 * @package ACS\API
 */

namespace ACS\API;

use ACS\Data\Language_Config;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Language_Endpoint class
 */
class Language_Endpoint extends REST_Controller {

    /**
     * Register routes
     */
    public function register_routes() {
        // GET /acs/v1/languages - Get all languages
        register_rest_route('acs/v1', '/languages', [
            'methods' => 'GET',
            'callback' => [$this, 'get_languages'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        // GET /acs/v1/language - Get current user language
        register_rest_route('acs/v1', '/language', [
            'methods' => 'GET',
            'callback' => [$this, 'get_user_language'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        // POST /acs/v1/language - Set user language
        register_rest_route('acs/v1', '/language', [
            'methods' => 'POST',
            'callback' => [$this, 'set_user_language'],
            'permission_callback' => [$this, 'permission_check'],
            'args' => [
                'language' => [
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => function($param) {
                        return Language_Config::is_supported($param);
                    }
                ],
            ],
        ]);

        // GET /acs/v1/translations/{lang} - Get translations for a language (public access for login/register)
        register_rest_route('acs/v1', '/translations/(?P<lang>[a-z]{2})', [
            'methods' => 'GET',
            'callback' => [$this, 'get_translations'],
            'permission_callback' => '__return_true', // Public access for non-authenticated users
        ]);
    }

    /**
     * Get all available languages
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_languages($request) {
        $languages = Language_Config::get_all();

        return $this->success([
            'languages' => $languages,
            'popular' => Language_Config::get_popular(),
        ]);
    }

    /**
     * Get current user language
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_user_language($request) {
        $user_id = get_current_user_id();

        // Try new key first, then fallback to preferred language (set during registration)
        $language = get_user_meta($user_id, 'acs_user_language', true);
        if (!$language) {
            $language = get_user_meta($user_id, 'acs_preferred_language', true);
        }

        if (!$language) {
            $language = 'en'; // Default language for new users
        }

        $lang_config = Language_Config::get($language);

        return $this->success([
            'language' => $language,
            'config' => $lang_config,
        ]);
    }

    /**
     * Set user language
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function set_user_language($request) {
        $user_id = get_current_user_id();
        $language = $request->get_param('language');

        if (!Language_Config::is_supported($language)) {
            return $this->error(__('Langue non supportée', 'ai-content-studio'));
        }

        update_user_meta($user_id, 'acs_user_language', $language);

        return $this->success([
            'language' => $language,
            'message' => __('Langue mise à jour avec succès', 'ai-content-studio'),
        ]);
    }

    /**
     * Get translations for a specific language
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_translations($request) {
        $lang = $request->get_param('lang');

        if (!Language_Config::is_supported($lang)) {
            return $this->error(__('Langue non supportée', 'ai-content-studio'));
        }

        $translations_file = ACS_TRANSLATIONS_DIR . "/translations-{$lang}.json";

        if (!file_exists($translations_file)) {
            return $this->error(__('Traductions non disponibles pour cette langue', 'ai-content-studio'));
        }

        $content = file_get_contents($translations_file);
        $translations = json_decode($content, true);

        return $this->success([
            'language' => $lang,
            'translations' => $translations,
        ]);
    }
}
