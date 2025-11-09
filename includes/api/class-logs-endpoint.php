<?php
/**
 * Logs API Endpoint
 * Endpoint pour récupérer et gérer les logs système
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
 * Logs Endpoint class
 */
class Logs_Endpoint extends WP_REST_Controller {

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
    protected $rest_base = 'logs';

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes() {
        // Get logs
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_logs'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);

        // Get log statistics
        register_rest_route($this->namespace, '/' . $this->rest_base . '/stats', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_stats'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);

        // Clear old logs
        register_rest_route($this->namespace, '/' . $this->rest_base . '/clear', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'clear_logs'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);

        // Test logging (for debugging)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/test', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'test_logging'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);
    }

    /**
     * Check if user has admin permission
     *
     * @param WP_REST_Request $request Request object.
     * @return bool
     */
    public function check_admin_permission($request) {
        return current_user_can('manage_options');
    }

    /**
     * Get logs with filters
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_logs($request) {
        $limit = $request->get_param('limit') ? intval($request->get_param('limit')) : 100;
        $level = $request->get_param('level');
        $category = $request->get_param('category');
        $user_id = $request->get_param('user_id');

        // Limit maximum results
        if ($limit > 1000) {
            $limit = 1000;
        }

        $logs = Logger::get_recent_logs($limit, $level, $category, $user_id);

        return rest_ensure_response([
            'success' => true,
            'data' => $logs,
            'count' => count($logs),
        ]);
    }

    /**
     * Get log statistics
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_stats($request) {
        $stats = Logger::get_stats();

        return rest_ensure_response([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Clear old logs
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function clear_logs($request) {
        $days = $request->get_param('days') ? intval($request->get_param('days')) : 30;

        $deleted = Logger::clear_old_logs($days);

        return rest_ensure_response([
            'success' => true,
            'message' => sprintf(__('%d logs supprimés', 'ai-content-studio'), $deleted),
            'deleted' => $deleted,
        ]);
    }

    /**
     * Test logging system (for debugging)
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function test_logging($request) {
        Logger::debug('Test debug log', ['test' => 'data'], 'test');
        Logger::info('Test info log', ['test' => 'data'], 'test');
        Logger::warning('Test warning log', ['test' => 'data'], 'test');
        Logger::error('Test error log', ['test' => 'data'], 'test');

        return rest_ensure_response([
            'success' => true,
            'message' => __('Logs de test créés avec succès', 'ai-content-studio'),
        ]);
    }
}
