<?php
/**
 * Admin Settings Page
 *
 * @package ACS\Admin
 */

namespace ACS\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings class
 */
class Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'add_settings_page']);
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'ai-content-studio',
            __('Réglages AI Content Studio', 'ai-content-studio'),
            __('Réglages', 'ai-content-studio'),
            'manage_options',
            'ai-content-studio-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // API Keys Section
        add_settings_section(
            'acs_api_keys',
            __('Clés API', 'ai-content-studio'),
            [$this, 'api_keys_section_callback'],
            'acs-settings'
        );

        // Claude API Key
        register_setting('acs_settings', 'acs_claude_api_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        add_settings_field(
            'acs_claude_api_key',
            __('Claude API Key', 'ai-content-studio'),
            [$this, 'api_key_field_callback'],
            'acs-settings',
            'acs_api_keys',
            ['field' => 'acs_claude_api_key', 'placeholder' => 'sk-ant-...']
        );

        // DALL-E API Key
        register_setting('acs_settings', 'acs_dalle_api_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        add_settings_field(
            'acs_dalle_api_key',
            __('DALL-E API Key (OpenAI)', 'ai-content-studio'),
            [$this, 'api_key_field_callback'],
            'acs-settings',
            'acs_api_keys',
            ['field' => 'acs_dalle_api_key', 'placeholder' => 'sk-...']
        );

        // General Settings Section
        add_settings_section(
            'acs_general',
            __('Paramètres Généraux', 'ai-content-studio'),
            [$this, 'general_section_callback'],
            'acs-settings'
        );

        // Default Language
        register_setting('acs_settings', 'acs_default_language', [
            'type' => 'string',
            'default' => 'fr',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        add_settings_field(
            'acs_default_language',
            __('Langue par défaut', 'ai-content-studio'),
            [$this, 'language_field_callback'],
            'acs-settings',
            'acs_general'
        );

        // Enable Debug
        register_setting('acs_settings', 'acs_enable_debug', [
            'type' => 'boolean',
            'default' => false,
        ]);

        add_settings_field(
            'acs_enable_debug',
            __('Mode Debug', 'ai-content-studio'),
            [$this, 'checkbox_field_callback'],
            'acs-settings',
            'acs_general',
            ['field' => 'acs_enable_debug', 'label' => __('Activer les logs de débogage', 'ai-content-studio')]
        );

        // Shortcode Page
        register_setting('acs_settings', 'acs_shortcode_page', [
            'type' => 'integer',
            'default' => 0,
            'sanitize_callback' => 'absint',
        ]);

        add_settings_field(
            'acs_shortcode_page',
            __('Page Frontend par défaut', 'ai-content-studio'),
            [$this, 'page_select_field_callback'],
            'acs-settings',
            'acs_general',
            ['field' => 'acs_shortcode_page']
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'ai-content-studio'));
        }

        // Check if WooCommerce is active
        $wc_active = class_exists('WooCommerce');
        $wc_subs_active = class_exists('WC_Subscriptions');

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php if (!$wc_active || !$wc_subs_active): ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php _e('Attention:', 'ai-content-studio'); ?></strong>
                        <?php if (!$wc_active): ?>
                            <?php _e('WooCommerce n\'est pas installé. Le système d\'abonnement ne fonctionnera pas.', 'ai-content-studio'); ?>
                        <?php elseif (!$wc_subs_active): ?>
                            <?php _e('WooCommerce Subscriptions n\'est pas installé. Le système d\'abonnement récurrent ne fonctionnera pas complètement.', 'ai-content-studio'); ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php settings_errors('acs_settings'); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('acs_settings');
                do_settings_sections('acs-settings');
                submit_button();
                ?>
            </form>

            <hr>

            <h2><?php _e('Informations du Plugin', 'ai-content-studio'); ?></h2>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong><?php _e('Version:', 'ai-content-studio'); ?></strong></td>
                        <td><?php echo ACS_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('WooCommerce:', 'ai-content-studio'); ?></strong></td>
                        <td><?php echo $wc_active ? '✅ Actif' : '❌ Non installé'; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('WooCommerce Subscriptions:', 'ai-content-studio'); ?></strong></td>
                        <td><?php echo $wc_subs_active ? '✅ Actif' : '❌ Non installé'; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Claude API configurée:', 'ai-content-studio'); ?></strong></td>
                        <td><?php echo get_option('acs_claude_api_key') ? '✅ Oui' : '❌ Non'; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('DALL-E API configurée:', 'ai-content-studio'); ?></strong></td>
                        <td><?php echo get_option('acs_dalle_api_key') ? '✅ Oui' : '❌ Non'; ?></td>
                    </tr>
                </tbody>
            </table>

            <hr>

            <h2><?php _e('Utilisation du Shortcode', 'ai-content-studio'); ?></h2>
            <p><?php _e('Pour afficher l\'interface client sur une page, utilisez le shortcode:', 'ai-content-studio'); ?></p>
            <code>[ai_content_studio]</code>
            <p><?php _e('Ou avec des paramètres:', 'ai-content-studio'); ?></p>
            <code>[ai_content_studio view="generator"]</code><br>
            <code>[ai_content_studio view="dashboard" height="1000px"]</code>
        </div>
        <?php
    }

    /**
     * API Keys section callback
     */
    public function api_keys_section_callback() {
        echo '<p>' . __('Configurez vos clés API pour utiliser les services d\'IA.', 'ai-content-studio') . '</p>';
        echo '<p><strong>' . __('Claude API:', 'ai-content-studio') . '</strong> <a href="https://console.anthropic.com/" target="_blank">https://console.anthropic.com/</a></p>';
        echo '<p><strong>' . __('OpenAI API:', 'ai-content-studio') . '</strong> <a href="https://platform.openai.com/" target="_blank">https://platform.openai.com/</a></p>';
    }

    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Paramètres généraux du plugin.', 'ai-content-studio') . '</p>';
    }

    /**
     * API Key field callback
     */
    public function api_key_field_callback($args) {
        $field = $args['field'];
        $value = get_option($field, '');
        $placeholder = $args['placeholder'] ?? '';

        printf(
            '<input type="text" name="%s" value="%s" class="regular-text" placeholder="%s" />',
            esc_attr($field),
            esc_attr($value),
            esc_attr($placeholder)
        );

        if (!empty($value)) {
            echo ' <span style="color: green;">✅ Configurée</span>';
        }
    }

    /**
     * Checkbox field callback
     */
    public function checkbox_field_callback($args) {
        $field = $args['field'];
        $label = $args['label'] ?? '';
        $value = get_option($field, false);

        printf(
            '<label><input type="checkbox" name="%s" value="1" %s /> %s</label>',
            esc_attr($field),
            checked(1, $value, false),
            esc_html($label)
        );
    }

    /**
     * Language field callback
     */
    public function language_field_callback() {
        $value = get_option('acs_default_language', 'fr');
        $languages = [
            'fr' => 'Français',
            'en' => 'English',
            'es' => 'Español',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
        ];

        echo '<select name="acs_default_language">';
        foreach ($languages as $code => $name) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($code),
                selected($value, $code, false),
                esc_html($name)
            );
        }
        echo '</select>';
    }

    /**
     * Page select field callback
     */
    public function page_select_field_callback($args) {
        $field = $args['field'];
        $value = get_option($field, 0);

        wp_dropdown_pages([
            'name' => $field,
            'selected' => $value,
            'show_option_none' => __('-- Sélectionner une page --', 'ai-content-studio'),
            'option_none_value' => 0,
        ]);

        echo '<p class="description">' . __('Page où le shortcode sera automatiquement ajouté (optionnel)', 'ai-content-studio') . '</p>';
    }
}
