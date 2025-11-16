<?php
/**
 * Translations Admin Page
 *
 * @package ACS\Admin
 */

namespace ACS\Admin;

use ACS\Services\Translation_Service;
use ACS\Data\Language_Config;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Translations_Admin class
 */
class Translations_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page'], 100);
        add_action('admin_post_acs_generate_translations', [$this, 'handle_generate_translations']);
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'ai-content-studio',
            __('Traductions', 'ai-content-studio'),
            __('Traductions', 'ai-content-studio'),
            'manage_options',
            'ai-content-studio-translations',
            [$this, 'render_page']
        );
    }

    /**
     * Render page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'ai-content-studio'));
        }

        $languages = Language_Config::get_all();
        $languages_dir = ACS_PLUGIN_DIR . 'languages';

        ?>
        <div class="wrap">
            <h1><?php _e('Gestion des Traductions', 'ai-content-studio'); ?></h1>

            <div class="card" style="max-width: 1200px;">
                <h2><?php _e('Traductions Disponibles', 'ai-content-studio'); ?></h2>
                <p><?php _e('Générez automatiquement les traductions pour toutes les langues supportées.', 'ai-content-studio'); ?></p>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php _e('Drapeau', 'ai-content-studio'); ?></th>
                            <th><?php _e('Langue', 'ai-content-studio'); ?></th>
                            <th><?php _e('Code', 'ai-content-studio'); ?></th>
                            <th><?php _e('Statut', 'ai-content-studio'); ?></th>
                            <th><?php _e('Action', 'ai-content-studio'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($languages as $code => $lang): ?>
                            <?php
                            $file_exists = file_exists("{$languages_dir}/translations-{$code}.json");
                            $status_class = $file_exists ? 'success' : 'warning';
                            $status_text = $file_exists ? __('Traduit', 'ai-content-studio') : __('Non traduit', 'ai-content-studio');
                            ?>
                            <tr>
                                <td style="font-size: 24px; text-align: center;"><?php echo $lang['flag']; ?></td>
                                <td><strong><?php echo esc_html($lang['native_name']); ?></strong></td>
                                <td><code><?php echo esc_html($code); ?></code></td>
                                <td>
                                    <span class="dashicons dashicons-<?php echo $file_exists ? 'yes-alt' : 'warning'; ?>"
                                          style="color: <?php echo $file_exists ? '#46b450' : '#f0b849'; ?>;"></span>
                                    <?php echo $status_text; ?>
                                </td>
                                <td>
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                        <input type="hidden" name="action" value="acs_generate_translations">
                                        <input type="hidden" name="lang_code" value="<?php echo esc_attr($code); ?>">
                                        <?php wp_nonce_field('acs_generate_translations'); ?>
                                        <button type="submit" class="button button-small">
                                            <?php echo $file_exists ? __('Régénérer', 'ai-content-studio') : __('Générer', 'ai-content-studio'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p style="margin-top: 20px;">
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="acs_generate_translations">
                        <input type="hidden" name="lang_code" value="all">
                        <?php wp_nonce_field('acs_generate_translations'); ?>
                        <button type="submit" class="button button-primary button-hero">
                            <?php _e('Générer Toutes les Traductions (30 langues)', 'ai-content-studio'); ?>
                        </button>
                    </form>
                </p>

                <div class="notice notice-info inline" style="margin-top: 20px;">
                    <p>
                        <strong><?php _e('Note:', 'ai-content-studio'); ?></strong>
                        <?php _e('La génération de toutes les traductions peut prendre plusieurs minutes et consommer des crédits API Claude.', 'ai-content-studio'); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle generate translations
     */
    public function handle_generate_translations() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'ai-content-studio'));
        }

        check_admin_referer('acs_generate_translations');

        $lang_code = $_POST['lang_code'] ?? '';

        if ($lang_code === 'all') {
            // Generate all translations
            $languages = Language_Config::get_all();
            $count = 0;

            foreach ($languages as $code => $lang) {
                Translation_Service::generate_translations($code);
                $count++;
            }

            add_settings_error(
                'acs_translations',
                'translations_generated',
                sprintf(__('%d traductions ont été générées avec succès !', 'ai-content-studio'), $count),
                'success'
            );
        } else {
            // Generate single language
            Translation_Service::generate_translations($lang_code);

            $lang = Language_Config::get($lang_code);
            add_settings_error(
                'acs_translations',
                'translation_generated',
                sprintf(__('Traduction générée pour: %s', 'ai-content-studio'), $lang['native_name']),
                'success'
            );
        }

        set_transient('acs_admin_notices', get_settings_errors(), 30);

        wp_redirect(admin_url('admin.php?page=ai-content-studio-translations'));
        exit;
    }
}
