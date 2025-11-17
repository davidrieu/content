<?php
/**
 * Translation Migration Tool
 *
 * Allows automatic update of translation files from WordPress admin
 *
 * @package ACS\Admin
 */

namespace ACS\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Translation_Migration class
 */
class Translation_Migration {

    /**
     * Current translation version
     */
    const TRANSLATION_VERSION = '1.2.0';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 100);
        add_action('admin_post_acs_migrate_translations', [$this, 'handle_migration']);
        add_action('admin_notices', [$this, 'show_migration_notice']);
    }

    /**
     * Add migration page to admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            null, // Hidden from menu, accessible via direct link
            __('Migration des traductions', 'ai-content-studio'),
            __('Migration des traductions', 'ai-content-studio'),
            'manage_options',
            'acs-translation-migration',
            [$this, 'render_migration_page']
        );
    }

    /**
     * Show admin notice if translations need update
     */
    public function show_migration_notice() {
        // Only show on ACS pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'ai-content-studio') === false) {
            return;
        }

        if ($this->needs_migration()) {
            $migration_url = admin_url('admin.php?page=acs-translation-migration');
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php _e('AI Content Studio - Mise à jour disponible', 'ai-content-studio'); ?></strong>
                </p>
                <p>
                    <?php
                    printf(
                        __('Une nouvelle version des traductions est disponible (%s). Vos traductions actuelles sont obsolètes.', 'ai-content-studio'),
                        self::TRANSLATION_VERSION
                    );
                    ?>
                </p>
                <p>
                    <a href="<?php echo esc_url($migration_url); ?>" class="button button-primary">
                        <?php _e('Mettre à jour les traductions', 'ai-content-studio'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Check if translations need migration
     */
    public function needs_migration() {
        $current_version = get_option('acs_translation_version', '0.0.0');
        return version_compare($current_version, self::TRANSLATION_VERSION, '<');
    }

    /**
     * Get translation file statistics
     */
    private function get_translation_stats($file_path) {
        if (!file_exists($file_path)) {
            return [
                'exists' => false,
                'count' => 0,
                'size' => 0,
                'version' => '0.0.0'
            ];
        }

        $content = file_get_contents($file_path);
        $translations = json_decode($content, true);

        return [
            'exists' => true,
            'count' => is_array($translations) ? count($translations) : 0,
            'size' => filesize($file_path),
            'modified' => date('Y-m-d H:i:s', filemtime($file_path)),
            'version' => get_option('acs_translation_version', '0.0.0')
        ];
    }

    /**
     * Render migration page
     */
    public function render_migration_page() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'ai-content-studio'));
        }

        $source_file = ACS_PLUGIN_DIR . 'languages/translations-en.json';
        $target_file = ACS_TRANSLATIONS_DIR . '/translations-en.json';

        $source_stats = $this->get_translation_stats($source_file);
        $target_stats = $this->get_translation_stats($target_file);
        $needs_migration = $this->needs_migration();

        ?>
        <div class="wrap">
            <h1>
                <?php _e('Migration des traductions', 'ai-content-studio'); ?>
                <span style="font-size: 14px; color: #666; font-weight: normal;">
                    Version <?php echo esc_html(self::TRANSLATION_VERSION); ?>
                </span>
            </h1>

            <div class="notice notice-info" style="margin-top: 20px;">
                <p>
                    <strong><?php _e('Qu\'est-ce que cet outil ?', 'ai-content-studio'); ?></strong>
                </p>
                <p>
                    <?php _e('Cet assistant met à jour automatiquement vos fichiers de traductions pour inclure toutes les nouvelles clés de traduction. Il copie les fichiers sources du plugin vers le répertoire de traductions actif.', 'ai-content-studio'); ?>
                </p>
            </div>

            <div style="max-width: 800px; margin-top: 30px;">
                <h2><?php _e('État des traductions', 'ai-content-studio'); ?></h2>

                <table class="widefat" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th style="width: 30%;"><?php _e('Fichier', 'ai-content-studio'); ?></th>
                            <th style="width: 20%;"><?php _e('Statut', 'ai-content-studio'); ?></th>
                            <th style="width: 15%;"><?php _e('Traductions', 'ai-content-studio'); ?></th>
                            <th style="width: 15%;"><?php _e('Taille', 'ai-content-studio'); ?></th>
                            <th style="width: 20%;"><?php _e('Dernière modification', 'ai-content-studio'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong><?php _e('Source (plugin)', 'ai-content-studio'); ?></strong><br>
                                <code style="font-size: 11px;">languages/translations-en.json</code>
                            </td>
                            <td>
                                <?php if ($source_stats['exists']): ?>
                                    <span style="color: #46b450;">✓ <?php _e('Disponible', 'ai-content-studio'); ?></span>
                                <?php else: ?>
                                    <span style="color: #dc3232;">✗ <?php _e('Manquant', 'ai-content-studio'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo number_format($source_stats['count']); ?></strong> clés</td>
                            <td><?php echo size_format($source_stats['size'], 1); ?></td>
                            <td>
                                <?php if (isset($source_stats['modified'])): ?>
                                    <?php echo esc_html($source_stats['modified']); ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr style="background-color: <?php echo $needs_migration ? '#fff8e5' : '#f0f0f1'; ?>">
                            <td>
                                <strong><?php _e('Actuel (utilisé)', 'ai-content-studio'); ?></strong><br>
                                <code style="font-size: 11px;">wp-content/uploads/acs-translations/translations-en.json</code>
                            </td>
                            <td>
                                <?php if ($target_stats['exists']): ?>
                                    <?php if ($needs_migration): ?>
                                        <span style="color: #f0ad4e;">⚠ <?php _e('Obsolète', 'ai-content-studio'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #46b450;">✓ <?php _e('À jour', 'ai-content-studio'); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #dc3232;">✗ <?php _e('Manquant', 'ai-content-studio'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo number_format($target_stats['count']); ?></strong> clés
                                <?php if ($source_stats['count'] > $target_stats['count']): ?>
                                    <br><span style="color: #dc3232; font-size: 11px;">
                                        (-<?php echo number_format($source_stats['count'] - $target_stats['count']); ?> <?php _e('manquantes', 'ai-content-studio'); ?>)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo size_format($target_stats['size'], 1); ?></td>
                            <td>
                                <?php if (isset($target_stats['modified'])): ?>
                                    <?php echo esc_html($target_stats['modified']); ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php if ($needs_migration): ?>
                    <div class="notice notice-warning inline" style="margin-top: 20px; padding: 15px;">
                        <h3 style="margin-top: 0;"><?php _e('Mise à jour nécessaire', 'ai-content-studio'); ?></h3>
                        <p>
                            <?php
                            printf(
                                __('Votre fichier de traductions contient <strong>%d traductions</strong> mais la version actuelle en contient <strong>%d</strong>.', 'ai-content-studio'),
                                $target_stats['count'],
                                $source_stats['count']
                            );
                            ?>
                        </p>
                        <p>
                            <?php
                            printf(
                                __('Il manque <strong>%d traductions</strong> nécessaires pour le bon fonctionnement de l\'interface (formulaires de login, authentification, etc.).', 'ai-content-studio'),
                                $source_stats['count'] - $target_stats['count']
                            );
                            ?>
                        </p>
                    </div>

                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top: 20px;">
                        <?php wp_nonce_field('acs_migrate_translations', 'acs_migration_nonce'); ?>
                        <input type="hidden" name="action" value="acs_migrate_translations">

                        <button type="submit" class="button button-primary button-hero" style="margin-right: 10px;">
                            <span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
                            <?php _e('Mettre à jour les traductions maintenant', 'ai-content-studio'); ?>
                        </button>

                        <p class="description">
                            <?php _e('Cette opération copiera le fichier source vers le répertoire de traductions actif. Les traductions existantes seront remplacées.', 'ai-content-studio'); ?>
                        </p>
                    </form>
                <?php else: ?>
                    <div class="notice notice-success inline" style="margin-top: 20px; padding: 15px;">
                        <h3 style="margin-top: 0;">
                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            <?php _e('Traductions à jour', 'ai-content-studio'); ?>
                        </h3>
                        <p>
                            <?php _e('Vos traductions sont à la dernière version. Aucune action nécessaire.', 'ai-content-studio'); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <h3><?php _e('Actions supplémentaires', 'ai-content-studio'); ?></h3>

                    <p>
                        <a href="<?php echo admin_url('admin.php?page=acs-translations'); ?>" class="button">
                            <span class="dashicons dashicons-translation" style="margin-top: 4px;"></span>
                            <?php _e('Générer les traductions pour toutes les langues', 'ai-content-studio'); ?>
                        </a>
                    </p>

                    <p class="description">
                        <?php _e('Après la mise à jour, vous devrez régénérer les traductions pour toutes les langues (30 langues disponibles).', 'ai-content-studio'); ?>
                    </p>
                </div>
            </div>
        </div>

        <style>
            .widefat th {
                background-color: #f0f0f1;
                font-weight: 600;
            }
            .widefat td {
                vertical-align: middle;
            }
        </style>
        <?php
    }

    /**
     * Handle translation migration
     */
    public function handle_migration() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'ai-content-studio'));
        }

        // Verify nonce
        if (!isset($_POST['acs_migration_nonce']) || !wp_verify_nonce($_POST['acs_migration_nonce'], 'acs_migrate_translations')) {
            wp_die(__('Erreur de sécurité. Veuillez réessayer.', 'ai-content-studio'));
        }

        $source_file = ACS_PLUGIN_DIR . 'languages/translations-en.json';
        $target_dir = ACS_TRANSLATIONS_DIR;
        $target_file = $target_dir . '/translations-en.json';

        // Check if source file exists
        if (!file_exists($source_file)) {
            wp_die(__('Le fichier source de traductions est introuvable.', 'ai-content-studio'));
        }

        // Create target directory if it doesn't exist
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }

        // Backup existing file if it exists
        if (file_exists($target_file)) {
            $backup_file = $target_file . '.backup-' . date('YmdHis');
            copy($target_file, $backup_file);
        }

        // Copy source to target
        $copy_result = copy($source_file, $target_file);

        if ($copy_result) {
            // Update version
            update_option('acs_translation_version', self::TRANSLATION_VERSION);

            // Clear any cached translations
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }

            // Success message
            $message = sprintf(
                __('Traductions mises à jour avec succès ! Version: %s', 'ai-content-studio'),
                self::TRANSLATION_VERSION
            );

            // Get stats for success message
            $content = file_get_contents($target_file);
            $translations = json_decode($content, true);
            $count = is_array($translations) ? count($translations) : 0;

            $message .= sprintf(
                '<br><strong>%d</strong> %s',
                $count,
                __('traductions installées.', 'ai-content-studio')
            );

            wp_redirect(add_query_arg([
                'page' => 'acs-translation-migration',
                'migration' => 'success',
                'count' => $count
            ], admin_url('admin.php')));
            exit;
        } else {
            wp_die(__('Erreur lors de la copie du fichier de traductions.', 'ai-content-studio'));
        }
    }
}
