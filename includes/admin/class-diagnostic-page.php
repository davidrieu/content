<?php
/**
 * Diagnostic Admin Page
 *
 * @package ACS\Admin
 */

namespace ACS\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Diagnostic_Page class
 */
class Diagnostic_Page {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 100);
        add_action('admin_post_acs_reset_migration', [$this, 'handle_reset_migration']);
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'ai-content-studio',
            __('Diagnostic', 'ai-content-studio'),
            __('Diagnostic', 'ai-content-studio'),
            'manage_options',
            'acs-diagnostic',
            [$this, 'render_page']
        );
    }

    /**
     * Handle migration reset
     */
    public function handle_reset_migration() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'ai-content-studio'));
        }

        // Check nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'acs_reset_migration')) {
            wp_die(__('Erreur de sécurité.', 'ai-content-studio'));
        }

        // Reset migration flag
        delete_option('acs_profiles_to_projects_migration_completed');

        // Redirect back
        wp_redirect(add_query_arg([
            'page' => 'acs-diagnostic',
            'reset' => 'success',
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Render diagnostic page
     */
    public function render_page() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . ACS_TABLE_PREFIX;

        // Get diagnostic data
        $projects_table = $table_prefix . 'projects';
        $profiles_table = $table_prefix . 'business_profiles';
        $content_plans_table = $table_prefix . 'content_plans';
        $social_posts_table = $table_prefix . 'social_posts';
        $blog_articles_table = $table_prefix . 'blog_articles';

        $tables_status = [];

        // Check projects table
        $projects_exists = $wpdb->get_var("SHOW TABLES LIKE '$projects_table'") === $projects_table;
        $projects_count = 0;
        $projects_data = [];
        if ($projects_exists) {
            $projects_count = $wpdb->get_var("SELECT COUNT(*) FROM $projects_table");
            $projects_data = $wpdb->get_results("SELECT id, user_id, project_name, is_active FROM $projects_table ORDER BY id DESC LIMIT 10", ARRAY_A);
        }

        // Check profiles table
        $profiles_exists = $wpdb->get_var("SHOW TABLES LIKE '$profiles_table'") === $profiles_table;
        $profiles_count = 0;
        $profiles_data = [];
        if ($profiles_exists) {
            $profiles_count = $wpdb->get_var("SELECT COUNT(*) FROM $profiles_table");
            $profiles_data = $wpdb->get_results("SELECT id, user_id, business_name FROM $profiles_table ORDER BY id DESC LIMIT 10", ARRAY_A);
        }

        // Check project_id columns
        $has_project_id = [];
        foreach (['content_plans', 'social_posts', 'blog_articles', 'blog_strategies'] as $table_name) {
            $full_table = $table_prefix . $table_name;
            if ($wpdb->get_var("SHOW TABLES LIKE '$full_table'") === $full_table) {
                $columns = $wpdb->get_col("DESCRIBE $full_table", 0);
                $has_project_id[$table_name] = in_array('project_id', $columns);
            }
        }

        // Check migration flag
        $migration_completed = get_option('acs_profiles_to_projects_migration_completed', false);

        // Get current user
        $current_user = wp_get_current_user();

        ?>
        <div class="wrap">
            <h1><?php _e('Diagnostic Multi-Projets', 'ai-content-studio'); ?></h1>

            <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Flag de migration réinitialisé avec succès. Vous pouvez maintenant réexécuter la migration.', 'ai-content-studio'); ?></p>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width: 1200px;">
                <h2>📊 État des Tables</h2>

                <table class="widefat" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Existe</th>
                            <th>Nombre d'entrées</th>
                            <th>Colonne project_id</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>projects</strong></td>
                            <td><?php echo $projects_exists ? '<span style="color: green;">✓ OUI</span>' : '<span style="color: red;">✗ NON</span>'; ?></td>
                            <td><?php echo $projects_count; ?></td>
                            <td>N/A (table principale)</td>
                        </tr>
                        <tr>
                            <td><strong>business_profiles</strong></td>
                            <td><?php echo $profiles_exists ? '<span style="color: green;">✓ OUI</span>' : '<span style="color: red;">✗ NON</span>'; ?></td>
                            <td><?php echo $profiles_count; ?></td>
                            <td>N/A (ancienne table)</td>
                        </tr>
                        <?php foreach ($has_project_id as $table => $has_col): ?>
                        <tr>
                            <td><?php echo esc_html($table); ?></td>
                            <td><span style="color: green;">✓ OUI</span></td>
                            <td>-</td>
                            <td><?php echo $has_col ? '<span style="color: green;">✓ OUI</span>' : '<span style="color: red;">✗ NON</span>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card" style="max-width: 1200px; margin-top: 20px;">
                <h2>🔄 État de la Migration</h2>
                <p><strong>Migration marquée comme complétée:</strong>
                    <?php echo $migration_completed ? '<span style="color: green;">OUI ✓</span>' : '<span style="color: orange;">NON</span>'; ?>
                </p>
            </div>

            <?php if ($projects_count > 0): ?>
            <div class="card" style="max-width: 1200px; margin-top: 20px;">
                <h2>📦 Projets Existants</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User ID</th>
                            <th>Nom du Projet</th>
                            <th>Actif</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects_data as $project): ?>
                        <tr>
                            <td><?php echo esc_html($project['id']); ?></td>
                            <td><?php echo esc_html($project['user_id']); ?></td>
                            <td><?php echo esc_html($project['project_name']); ?></td>
                            <td><?php echo $project['is_active'] ? '<span style="color: green;">✓ Oui</span>' : 'Non'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if ($profiles_count > 0): ?>
            <div class="card" style="max-width: 1200px; margin-top: 20px;">
                <h2>👤 Profils Existants (Ancienne Table)</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User ID</th>
                            <th>Nom de l'Entreprise</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profiles_data as $profile): ?>
                        <tr>
                            <td><?php echo esc_html($profile['id']); ?></td>
                            <td><?php echo esc_html($profile['user_id']); ?></td>
                            <td><?php echo esc_html($profile['business_name']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="card" style="max-width: 1200px; margin-top: 20px;">
                <h2>💡 Diagnostic et Recommandations</h2>

                <?php if ($projects_count === 0 && $profiles_count > 0 && $migration_completed): ?>
                    <div class="notice notice-warning inline">
                        <p><strong>⚠️ PROBLÈME DÉTECTÉ</strong></p>
                        <p>Vous avez <strong><?php echo $profiles_count; ?> profil(s)</strong> dans l'ancienne table mais <strong>0 projet</strong> dans la nouvelle table.</p>
                        <p>La migration est marquée comme complétée mais n'a pas créé de projets.</p>
                    </div>

                    <h3>Solution: Réinitialiser et réexécuter la migration</h3>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                          onsubmit="return confirm('Êtes-vous sûr de vouloir réinitialiser la migration ?');">
                        <?php wp_nonce_field('acs_reset_migration'); ?>
                        <input type="hidden" name="action" value="acs_reset_migration">
                        <p>
                            <button type="submit" class="button button-primary button-large">
                                🔄 Réinitialiser la Migration
                            </button>
                        </p>
                        <p class="description">
                            Cela va permettre de réexécuter la migration depuis la page Migrations.
                        </p>
                    </form>

                <?php elseif (!$projects_exists): ?>
                    <div class="notice notice-error inline">
                        <p><strong>❌ ERREUR CRITIQUE</strong></p>
                        <p>La table 'projects' n'existe pas.</p>
                        <p><strong>Solution:</strong> Désactiver et réactiver le plugin pour créer les tables.</p>
                    </div>

                <?php elseif ($projects_count === 0 && $profiles_count === 0): ?>
                    <div class="notice notice-info inline">
                        <p><strong>ℹ️ INFORMATION</strong></p>
                        <p>Aucun profil ni projet trouvé. C'est normal si vous n'avez pas encore fait l'onboarding.</p>
                        <p>L'onboarding va créer votre premier projet automatiquement.</p>
                    </div>

                <?php elseif ($projects_count > 0): ?>
                    <div class="notice notice-success inline">
                        <p><strong>✅ TOUT EST OK</strong></p>
                        <p>Vous avez <strong><?php echo $projects_count; ?> projet(s)</strong>.</p>
                        <p>Le ProjectSwitcher devrait s'afficher dans l'interface.</p>
                        <p><strong>Utilisateur actuel:</strong> <?php echo esc_html($current_user->user_login); ?> (ID: <?php echo $current_user->ID; ?>)</p>
                    </div>

                    <?php
                    // Check if current user has active project
                    $user_active_project = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $projects_table WHERE user_id = %d AND is_active = 1",
                        $current_user->ID
                    ), ARRAY_A);

                    if (!$user_active_project && $projects_count > 0): ?>
                        <div class="notice notice-warning inline" style="margin-top: 15px;">
                            <p><strong>⚠️ ATTENTION</strong></p>
                            <p>Des projets existent mais vous n'avez pas de projet actif.</p>
                            <p>Cela peut arriver si les projets appartiennent à un autre utilisateur.</p>
                            <p><strong>Vérifiez:</strong> Les projets ci-dessus correspondent-ils à votre User ID (<?php echo $current_user->ID; ?>) ?</p>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>

            <div class="card" style="max-width: 1200px; margin-top: 20px;">
                <h2>🔧 Actions Supplémentaires</h2>
                <p><a href="<?php echo admin_url('admin.php?page=acs-migrations'); ?>" class="button">Aller à la page Migrations</a></p>
                <p><a href="<?php echo admin_url('plugins.php'); ?>" class="button">Gérer les Extensions</a></p>
            </div>
        </div>
        <?php
    }
}
