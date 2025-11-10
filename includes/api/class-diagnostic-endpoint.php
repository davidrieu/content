<?php
/**
 * Diagnostic API Endpoint
 * Endpoint pour diagnostiquer les problèmes du plugin
 *
 * @package ACS\API
 */

namespace ACS\API;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use ACS\Utils\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Diagnostic Endpoint class
 */
class Diagnostic_Endpoint extends WP_REST_Controller {

    /**
     * Namespace
     *
     * @var string
     */
    protected $namespace = 'acs/v1';

    /**
     * Rest base
     *
     * @var string
     */
    protected $rest_base = 'diagnostic';

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes() {
        // Get diagnostic info
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_diagnostic'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        // Run migration manually
        register_rest_route($this->namespace, '/' . $this->rest_base . '/migrate', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'run_migration'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);
    }

    /**
     * Check if user is logged in
     *
     * @param WP_REST_Request $request Request object.
     * @return bool
     */
    public function check_permission($request) {
        return is_user_logged_in();
    }

    /**
     * Get diagnostic information
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_diagnostic($request) {
        global $wpdb;

        $diagnostic = [];

        // 1. Check system_logs table
        $table = $wpdb->prefix . 'acs_system_logs';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        $diagnostic['system_logs_table'] = [
            'exists' => $table_exists,
            'name' => $table,
        ];

        if ($table_exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            $diagnostic['system_logs_table']['count'] = (int) $count;

            // Get recent logs
            $recent = $wpdb->get_results(
                "SELECT id, user_id, level, category, message, created_at FROM $table ORDER BY created_at DESC LIMIT 5",
                ARRAY_A
            );
            $diagnostic['system_logs_table']['recent'] = $recent;
        } else {
            $diagnostic['system_logs_table']['count'] = 0;
            $diagnostic['system_logs_table']['recent'] = [];
        }

        // 2. Check API keys
        $api_key = get_option('acs_claude_api_key', '');
        $diagnostic['api_configuration'] = [
            'claude_api_key_configured' => !empty($api_key),
            'claude_api_key_preview' => !empty($api_key) ? substr($api_key, 0, 10) . '...' : 'NOT SET',
            'claude_model' => get_option('acs_claude_model', 'claude-sonnet-4-20250514'),
        ];

        // 3. Check versions
        $diagnostic['versions'] = [
            'plugin_version' => defined('ACS_VERSION') ? ACS_VERSION : 'unknown',
            'stored_version' => get_option('acs_version', 'not set'),
            'db_version' => get_option('acs_db_version', 'not set'),
        ];

        // 4. Check user profile
        $user_id = get_current_user_id();
        $profile_table = $wpdb->prefix . 'acs_business_profiles';
        $has_profile = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $profile_table WHERE user_id = %d",
            $user_id
        ));
        $diagnostic['user_profile'] = [
            'user_id' => $user_id,
            'has_profile' => (bool) $has_profile,
        ];

        // 5. Check if Logger class works
        $diagnostic['logger'] = [
            'class_exists' => class_exists('ACS\\Utils\\Logger'),
        ];

        // 6. Database connection
        $diagnostic['database'] = [
            'connected' => (bool) $wpdb->dbh,
            'prefix' => $wpdb->prefix,
        ];

        return rest_ensure_response([
            'success' => true,
            'data' => $diagnostic,
            'recommendations' => $this->get_recommendations($diagnostic),
        ]);
    }

    /**
     * Run database migration
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function run_migration($request) {
        if (!class_exists('ACS\\Database')) {
            return rest_ensure_response([
                'success' => false,
                'message' => 'Database class not found',
            ]);
        }

        try {
            // Create system_logs table
            \ACS\Database::create_system_logs_table();

            // Verify it was created
            global $wpdb;
            $table = $wpdb->prefix . 'acs_system_logs';
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;

            if ($exists) {
                // Test logging
                Logger::info('Migration completed successfully', [
                    'user_id' => get_current_user_id(),
                    'triggered_by' => 'manual_migration',
                ], 'database');

                return rest_ensure_response([
                    'success' => true,
                    'message' => 'Migration completed successfully. System logs table created.',
                ]);
            } else {
                return rest_ensure_response([
                    'success' => false,
                    'message' => 'Table creation failed',
                ]);
            }
        } catch (\Exception $e) {
            return rest_ensure_response([
                'success' => false,
                'message' => 'Migration error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get recommendations based on diagnostic
     *
     * @param array $diagnostic Diagnostic data
     * @return array
     */
    private function get_recommendations($diagnostic) {
        $recommendations = [];

        // Check if table exists
        if (!$diagnostic['system_logs_table']['exists']) {
            $recommendations[] = [
                'severity' => 'critical',
                'message' => 'System logs table does not exist. Run migration.',
                'action' => 'run_migration',
            ];
        }

        // Check API key
        if (!$diagnostic['api_configuration']['claude_api_key_configured']) {
            $recommendations[] = [
                'severity' => 'critical',
                'message' => 'Claude API key is not configured. Add it in Settings.',
                'action' => 'configure_api_key',
            ];
        }

        // Check profile
        if (!$diagnostic['user_profile']['has_profile']) {
            $recommendations[] = [
                'severity' => 'warning',
                'message' => 'User profile not found. Complete onboarding.',
                'action' => 'complete_onboarding',
            ];
        }

        // Check if logs are being written
        if ($diagnostic['system_logs_table']['exists'] && $diagnostic['system_logs_table']['count'] === 0) {
            $recommendations[] = [
                'severity' => 'info',
                'message' => 'No logs have been written yet. This is normal for new installations.',
                'action' => null,
            ];
        }

        return $recommendations;
    }
}
