<?php
/**
 * Calendar API Endpoint
 * Gestion du calendrier éditorial
 *
 * @package ACS
 */

namespace ACS\API;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calendar Endpoint class
 */
class Calendar_Endpoint extends WP_REST_Controller {

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
    protected $rest_base = 'calendar';

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes() {
        // Get posts for a specific month
        register_rest_route($this->namespace, '/' . $this->rest_base . '/posts', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_calendar_posts'],
                'permission_callback' => [$this, 'check_user_permission'],
            ],
        ]);

        // Create/schedule a post
        register_rest_route($this->namespace, '/' . $this->rest_base . '/posts', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'schedule_post'],
                'permission_callback' => [$this, 'check_user_permission'],
            ],
        ]);

        // Update a scheduled post
        register_rest_route($this->namespace, '/' . $this->rest_base . '/posts/(?P<id>[\d]+)', [
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update_post'],
                'permission_callback' => [$this, 'check_user_permission'],
            ],
        ]);

        // Delete a scheduled post
        register_rest_route($this->namespace, '/' . $this->rest_base . '/posts/(?P<id>[\d]+)', [
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'delete_post'],
                'permission_callback' => [$this, 'check_user_permission'],
            ],
        ]);

        // Get calendar events
        register_rest_route($this->namespace, '/' . $this->rest_base . '/events', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_events'],
                'permission_callback' => [$this, 'check_user_permission'],
            ],
        ]);
    }

    /**
     * Check if user has permission
     *
     * @param WP_REST_Request $request Request object.
     * @return bool
     */
    public function check_user_permission($request) {
        return is_user_logged_in();
    }

    /**
     * Get calendar posts for a specific month
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_calendar_posts($request) {
        $user_id = get_current_user_id();
        $month = isset($request['month']) ? intval($request['month']) : date('n');
        $year = isset($request['year']) ? intval($request['year']) : date('Y');

        global $wpdb;
        $table = $wpdb->prefix . 'acs_social_posts';

        // Get first and last day of month
        $first_day = sprintf('%04d-%02d-01', $year, $month);
        $last_day = date('Y-m-t', strtotime($first_day));

        $query = $wpdb->prepare(
            "SELECT id, platform, content, hashtags, status, scheduled_for, published_at, template_id, metadata
             FROM {$table}
             WHERE user_id = %d
             AND scheduled_for >= %s
             AND scheduled_for <= %s
             ORDER BY scheduled_for ASC",
            $user_id,
            $first_day . ' 00:00:00',
            $last_day . ' 23:59:59'
        );

        $posts = $wpdb->get_results($query, ARRAY_A);

        return rest_ensure_response([
            'success' => true,
            'data' => $posts,
        ]);
    }

    /**
     * Schedule a post
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function schedule_post($request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();

        if (empty($params['content']) || empty($params['platform']) || empty($params['scheduled_for'])) {
            return new WP_Error(
                'missing_fields',
                __('Champs requis manquants', 'ai-content-studio'),
                ['status' => 400]
            );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'acs_social_posts';

        $data = [
            'user_id' => $user_id,
            'platform' => sanitize_text_field($params['platform']),
            'content' => wp_kses_post($params['content']),
            'hashtags' => isset($params['hashtags']) ? wp_json_encode($params['hashtags']) : null,
            'language' => isset($params['language']) ? sanitize_text_field($params['language']) : 'fr',
            'status' => 'scheduled',
            'scheduled_for' => sanitize_text_field($params['scheduled_for']),
            'template_id' => isset($params['template_id']) ? intval($params['template_id']) : null,
            'metadata' => isset($params['metadata']) ? wp_json_encode($params['metadata']) : null,
        ];

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            return new WP_Error(
                'db_error',
                __('Erreur lors de la création du post', 'ai-content-studio'),
                ['status' => 500]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'data' => [
                'id' => $wpdb->insert_id,
                'message' => __('Post planifié avec succès', 'ai-content-studio'),
            ],
        ]);
    }

    /**
     * Update a post
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function update_post($request) {
        $user_id = get_current_user_id();
        $post_id = $request['id'];
        $params = $request->get_json_params();

        global $wpdb;
        $table = $wpdb->prefix . 'acs_social_posts';

        // Verify ownership
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE id = %d AND user_id = %d",
            $post_id,
            $user_id
        ));

        if (!$existing) {
            return new WP_Error('not_found', 'Post not found', ['status' => 404]);
        }

        $data = [];

        if (isset($params['content'])) {
            $data['content'] = wp_kses_post($params['content']);
        }
        if (isset($params['scheduled_for'])) {
            $data['scheduled_for'] = sanitize_text_field($params['scheduled_for']);
        }
        if (isset($params['status'])) {
            $data['status'] = sanitize_text_field($params['status']);
        }
        if (isset($params['hashtags'])) {
            $data['hashtags'] = wp_json_encode($params['hashtags']);
        }

        if (empty($data)) {
            return new WP_Error('no_data', 'No data to update', ['status' => 400]);
        }

        $wpdb->update(
            $table,
            $data,
            ['id' => $post_id, 'user_id' => $user_id]
        );

        return rest_ensure_response([
            'success' => true,
            'message' => __('Post mis à jour', 'ai-content-studio'),
        ]);
    }

    /**
     * Delete a post
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function delete_post($request) {
        $user_id = get_current_user_id();
        $post_id = $request['id'];

        global $wpdb;
        $table = $wpdb->prefix . 'acs_social_posts';

        $result = $wpdb->delete(
            $table,
            ['id' => $post_id, 'user_id' => $user_id]
        );

        if ($result === false || $result === 0) {
            return new WP_Error('delete_failed', 'Failed to delete post', ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Post supprimé', 'ai-content-studio'),
        ]);
    }

    /**
     * Get calendar events
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_events($request) {
        $user_id = get_current_user_id();

        global $wpdb;
        $table = $wpdb->prefix . 'acs_calendar_events';

        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY event_date ASC",
            $user_id
        ), ARRAY_A);

        return rest_ensure_response([
            'success' => true,
            'data' => $events,
        ]);
    }
}
