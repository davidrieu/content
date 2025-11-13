<?php
/**
 * Migrations Admin Page
 *
 * @package ACS\Admin
 */

namespace ACS\Admin;

use ACS\Migrations\Profiles_To_Projects_Migration;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Migrations_Page class
 */
class Migrations_Page {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 99);
        add_action('admin_post_acs_run_migration', [$this, 'handle_migration']);
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'ai-content-studio',
            __('Migrations', 'ai-content-studio'),
            __('Migrations', 'ai-content-studio'),
            'manage_options',
            'acs-migrations',
            [$this, 'render_page']
        );
    }

    /**
     * Handle migration form submission
     */
    public function handle_migration() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'ai-content-studio'));
        }

        // Check nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'acs_run_migration')) {
            wp_die(__('Erreur de sécurité.', 'ai-content-studio'));
        }

        $migration_type = sanitize_text_field($_POST['migration_type'] ?? '');

        if ($migration_type === 'profiles_to_projects') {
            $result = Profiles_To_Projects_Migration::run();

            // Redirect back with result
            $redirect_url = add_query_arg([
                'page' => 'acs-migrations',
                'migration_result' => $result['success'] ? 'success' : 'error',
                'message' => urlencode($result['message']),
            ], admin_url('admin.php'));

            wp_redirect($redirect_url);
            exit;
        }

        // Invalid migration type
        wp_die(__('Type de migration invalide.', 'ai-content-studio'));
    }

    /**
     * Render admin page
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Migrations Base de Données', 'ai-content-studio'); ?></h1>

            <?php
            // Show migration result message
            if (isset($_GET['migration_result'])) {
                $result_class = $_GET['migration_result'] === 'success' ? 'updated' : 'error';
                $message = isset($_GET['message']) ? urldecode($_GET['message']) : '';
                ?>
                <div class="notice <?php echo esc_attr($result_class); ?> is-dismissible">
                    <p><?php echo esc_html($message); ?></p>
                </div>
                <?php
            }
            ?>

            <div class="card" style="max-width: 800px;">
                <h2><?php _e('Migration: Profils → Projets', 'ai-content-studio'); ?></h2>

                <?php if (Profiles_To_Projects_Migration::is_completed()): ?>
                    <div class="notice notice-success inline">
                        <p><strong><?php _e('✓ Migration déjà exécutée', 'ai-content-studio'); ?></strong></p>
                        <p><?php _e('Cette migration a déjà été appliquée à votre base de données.', 'ai-content-studio'); ?></p>
                    </div>
                <?php else: ?>
                    <p><?php _e('Cette migration convertit les anciens profils d\'entreprise en projets, permettant la gestion de plusieurs clients.', 'ai-content-studio'); ?></p>

                    <p><strong><?php _e('Ce qui sera fait:', 'ai-content-studio'); ?></strong></p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><?php _e('Lecture de tous les profils existants dans la table <code>business_profiles</code>', 'ai-content-studio'); ?></li>
                        <li><?php _e('Création d\'un projet pour chaque profil dans la nouvelle table <code>projects</code>', 'ai-content-studio'); ?></li>
                        <li><?php _e('Conservation de toutes les données (nom, secteur, objectifs, etc.)', 'ai-content-studio'); ?></li>
                        <li><?php _e('Chaque projet créé sera automatiquement activé pour son utilisateur', 'ai-content-studio'); ?></li>
                        <li><?php _e('Les données originales restent intactes dans <code>business_profiles</code>', 'ai-content-studio'); ?></li>
                    </ul>

                    <p><strong style="color: #d63638;"><?php _e('⚠️ Recommandation:', 'ai-content-studio'); ?></strong></p>
                    <p><?php _e('Il est recommandé de faire une sauvegarde de votre base de données avant d\'exécuter cette migration.', 'ai-content-studio'); ?></p>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                          onsubmit="return confirm('<?php _e('Êtes-vous sûr de vouloir exécuter cette migration ?', 'ai-content-studio'); ?>');">
                        <?php wp_nonce_field('acs_run_migration'); ?>
                        <input type="hidden" name="action" value="acs_run_migration">
                        <input type="hidden" name="migration_type" value="profiles_to_projects">

                        <p>
                            <button type="submit" class="button button-primary button-large">
                                <?php _e('Exécuter la migration', 'ai-content-studio'); ?>
                            </button>
                        </p>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('Informations', 'ai-content-studio'); ?></h2>
                <p><?php _e('Après la migration:', 'ai-content-studio'); ?></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><?php _e('Les utilisateurs verront un nouveau sélecteur de projet en haut du Dashboard', 'ai-content-studio'); ?></li>
                    <li><?php _e('Ils pourront ajouter plusieurs projets clients via le bouton "Ajouter un projet"', 'ai-content-studio'); ?></li>
                    <li><?php _e('Chaque projet aura ses propres données (posts, articles, stratégies)', 'ai-content-studio'); ?></li>
                    <li><?php _e('Le profil API retournera automatiquement les données du projet actif', 'ai-content-studio'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
}
