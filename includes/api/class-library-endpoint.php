<?php
/**
 * Library API Endpoint
 * Gestion de la bibliothèque de contenu (posts sauvegardés, templates)
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
 * Library Endpoint class
 */
class Library_Endpoint extends WP_REST_Controller {

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
    protected $rest_base = 'library';

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes() {
        // Get saved posts
        register_rest_route($this->namespace, '/' . $this->rest_base . '/posts', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_saved_posts'],
                'permission_callback' => [$this, 'check_user_permission'],
            ],
        ]);

        // Save a post to library
        register_rest_route($this->namespace, '/' . $this->rest_base . '/save', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'save_post'],
                'permission_callback' => [$this, 'check_user_permission'],
            ],
        ]);

        // Get user templates
        register_rest_route($this->namespace, '/' . $this->rest_base . '/templates', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_templates'],
                'permission_callback' => [$this, 'check_user_permission'],
            ],
        ]);

        // Delete item from library
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<type>post|template)/(?P<id>[\d]+)', [
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'delete_item'],
                'permission_callback' => [$this, 'check_user_permission'],
            ],
        ]);

        // Toggle favorite
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<type>post|template)/(?P<id>[\d]+)/favorite', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'toggle_favorite'],
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
     * Get saved posts from library
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_saved_posts($request) {
        $user_id = get_current_user_id();

        global $wpdb;
        $table = $wpdb->prefix . 'acs_saved_templates';

        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE user_id = %d AND category = 'post'
             ORDER BY created_at DESC",
            $user_id
        ), ARRAY_A);

        return rest_ensure_response([
            'success' => true,
            'data' => $posts,
        ]);
    }

    /**
     * Save a post to library
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function save_post($request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();

        if (empty($params['content'])) {
            return new WP_Error(
                'missing_content',
                __('Le contenu est requis', 'ai-content-studio'),
                ['status' => 400]
            );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'acs_saved_templates';

        $data = [
            'user_id' => $user_id,
            'name' => isset($params['name']) ? sanitize_text_field($params['name']) : 'Post ' . date('d/m/Y H:i'),
            'category' => isset($params['category']) ? sanitize_text_field($params['category']) : 'post',
            'content' => wp_kses_post($params['content']),
            'platform' => isset($params['platform']) ? sanitize_text_field($params['platform']) : null,
            'hashtags' => isset($params['hashtags']) ? wp_json_encode($params['hashtags']) : null,
            'usage_count' => 0,
            'is_favorite' => 0,
        ];

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            return new WP_Error(
                'save_failed',
                __('Erreur lors de la sauvegarde', 'ai-content-studio'),
                ['status' => 500]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'data' => [
                'id' => $wpdb->insert_id,
                'message' => __('Post sauvegardé avec succès', 'ai-content-studio'),
            ],
        ]);
    }

    /**
     * Get user templates
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_templates($request) {
        $user_id = get_current_user_id();

        global $wpdb;
        $table = $wpdb->prefix . 'acs_saved_templates';

        $templates = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE user_id = %d AND category != 'post'
             ORDER BY created_at DESC",
            $user_id
        ), ARRAY_A);

        return rest_ensure_response([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * Delete item from library
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function delete_item($request) {
        $user_id = get_current_user_id();
        $item_id = $request['id'];
        $type = $request['type'];

        global $wpdb;
        $table = $wpdb->prefix . 'acs_saved_templates';

        $result = $wpdb->delete(
            $table,
            ['id' => $item_id, 'user_id' => $user_id]
        );

        if ($result === false || $result === 0) {
            return new WP_Error('delete_failed', 'Failed to delete item', ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Élément supprimé', 'ai-content-studio'),
        ]);
    }

    /**
     * Toggle favorite status
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function toggle_favorite($request) {
        $user_id = get_current_user_id();
        $item_id = $request['id'];

        global $wpdb;
        $table = $wpdb->prefix . 'acs_saved_templates';

        // Get current status
        $current = $wpdb->get_var($wpdb->prepare(
            "SELECT is_favorite FROM {$table} WHERE id = %d AND user_id = %d",
            $item_id,
            $user_id
        ));

        if ($current === null) {
            return new WP_Error('not_found', 'Item not found', ['status' => 404]);
        }

        // Toggle
        $new_status = $current ? 0 : 1;

        $wpdb->update(
            $table,
            ['is_favorite' => $new_status],
            ['id' => $item_id, 'user_id' => $user_id]
        );

        return rest_ensure_response([
            'success' => true,
            'is_favorite' => $new_status,
        ]);
    }
}
