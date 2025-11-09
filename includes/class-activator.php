<?php
/**
 * Fired during plugin activation and deactivation
 *
 * @package ACS
 */

namespace ACS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activator class
 */
class Activator {

    /**
     * Activate the plugin
     *
     * @return void
     */
    public static function activate() {
        // Create database tables
        self::create_tables();

        // Set default options
        self::set_default_options();

        // Create default templates
        self::create_default_templates();

        // Schedule cron jobs
        self::schedule_cron_jobs();

        // Set activation flag
        update_option('acs_activated', true);
        update_option('acs_version', ACS_VERSION);
        update_option('acs_activation_date', current_time('mysql'));

        // Flush rewrite rules
        flush_rewrite_rules();

        // Log activation
        if (class_exists('ACS\\Utils\\Logger')) {
            Utils\Logger::log('Plugin activated successfully', 'info');
        }
    }

    /**
     * Deactivate the plugin
     *
     * @return void
     */
    public static function deactivate() {
        // Clear scheduled cron jobs
        self::clear_cron_jobs();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Log deactivation
        if (class_exists('ACS\\Utils\\Logger')) {
            Utils\Logger::log('Plugin deactivated', 'info');
        }
    }

    /**
     * Uninstall the plugin
     *
     * This will delete all data including tables and options
     *
     * @return void
     */
    public static function uninstall() {
        global $wpdb;

        // Only proceed if user confirmed uninstall
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }

        // Check if we should preserve data
        $preserve_data = get_option('acs_preserve_data_on_uninstall', false);

        if (!$preserve_data) {
            // Delete all custom tables
            $table_prefix = $wpdb->prefix . ACS_TABLE_PREFIX;

            $tables = [
                'business_profiles',
                'strategies',
                'usage_stats',
                'social_posts',
                'blog_articles',
                'generated_images',
                'trends',
                'templates',
                'brand_kits',
                'social_connections',
                'calendar_events',
                'analytics',
                'notifications',
                'favorites',
            ];

            foreach ($tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS {$table_prefix}{$table}");
            }

            // Delete all options
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'acs_%'");

            // Delete all user meta
            $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'acs_%'");

            // Delete all transients
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_acs_%' OR option_name LIKE '_transient_timeout_acs_%'");
        }

        // Clear cron jobs
        self::clear_cron_jobs();

        // Log uninstall
        error_log('[ACS] Plugin uninstalled');
    }

    /**
     * Create database tables
     *
     * @return void
     */
    private static function create_tables() {
        if (!class_exists('ACS\\Database')) {
            require_once ACS_PLUGIN_DIR . 'includes/class-database.php';
        }

        Database::create_tables();
    }

    /**
     * Set default options
     *
     * @return void
     */
    private static function set_default_options() {
        $default_options = [
            'acs_claude_model' => 'claude-sonnet-4-20250514',
            'acs_dalle_model' => 'dall-e-3',
            'acs_default_language' => 'fr',
            'acs_cache_duration' => 3600, // 1 hour
            'acs_max_retries' => 3,
            'acs_preserve_data_on_uninstall' => false,
            'acs_enable_debug' => false,
            'acs_trends_refresh_interval' => 14400, // 4 hours
            'acs_default_tone' => 'professional',
            'acs_enable_analytics' => true,
        ];

        foreach ($default_options as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Create default templates
     *
     * This will be populated later with actual template data
     *
     * @return void
     */
    private static function create_default_templates() {
        global $wpdb;
        $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'templates';

        // Check if templates already exist
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

        if ($count > 0) {
            return; // Templates already created
        }

        // Default templates will be created by the Templates_Library class
        // This is just a placeholder for now
    }

    /**
     * Schedule cron jobs
     *
     * @return void
     */
    private static function schedule_cron_jobs() {
        // Reset usage monthly (1st of each month at 00:00)
        if (!wp_next_scheduled('acs_reset_monthly_usage')) {
            wp_schedule_event(
                strtotime('first day of next month midnight'),
                'monthly',
                'acs_reset_monthly_usage'
            );
        }

        // Fetch trends (every 4 hours)
        if (!wp_next_scheduled('acs_fetch_trends')) {
            wp_schedule_event(time(), 'fourhourly', 'acs_fetch_trends');
        }

        // Cleanup expired trends (daily)
        if (!wp_next_scheduled('acs_cleanup_trends')) {
            wp_schedule_event(time(), 'daily', 'acs_cleanup_trends');
        }

        // Refresh OAuth tokens (daily)
        if (!wp_next_scheduled('acs_refresh_tokens')) {
            wp_schedule_event(time(), 'daily', 'acs_refresh_tokens');
        }

        // Publish scheduled posts (every 15 minutes)
        if (!wp_next_scheduled('acs_publish_scheduled_posts')) {
            wp_schedule_event(time(), 'fifteen_minutes', 'acs_publish_scheduled_posts');
        }
    }

    /**
     * Clear cron jobs
     *
     * @return void
     */
    private static function clear_cron_jobs() {
        $cron_hooks = [
            'acs_reset_monthly_usage',
            'acs_fetch_trends',
            'acs_cleanup_trends',
            'acs_refresh_tokens',
            'acs_publish_scheduled_posts',
        ];

        foreach ($cron_hooks as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        }
    }

    /**
     * Check if this is a new installation
     *
     * @return bool
     */
    public static function is_new_installation() {
        return !get_option('acs_activated');
    }

    /**
     * Check if an update is needed
     *
     * @return bool
     */
    public static function needs_update() {
        $installed_version = get_option('acs_version', '0.0.0');
        return version_compare($installed_version, ACS_VERSION, '<');
    }

    /**
     * Run update routine
     *
     * @return void
     */
    public static function update() {
        $installed_version = get_option('acs_version', '0.0.0');

        // Run version-specific updates
        if (version_compare($installed_version, '1.0.0', '<')) {
            // Update to 1.0.0
            self::update_to_1_0_0();
        }

        if (version_compare($installed_version, '1.1.0', '<')) {
            // Update to 1.1.0 - Add new onboarding fields
            self::update_to_1_1_0();
        }

        if (version_compare($installed_version, '1.2.0', '<')) {
            // Update to 1.2.0 - Add system logs table
            self::update_to_1_2_0();
        }

        // Update version number
        update_option('acs_version', ACS_VERSION);

        if (class_exists('ACS\\Utils\\Logger')) {
            Utils\Logger::log('Plugin updated to version ' . ACS_VERSION, 'info');
        }
    }

    /**
     * Update to version 1.0.0
     *
     * @return void
     */
    private static function update_to_1_0_0() {
        // Initial release - nothing to update
    }

    /**
     * Update to version 1.1.0
     * Add new onboarding fields to business_profiles table
     *
     * @return void
     */
    private static function update_to_1_1_0() {
        if (!class_exists('ACS\\Database')) {
            require_once ACS_PLUGIN_DIR . 'includes/class-database.php';
        }

        // Upgrade database tables with new columns
        Database::upgrade_tables();

        if (class_exists('ACS\\Utils\\Logger')) {
            Utils\Logger::info('Database upgraded to version 1.1.0 - Added new onboarding fields');
        }
    }

    /**
     * Update to version 1.2.0
     * Add system_logs table for debugging and monitoring
     *
     * @return void
     */
    private static function update_to_1_2_0() {
        if (!class_exists('ACS\\Database')) {
            require_once ACS_PLUGIN_DIR . 'includes/class-database.php';
        }

        // Create system_logs table
        Database::create_system_logs_table();

        if (class_exists('ACS\\Utils\\Logger')) {
            Utils\Logger::info('Database upgraded to version 1.2.0 - Added system_logs table', [], 'database');
        }
    }
}
