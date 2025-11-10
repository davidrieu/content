<?php
/**
 * Diagnostic System Page - WordPress Admin
 *
 * @package ACS\Admin\Views
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load Diagnostic_Endpoint class if not already loaded
if (!class_exists('ACS\\API\\Diagnostic_Endpoint')) {
    require_once ACS_PLUGIN_DIR . 'includes/api/class-diagnostic-endpoint.php';
}

// Get diagnostic data directly
$diagnostic = \ACS\API\Diagnostic_Endpoint::get_diagnostic_data();
$recommendations = \ACS\API\Diagnostic_Endpoint::get_recommendations_data($diagnostic);
?>

<div class="wrap">
    <h1><?php echo esc_html__('Diagnostic Système', 'ai-content-studio'); ?></h1>

    <?php if ($diagnostic): ?>

    <!-- Recommendations Section -->
    <?php if (!empty($recommendations)): ?>
    <div class="notice notice-warning" style="margin-top: 20px; padding: 20px; border-left: 4px solid #f59e0b;">
        <h2 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-warning" style="color: #f59e0b; font-size: 24px;"></span>
            <?php _e('Recommandations', 'ai-content-studio'); ?>
        </h2>

        <?php foreach ($recommendations as $rec): ?>
        <div style="margin: 15px 0; display: flex; gap: 15px; align-items: flex-start;">
            <span class="acs-badge" style="
                background: <?php echo $rec['severity'] === 'critical' ? '#ef4444' : ($rec['severity'] === 'warning' ? '#f59e0b' : '#3b82f6'); ?>;
                color: white;
                padding: 4px 12px;
                border-radius: 4px;
                font-size: 11px;
                text-transform: uppercase;
                font-weight: 600;
            ">
                <?php echo esc_html(strtoupper($rec['severity'])); ?>
            </span>
            <span style="flex: 1; line-height: 1.6;"><?php echo esc_html($rec['message']); ?></span>

            <?php if ($rec['action'] === 'run_migration'): ?>
            <button id="acs-run-migration" class="button button-primary">
                <span class="dashicons dashicons-database-import"></span>
                <?php _e('Migrer la Base de Données', 'ai-content-studio'); ?>
            </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="notice notice-success" style="margin-top: 20px; padding: 20px;">
        <h2 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-yes-alt" style="color: #10b981; font-size: 24px;"></span>
            <?php _e('Système Opérationnel', 'ai-content-studio'); ?>
        </h2>
        <p><?php _e('Aucun problème détecté. Tous les systèmes fonctionnent correctement.', 'ai-content-studio'); ?></p>
    </div>
    <?php endif; ?>

    <!-- System Status -->
    <div style="background: white; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px; border-radius: 8px;">
        <h2><?php _e('État du Système', 'ai-content-studio'); ?></h2>

        <table class="wp-list-table widefat fixed" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th style="width: 40%;"><?php _e('Composant', 'ai-content-studio'); ?></th>
                    <th><?php _e('État', 'ai-content-studio'); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- System Logs Table -->
                <tr>
                    <td><strong><?php _e('Table system_logs', 'ai-content-studio'); ?></strong></td>
                    <td>
                        <?php if ($diagnostic['system_logs_table']['exists']): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                            <strong style="color: #10b981;"><?php _e('Existe', 'ai-content-studio'); ?></strong>
                            <span style="color: #6b7280; margin-left: 10px;">
                                (<?php echo esc_html($diagnostic['system_logs_table']['count'] ?? 0); ?> logs)
                            </span>
                        <?php else: ?>
                            <span class="dashicons dashicons-dismiss" style="color: #ef4444;"></span>
                            <strong style="color: #ef4444;"><?php _e('Manquante', 'ai-content-studio'); ?></strong>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- API Configuration -->
                <tr>
                    <td><strong><?php _e('Clé API Claude', 'ai-content-studio'); ?></strong></td>
                    <td>
                        <?php if ($diagnostic['api_configuration']['claude_api_key_configured']): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                            <strong style="color: #10b981;"><?php _e('Configurée', 'ai-content-studio'); ?></strong>
                            <code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; margin-left: 10px;">
                                <?php echo esc_html($diagnostic['api_configuration']['claude_api_key_preview']); ?>
                            </code>
                        <?php else: ?>
                            <span class="dashicons dashicons-dismiss" style="color: #ef4444;"></span>
                            <strong style="color: #ef4444;"><?php _e('Non configurée', 'ai-content-studio'); ?></strong>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <td><strong><?php _e('Modèle Claude', 'ai-content-studio'); ?></strong></td>
                    <td>
                        <code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px;">
                            <?php echo esc_html($diagnostic['api_configuration']['claude_model']); ?>
                        </code>
                    </td>
                </tr>

                <!-- User Profile -->
                <tr>
                    <td><strong><?php _e('Profil Utilisateur Actuel', 'ai-content-studio'); ?></strong></td>
                    <td>
                        <?php if ($diagnostic['user_profile']['has_profile']): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                            <strong style="color: #10b981;"><?php _e('Configuré', 'ai-content-studio'); ?></strong>
                            <span style="color: #6b7280; margin-left: 10px;">
                                (User ID: <?php echo esc_html($diagnostic['user_profile']['user_id']); ?>)
                            </span>
                        <?php else: ?>
                            <span class="dashicons dashicons-dismiss" style="color: #f59e0b;"></span>
                            <strong style="color: #f59e0b;"><?php _e('Manquant', 'ai-content-studio'); ?></strong>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Recent Logs (if table exists) -->
    <?php if ($diagnostic['system_logs_table']['exists'] && !empty($diagnostic['system_logs_table']['recent'])): ?>
    <div style="background: white; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px; border-radius: 8px;">
        <h2><?php _e('Logs Récents', 'ai-content-studio'); ?></h2>
        <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th style="width: 150px;"><?php _e('Date/Heure', 'ai-content-studio'); ?></th>
                    <th style="width: 80px;"><?php _e('Niveau', 'ai-content-studio'); ?></th>
                    <th style="width: 100px;"><?php _e('Catégorie', 'ai-content-studio'); ?></th>
                    <th><?php _e('Message', 'ai-content-studio'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $level_colors = [
                    'debug' => '#6b7280',
                    'info' => '#3b82f6',
                    'warning' => '#f59e0b',
                    'error' => '#ef4444',
                    'critical' => '#dc2626',
                ];
                foreach ($diagnostic['system_logs_table']['recent'] as $log):
                ?>
                <tr>
                    <td><?php echo esc_html($log['created_at']); ?></td>
                    <td>
                        <span style="
                            background: <?php echo esc_attr($level_colors[$log['level']] ?? '#6b7280'); ?>;
                            color: white;
                            padding: 4px 8px;
                            border-radius: 4px;
                            font-size: 11px;
                            text-transform: uppercase;
                            font-weight: 600;
                        ">
                            <?php echo esc_html($log['level']); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($log['category']); ?></td>
                    <td><?php echo esc_html($log['message']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p style="margin-top: 15px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=ai-content-studio-logs')); ?>" class="button">
                <?php _e('Voir tous les logs', 'ai-content-studio'); ?> →
            </a>
        </p>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="notice notice-error">
        <p><?php _e('Impossible de récupérer les données de diagnostic.', 'ai-content-studio'); ?></p>
    </div>
    <?php endif; ?>

    <!-- Debug Info -->
    <div style="background: #f3f4f6; padding: 20px; margin-top: 20px; border-radius: 8px;">
        <h3 style="margin-top: 0;"><?php _e('Informations de Débogage', 'ai-content-studio'); ?></h3>
        <table class="widefat">
            <tr>
                <td style="width: 30%;"><strong>Version du Plugin:</strong></td>
                <td><?php echo esc_html(ACS_VERSION); ?></td>
            </tr>
            <tr>
                <td><strong>Version WordPress:</strong></td>
                <td><?php echo esc_html(get_bloginfo('version')); ?></td>
            </tr>
            <tr>
                <td><strong>Version PHP:</strong></td>
                <td><?php echo esc_html(PHP_VERSION); ?></td>
            </tr>
            <tr>
                <td><strong>API REST URL:</strong></td>
                <td><code><?php echo esc_html(rest_url('acs/v1')); ?></code></td>
            </tr>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#acs-run-migration').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();

        if (!confirm('<?php _e('Exécuter la migration de la base de données ? Cela va créer la table system_logs.', 'ai-content-studio'); ?>')) {
            return;
        }

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: rotation 1s linear infinite;"></span> <?php _e('Migration en cours...', 'ai-content-studio'); ?>');

        $.ajax({
            url: '<?php echo esc_url(rest_url('acs/v1/diagnostic/migrate')); ?>',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Migration réussie ! La page va se recharger.', 'ai-content-studio'); ?>');
                    location.reload();
                } else {
                    alert('<?php _e('Erreur lors de la migration: ', 'ai-content-studio'); ?>' + (response.message || 'Unknown error'));
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert('<?php _e('Erreur de connexion lors de la migration.', 'ai-content-studio'); ?>');
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>

<style>
@keyframes rotation {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
