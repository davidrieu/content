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
