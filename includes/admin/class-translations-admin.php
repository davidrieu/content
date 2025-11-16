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
        add_action('wp_ajax_acs_generate_single_translation', [$this, 'ajax_generate_single_translation']);
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
        $languages_dir = ACS_TRANSLATIONS_DIR;

        // Diagnostic info
        $api_key = get_option('acs_claude_api_key', '');
        $dir_exists = file_exists($languages_dir);
        $dir_writable = is_writable($languages_dir);

        ?>
        <div class="wrap">
            <h1><?php _e('Gestion des Traductions', 'ai-content-studio'); ?></h1>

            <!-- DIAGNOSTIC SECTION -->
            <div class="card" style="max-width: 1200px; margin-bottom: 20px; <?php echo (!$api_key || !$dir_exists) ? 'border-left: 4px solid #dc3232;' : ''; ?>">
                <h2>🔍 Diagnostic Système</h2>
                <table class="form-table">
                    <tr>
                        <th>Clé API Claude :</th>
                        <td>
                            <?php if ($api_key): ?>
                                <span style="color: #46b450;">✅ Configurée</span>
                                <code><?php echo substr($api_key, 0, 15); ?>...</code>
                            <?php else: ?>
                                <span style="color: #dc3232;">❌ NON CONFIGURÉE</span>
                                <p>Allez dans <a href="<?php echo admin_url('admin.php?page=ai-content-studio-settings'); ?>">Paramètres</a> pour configurer votre clé API Claude</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Répertoire traductions :</th>
                        <td>
                            <code><?php echo $languages_dir; ?></code><br>
                            <?php if ($dir_exists): ?>
                                <span style="color: #46b450;">✅ Existe</span>
                            <?php else: ?>
                                <span style="color: #dc3232;">❌ N'existe pas</span>
                            <?php endif; ?>

                            <?php if ($dir_exists && $dir_writable): ?>
                                <span style="color: #46b450;">✅ Inscriptible</span>
                            <?php elseif ($dir_exists): ?>
                                <span style="color: #dc3232;">❌ Pas inscriptible</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Fichier source :</th>
                        <td>
                            <?php
                            $source_file = ACS_PLUGIN_DIR . '/languages/translations-en.json';
                            if (file_exists($source_file)) {
                                $source_data = json_decode(file_get_contents($source_file), true);
                                $source_keys = count($source_data);
                                echo '<span style="color: #46b450;">✅ ' . $source_keys . ' clés</span>';
                                echo '<br><code>' . $source_file . '</code>';
                            } else {
                                echo '<span style="color: #dc3232;">❌ Fichier manquant</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card" style="max-width: 1200px;">
                <h2><?php _e('Traductions Disponibles', 'ai-content-studio'); ?></h2>
                <p><?php _e('Générez automatiquement les traductions pour toutes les langues supportées.', 'ai-content-studio'); ?></p>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php _e('Drapeau', 'ai-content-studio'); ?></th>
                            <th><?php _e('Langue', 'ai-content-studio'); ?></th>
                            <th><?php _e('Code', 'ai-content-studio'); ?></th>
                            <th style="width: 150px;"><?php _e('Statut', 'ai-content-studio'); ?></th>
                            <th style="width: 250px;">Vérification</th>
                            <th><?php _e('Action', 'ai-content-studio'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($languages as $code => $lang): ?>
                            <?php
                            $file_exists = file_exists("{$languages_dir}/translations-{$code}.json");
                            $status_class = $file_exists ? 'success' : 'warning';
                            $status_text = $file_exists ? __('Traduit', 'ai-content-studio') : __('Non traduit', 'ai-content-studio');

                            // Vérifier si c'est VRAIMENT traduit
                            $is_really_translated = false;
                            $test_sample = '';
                            if ($file_exists) {
                                $content = file_get_contents("{$languages_dir}/translations-{$code}.json");
                                $data = json_decode($content, true);
                                if (is_array($data)) {
                                    $num_keys = count($data);
                                    // Test avec "Bienvenue"
                                    $test_value = $data['Bienvenue'] ?? '';
                                    if ($code === 'en') {
                                        $is_really_translated = ($test_value === 'Welcome');
                                        $test_sample = $test_value;
                                    } elseif ($code === 'fr') {
                                        $is_really_translated = true;
                                        $test_sample = 'Langue par défaut';
                                    } else {
                                        // Pour autres langues, vérifier que ce n'est PAS "Bienvenue" ou "Welcome"
                                        $is_really_translated = ($test_value !== 'Bienvenue' && $test_value !== 'Welcome' && !empty($test_value));
                                        $test_sample = $test_value ?: 'Vide!';
                                    }
                                }
                            }
                            ?>
                            <tr>
                                <td style="font-size: 24px; text-align: center;"><?php echo $lang['flag']; ?></td>
                                <td><strong><?php echo esc_html($lang['native_name']); ?></strong></td>
                                <td><code><?php echo esc_html($code); ?></code></td>
                                <td>
                                    <?php if ($file_exists): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                        Fichier existe (<?php echo $num_keys ?? 0; ?> clés)
                                    <?php else: ?>
                                        <span class="dashicons dashicons-warning" style="color: #f0b849;"></span>
                                        Non généré
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($file_exists): ?>
                                        <?php if ($is_really_translated): ?>
                                            <span style="color: #46b450;">✅ Vraiment traduit</span><br>
                                            <small style="color: #666;">Ex: "<?php echo esc_html(substr($test_sample, 0, 30)); ?>"</small>
                                        <?php else: ?>
                                            <span style="color: #dc3232;">❌ PAS TRADUIT!</span><br>
                                            <small style="color: #dc3232;">Montre: "<?php echo esc_html($test_sample); ?>"</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form data-single-lang="<?php echo esc_attr($code); ?>" style="display: inline;">
                                        <button type="submit" class="button button-small <?php echo (!$is_really_translated && $file_exists) ? 'button-primary' : ''; ?>">
                                            <?php echo $file_exists ? __('Régénérer', 'ai-content-studio') : __('Générer', 'ai-content-studio'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p style="margin-top: 20px;">
                    <button id="generate-all-btn" class="button button-primary button-hero">
                        <?php _e('Générer Toutes les Traductions (30 langues)', 'ai-content-studio'); ?>
                    </button>
                </p>

                <div class="notice notice-info inline" style="margin-top: 20px;">
                    <p>
                        <strong><?php _e('Note:', 'ai-content-studio'); ?></strong>
                        <?php _e('La génération de toutes les traductions peut prendre plusieurs minutes et consommer des crédits API Claude.', 'ai-content-studio'); ?>
                    </p>
                </div>

                <!-- Progress Log -->
                <div id="translation-progress" style="display: none; margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #ccc; border-radius: 4px; max-height: 400px; overflow-y: auto;">
                    <h3><?php _e('Progression de la génération', 'ai-content-studio'); ?></h3>
                    <div id="translation-log" style="font-family: monospace; font-size: 12px; line-height: 1.6;">
                    </div>
                    <div id="translation-stats" style="margin-top: 15px; padding: 10px; background: #f0f0f0; border-radius: 4px; display: none;">
                        <strong><?php _e('Résumé:', 'ai-content-studio'); ?></strong>
                        <div id="stats-content"></div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Handle "Generate All" button
            $('#generate-all-btn').on('click', function(e) {
                e.preventDefault();

                if (!confirm('<?php _e('Générer toutes les traductions ? Cette opération peut prendre plusieurs minutes.', 'ai-content-studio'); ?>')) {
                    return;
                }

                const $btn = $(this);
                const $progress = $('#translation-progress');
                const $log = $('#translation-log');
                const $stats = $('#translation-stats');
                const $statsContent = $('#stats-content');

                // Show progress panel
                $progress.show();
                $log.html('');
                $stats.hide();
                $btn.prop('disabled', true).text('<?php _e('Génération en cours...', 'ai-content-studio'); ?>');

                // Get all languages
                const languages = <?php echo json_encode(Language_Config::get_all()); ?>;
                const langCodes = Object.keys(languages);
                let completed = 0;
                let failed = 0;
                let startTime = Date.now();

                function addLog(message, type = 'info') {
                    const timestamp = new Date().toLocaleTimeString();
                    const colors = {
                        'info': '#0073aa',
                        'success': '#46b450',
                        'error': '#dc3232',
                        'warning': '#f0b849'
                    };
                    const color = colors[type] || colors['info'];

                    $log.append(
                        '<div style="margin-bottom: 5px; color: ' + color + ';">' +
                        '<span style="color: #666;">[' + timestamp + ']</span> ' +
                        message +
                        '</div>'
                    );

                    // Auto-scroll to bottom
                    $log.parent()[0].scrollTop = $log.parent()[0].scrollHeight;
                }

                function generateNext(index) {
                    if (index >= langCodes.length) {
                        // All done
                        const duration = Math.round((Date.now() - startTime) / 1000);
                        addLog('✅ Génération terminée ! (' + duration + 's)', 'success');

                        // Show stats
                        $statsContent.html(
                            '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px;">' +
                            '<div><strong><?php _e('Total:', 'ai-content-studio'); ?></strong> ' + langCodes.length + ' langues</div>' +
                            '<div style="color: #46b450;"><strong><?php _e('Succès:', 'ai-content-studio'); ?></strong> ' + completed + '</div>' +
                            '<div style="color: #dc3232;"><strong><?php _e('Échecs:', 'ai-content-studio'); ?></strong> ' + failed + '</div>' +
                            '<div><strong><?php _e('Durée:', 'ai-content-studio'); ?></strong> ' + duration + 's</div>' +
                            '<div><strong><?php _e('Moyenne:', 'ai-content-studio'); ?></strong> ' + Math.round(duration / langCodes.length) + 's/langue</div>' +
                            '</div>'
                        );
                        $stats.show();

                        $btn.prop('disabled', false).text('<?php _e('Générer Toutes les Traductions (30 langues)', 'ai-content-studio'); ?>');

                        // Refresh page after 3 seconds
                        setTimeout(function() {
                            window.location.reload();
                        }, 3000);
                        return;
                    }

                    const langCode = langCodes[index];
                    const langInfo = languages[langCode];
                    const progress = index + 1;

                    addLog(`${langInfo.flag} [${progress}/${langCodes.length}] Génération de ${langInfo.native_name} (${langCode})...`, 'info');

                    // AJAX call to generate this language
                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'acs_generate_single_translation',
                            lang_code: langCode,
                            nonce: '<?php echo wp_create_nonce('acs_generate_translation'); ?>'
                        },
                        timeout: 120000, // 2 minutes per language
                        success: function(response) {
                            if (response.success) {
                                completed++;
                                addLog(`✓ ${langInfo.native_name} - Terminée (${response.data.count} traductions)`, 'success');
                            } else {
                                failed++;
                                addLog(`✗ ${langInfo.native_name} - Erreur: ${response.data.message}`, 'error');
                            }
                            // Generate next
                            generateNext(index + 1);
                        },
                        error: function(xhr, status, error) {
                            failed++;
                            addLog(`✗ ${langInfo.native_name} - Erreur réseau: ${error}`, 'error');
                            // Continue anyway
                            generateNext(index + 1);
                        }
                    });
                }

                // Start generation
                addLog('🚀 Début de la génération de ' + langCodes.length + ' langues...', 'info');
                generateNext(0);
            });

            // Handle individual generate buttons
            $('form[data-single-lang]').on('submit', function(e) {
                e.preventDefault();

                const $form = $(this);
                const $btn = $form.find('button[type="submit"]');
                const langCode = $form.data('single-lang');
                const $progress = $('#translation-progress');
                const $log = $('#translation-log');

                $progress.show();
                $log.html('');
                $btn.prop('disabled', true).text('<?php _e('Génération...', 'ai-content-studio'); ?>');

                function addLog(message, type = 'info') {
                    const timestamp = new Date().toLocaleTimeString();
                    const colors = {
                        'info': '#0073aa',
                        'success': '#46b450',
                        'error': '#dc3232'
                    };
                    const color = colors[type] || colors['info'];

                    $log.append(
                        '<div style="margin-bottom: 5px; color: ' + color + ';">' +
                        '<span style="color: #666;">[' + timestamp + ']</span> ' +
                        message +
                        '</div>'
                    );
                }

                addLog('Génération de la traduction pour: ' + langCode + '...', 'info');

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'acs_generate_single_translation',
                        lang_code: langCode,
                        nonce: '<?php echo wp_create_nonce('acs_generate_translation'); ?>'
                    },
                    timeout: 120000,
                    success: function(response) {
                        if (response.success) {
                            addLog('✓ Traduction générée avec succès (' + response.data.count + ' chaînes)', 'success');
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        } else {
                            addLog('✗ Erreur: ' + response.data.message, 'error');
                            $btn.prop('disabled', false).text('<?php _e('Générer', 'ai-content-studio'); ?>');
                        }
                    },
                    error: function(xhr, status, error) {
                        addLog('✗ Erreur réseau: ' + error, 'error');
                        $btn.prop('disabled', false).text('<?php _e('Générer', 'ai-content-studio'); ?>');
                    }
                });
            });
        });
        </script>
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

    /**
     * AJAX handler for generating a single language translation
     */
    public function ajax_generate_single_translation() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Vous n\'avez pas les permissions nécessaires.', 'ai-content-studio')
            ]);
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'acs_generate_translation')) {
            wp_send_json_error([
                'message' => __('Nonce invalide.', 'ai-content-studio')
            ]);
        }

        $lang_code = $_POST['lang_code'] ?? '';

        if (empty($lang_code)) {
            wp_send_json_error([
                'message' => __('Code de langue manquant.', 'ai-content-studio')
            ]);
        }

        try {
            // Generate translations for this language
            $translations = Translation_Service::generate_translations($lang_code);

            $lang = Language_Config::get($lang_code);

            wp_send_json_success([
                'message' => sprintf(__('Traduction générée pour: %s', 'ai-content-studio'), $lang['native_name']),
                'lang_code' => $lang_code,
                'count' => count($translations)
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}
