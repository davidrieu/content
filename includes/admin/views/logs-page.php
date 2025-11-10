<?php
/**
 * Logs System Page - WordPress Admin
 *
 * @package ACS\Admin\Views
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load Logger class if not already loaded
if (!class_exists('ACS\\Utils\\Logger')) {
    require_once ACS_PLUGIN_DIR . 'includes/utils/class-logger.php';
}

// Get logs directly
$logs = \ACS\Utils\Logger::get_recent_logs(100);

// Calculate stats
$stats = [
    'total' => count($logs),
    'by_level' => [],
    'by_category' => [],
];

foreach ($logs as $log) {
    // Count by level
    if (!isset($stats['by_level'][$log['level']])) {
        $stats['by_level'][$log['level']] = 0;
    }
    $stats['by_level'][$log['level']]++;

    // Count by category
    if (!isset($stats['by_category'][$log['category']])) {
        $stats['by_category'][$log['category']] = 0;
    }
    $stats['by_category'][$log['category']]++;
}

// Level badges colors
$level_colors = [
    'debug' => '#6b7280',
    'info' => '#3b82f6',
    'warning' => '#f59e0b',
    'error' => '#ef4444',
    'critical' => '#dc2626',
];
?>

<div class="wrap">
    <h1><?php echo esc_html__('Logs Système', 'ai-content-studio'); ?></h1>

    <div class="acs-logs-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <!-- Total -->
        <div class="acs-stat-card" style="background: white; padding: 20px; border-left: 4px solid #3b82f6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; margin-bottom: 8px;">Total Logs</div>
            <div style="font-size: 32px; font-weight: bold; color: #111827;"><?php echo esc_html($stats['total']); ?></div>
        </div>

        <!-- By Level -->
        <?php foreach ($stats['by_level'] as $level => $count): ?>
        <div class="acs-stat-card" style="background: white; padding: 20px; border-left: 4px solid <?php echo esc_attr($level_colors[$level] ?? '#6b7280'); ?>; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; margin-bottom: 8px;"><?php echo esc_html(ucfirst($level)); ?></div>
            <div style="font-size: 32px; font-weight: bold; color: #111827;"><?php echo esc_html($count); ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="acs-logs-actions" style="margin: 20px 0; display: flex; gap: 10px; align-items: center;">
        <button id="acs-refresh-logs" class="button button-primary">
            <span class="dashicons dashicons-update"></span> <?php _e('Actualiser', 'ai-content-studio'); ?>
        </button>

        <select id="acs-filter-level" class="button" style="height: 30px;">
            <option value=""><?php _e('Tous les niveaux', 'ai-content-studio'); ?></option>
            <option value="debug">Debug</option>
            <option value="info">Info</option>
            <option value="warning">Warning</option>
            <option value="error">Error</option>
            <option value="critical">Critical</option>
        </select>

        <select id="acs-filter-category" class="button" style="height: 30px;">
            <option value=""><?php _e('Toutes les catégories', 'ai-content-studio'); ?></option>
            <option value="generation">Génération</option>
            <option value="api">API</option>
            <option value="database">Base de données</option>
            <option value="auth">Authentification</option>
        </select>

        <button id="acs-clear-logs" class="button button-link-delete" style="margin-left: auto;">
            <span class="dashicons dashicons-trash"></span> <?php _e('Vider les logs', 'ai-content-studio'); ?>
        </button>
    </div>

    <div class="acs-logs-table" style="background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 140px;"><?php _e('Date/Heure', 'ai-content-studio'); ?></th>
                    <th style="width: 80px;"><?php _e('Niveau', 'ai-content-studio'); ?></th>
                    <th style="width: 100px;"><?php _e('Catégorie', 'ai-content-studio'); ?></th>
                    <th><?php _e('Message', 'ai-content-studio'); ?></th>
                    <th style="width: 80px;"><?php _e('User ID', 'ai-content-studio'); ?></th>
                    <th style="width: 150px;"><?php _e('Fichier', 'ai-content-studio'); ?></th>
                </tr>
            </thead>
            <tbody id="acs-logs-tbody">
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">
                        <?php _e('Aucun log trouvé', 'ai-content-studio'); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html($log['created_at'] ?? '-'); ?></td>
                        <td>
                            <span class="acs-badge" style="background: <?php echo esc_attr($level_colors[$log['level'] ?? 'info'] ?? '#6b7280'); ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">
                                <?php echo esc_html($log['level'] ?? 'info'); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($log['category'] ?? 'general'); ?></td>
                        <td>
                            <?php echo esc_html($log['message'] ?? ''); ?>
                            <?php if (!empty($log['context']) && is_array($log['context'])): ?>
                                <button class="button button-small acs-view-context" data-context="<?php echo esc_attr(wp_json_encode($log['context'])); ?>" style="margin-left: 8px;">
                                    <?php _e('Contexte', 'ai-content-studio'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($log['user_id'] ?? 'System'); ?></td>
                        <td><?php echo esc_html(!empty($log['file']) ? $log['file'] . ':' . ($log['line'] ?? '') : '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Context Modal -->
<div id="acs-context-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 8px; max-width: 800px; max-height: 80vh; overflow: auto; position: relative;">
        <button id="acs-close-modal" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        <h2><?php _e('Contexte du Log', 'ai-content-studio'); ?></h2>
        <pre id="acs-context-content" style="background: #f3f4f6; padding: 20px; border-radius: 4px; overflow: auto; max-height: 400px;"></pre>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Refresh logs
    $('#acs-refresh-logs').on('click', function() {
        location.reload();
    });

    // Filter logs
    $('#acs-filter-level, #acs-filter-category').on('change', function() {
        var level = $('#acs-filter-level').val();
        var category = $('#acs-filter-category').val();

        var url = '<?php echo esc_url(rest_url('acs/v1/logs')); ?>?';
        if (level) url += 'level=' + level + '&';
        if (category) url += 'category=' + category;

        $.ajax({
            url: url,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateLogsTable(response.data);
                }
            }
        });
    });

    // Clear logs
    $('#acs-clear-logs').on('click', function() {
        if (!confirm('<?php _e('Êtes-vous sûr de vouloir supprimer tous les logs ?', 'ai-content-studio'); ?>')) {
            return;
        }

        $.ajax({
            url: '<?php echo esc_url(rest_url('acs/v1/logs')); ?>',
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function() {
                location.reload();
            }
        });
    });

    // View context
    $(document).on('click', '.acs-view-context', function() {
        var context = $(this).data('context');
        $('#acs-context-content').text(JSON.stringify(context, null, 2));
        $('#acs-context-modal').css('display', 'flex');
    });

    $('#acs-close-modal, #acs-context-modal').on('click', function(e) {
        if (e.target === this) {
            $('#acs-context-modal').hide();
        }
    });

    function updateLogsTable(logs) {
        var tbody = $('#acs-logs-tbody');
        tbody.empty();

        if (logs.length === 0) {
            tbody.append('<tr><td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">Aucun log trouvé</td></tr>');
            return;
        }

        logs.forEach(function(log) {
            var levelColors = <?php echo wp_json_encode($level_colors); ?>;
            var contextBtn = log.context ? '<button class="button button-small acs-view-context" data-context=\'' + log.context + '\'>Contexte</button>' : '';

            tbody.append(
                '<tr>' +
                '<td>' + log.created_at + '</td>' +
                '<td><span class="acs-badge" style="background: ' + (levelColors[log.level] || '#6b7280') + '; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">' + log.level + '</span></td>' +
                '<td>' + log.category + '</td>' +
                '<td>' + log.message + ' ' + contextBtn + '</td>' +
                '<td>' + (log.user_id || 'System') + '</td>' +
                '<td>' + (log.file ? log.file + ':' + log.line : '-') + '</td>' +
                '</tr>'
            );
        });
    }
});
</script>

<style>
.acs-badge {
    display: inline-block;
    font-weight: 600;
}
</style>
