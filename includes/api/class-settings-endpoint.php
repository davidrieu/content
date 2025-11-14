<?php
/**
 * Settings REST Endpoint
 *
 * @package ACS\API
 */

namespace ACS\API;

use ACS\Services\Subscription_Service;
use ACS\Services\Usage_Service;
use ACS\Models\Project;
use ACS\Models\Social_Post;

class Settings_Endpoint extends REST_Controller {

    public function register_routes() {
        // Preferences
        register_rest_route($this->namespace, '/settings/preferences', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_preferences'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'save_preferences'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);

        // Export data
        register_rest_route($this->namespace, '/settings/export', [
            'methods' => 'GET',
            'callback' => [$this, 'export_data'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        // Subscription status
        register_rest_route($this->namespace, '/subscription/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_subscription_status'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        // Change password
        register_rest_route($this->namespace, '/settings/password', [
            'methods' => 'POST',
            'callback' => [$this, 'change_password'],
            'permission_callback' => [$this, 'permission_check'],
        ]);
    }

    /**
     * Get user preferences
     */
    public function get_preferences($request) {
        $user_id = $this->get_current_user_id();

        $preferences = get_user_meta($user_id, 'acs_preferences', true);

        // Default preferences if none exist
        if (!$preferences || !is_array($preferences)) {
            $preferences = [
                'default_language' => 'fr',
                'default_tone' => 'professional',
                'use_emojis' => true,
                'use_hashtags' => true,
                'content_length' => 'medium',
            ];
        }

        return $this->success($preferences);
    }

    /**
     * Save user preferences
     */
    public function save_preferences($request) {
        $user_id = $this->get_current_user_id();
        $data = $request->get_json_params();

        // Validate and sanitize
        $preferences = [
            'default_language' => sanitize_text_field($data['default_language'] ?? 'fr'),
            'default_tone' => sanitize_text_field($data['default_tone'] ?? 'professional'),
            'use_emojis' => (bool) ($data['use_emojis'] ?? true),
            'use_hashtags' => (bool) ($data['use_hashtags'] ?? true),
            'content_length' => sanitize_text_field($data['content_length'] ?? 'medium'),
        ];

        update_user_meta($user_id, 'acs_preferences', $preferences);

        return $this->success($preferences, __('Préférences sauvegardées', 'ai-content-studio'));
    }

    /**
     * Get subscription status and usage
     */
    public function get_subscription_status($request) {
        $user_id = $this->get_current_user_id();

        $subscription_service = new Subscription_Service();
        $usage_service = new Usage_Service();

        $plan = $subscription_service->get_user_plan($user_id);
        $status = $subscription_service->get_subscription_status($user_id);
        $limits = $subscription_service->get_plan_limits($plan);

        // Get current month usage
        $usage = $usage_service->get_monthly_usage($user_id);

        return $this->success([
            'plan' => $plan,
            'status' => $status,
            'usage' => [
                'posts' => $usage['posts'] ?? 0,
                'articles' => $usage['articles'] ?? 0,
            ],
            'limits' => $limits,
        ]);
    }

    /**
     * Export all user data
     */
    public function export_data($request) {
        $user_id = $this->get_current_user_id();

        global $wpdb;
        $table_prefix = $wpdb->prefix . ACS_TABLE_PREFIX;

        $export_data = [
            'export_date' => current_time('mysql'),
            'user_id' => $user_id,
        ];

        // Export projects
        $project_model = new Project();
        $export_data['projects'] = $project_model->get_by_user($user_id);

        // Export social posts
        $posts_table = $table_prefix . 'social_posts';
        $export_data['social_posts'] = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$posts_table} WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ), ARRAY_A);

        // Export blog articles
        $articles_table = $table_prefix . 'blog_articles';
        $export_data['blog_articles'] = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$articles_table} WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ), ARRAY_A);

        // Export strategies
        $strategies_table = $table_prefix . 'strategies';
        $export_data['strategies'] = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$strategies_table} WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ), ARRAY_A);

        // Export blog strategies
        $blog_strategies_table = $table_prefix . 'blog_strategies';
        $export_data['blog_strategies'] = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$blog_strategies_table} WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ), ARRAY_A);

        // Export preferences
        $export_data['preferences'] = get_user_meta($user_id, 'acs_preferences', true);

        // Export subscription info
        $subscription_service = new Subscription_Service();
        $export_data['subscription'] = [
            'plan' => $subscription_service->get_user_plan($user_id),
            'status' => $subscription_service->get_subscription_status($user_id),
        ];

        return $this->success($export_data);
    }

    /**
     * Change user password
     */
    public function change_password($request) {
        $user_id = $this->get_current_user_id();
        $data = $request->get_json_params();

        // Validate required fields
        if (empty($data['current_password']) || empty($data['new_password'])) {
            return $this->error(__('Tous les champs sont requis', 'ai-content-studio'), 'missing_fields', 400);
        }

        // Verify current password
        $user = get_user_by('id', $user_id);
        if (!$user || !wp_check_password($data['current_password'], $user->user_pass, $user_id)) {
            return $this->error(__('Mot de passe actuel incorrect', 'ai-content-studio'), 'invalid_password', 403);
        }

        // Validate new password strength (minimum 8 characters)
        if (strlen($data['new_password']) < 8) {
            return $this->error(__('Le nouveau mot de passe doit contenir au moins 8 caractères', 'ai-content-studio'), 'weak_password', 400);
        }

        // Update password
        wp_set_password($data['new_password'], $user_id);

        return $this->success(null, __('Mot de passe modifié avec succès', 'ai-content-studio'));
    }
}
