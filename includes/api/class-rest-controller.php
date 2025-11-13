<?php
/**
 * Base REST Controller
 *
 * @package ACS\API
 */

namespace ACS\API;

if (!defined('ABSPATH')) {
    exit;
}

abstract class REST_Controller extends \WP_REST_Controller {

    protected $namespace = 'acs/v1';

    /**
     * Check permissions
     */
    public function permission_check($request) {
        return is_user_logged_in();
    }

    /**
     * Get current user ID
     */
    protected function get_current_user_id() {
        return get_current_user_id();
    }

    /**
     * Get active project ID for current user
     *
     * @return int|null Project ID or null if no active project
     */
    protected function get_active_project_id() {
        global $wpdb;
        $user_id = $this->get_current_user_id();

        if (!$user_id) {
            return null;
        }

        $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'projects';
        $project_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND is_active = 1 LIMIT 1",
            $user_id
        ));

        return $project_id ? (int) $project_id : null;
    }

    /**
     * Success response
     */
    protected function success($data, $message = '', $status = 200) {
        return new \WP_REST_Response([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    /**
     * Error response
     */
    protected function error($message, $code = 'error', $status = 400) {
        return new \WP_REST_Response([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
