<?php
/**
 * Profile REST Endpoint
 *
 * @package ACS\API
 */

namespace ACS\API;

use ACS\Models\Business_Profile;
use ACS\Models\Project_Model;
use ACS\Services\Claude_Service;

class Profile_Endpoint extends REST_Controller {

    public function register_routes() {
        register_rest_route($this->namespace, '/profile', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_profile'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_or_update_profile'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/profile/language', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_user_language'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);
    }

    public function get_profile($request) {
        $user_id = $this->get_current_user_id();

        // Try to get active project first (new system)
        $project_model = new Project_Model();
        $active_project = $project_model->get_active_project($user_id);

        if ($active_project) {
            // User has projects, return active project data
            // Convert project format to profile format for backward compatibility
            $profile_data = [
                'id' => $active_project['id'],
                'user_id' => $active_project['user_id'],
                'business_name' => $active_project['project_name'],
                'user_type' => $active_project['user_type'],
                'sector' => $active_project['sector'],
                'description' => $active_project['description'],
                'website' => $active_project['website'],
                'goals' => $active_project['goals'],
                'social_platforms' => $active_project['platforms'],
                'posting_frequency' => $active_project['posting_frequency'],
                'has_blog' => $active_project['has_blog'],
                'blog_topics' => $active_project['blog_topics'],
                'seo_goals' => $active_project['seo_goals'],
                'primary_keywords' => $active_project['primary_keywords'],
                'niche' => $active_project['niche'],
                'target_audience' => $active_project['target_audience'],
                'languages' => $active_project['languages'],
                'created_at' => $active_project['created_at'],
                'updated_at' => $active_project['updated_at'],
            ];

            return $this->success($profile_data);
        }

        // Fallback to old business_profiles system (for backwards compatibility)
        $model = new Business_Profile();
        $profile = $model->get_by_user($user_id);

        if (!$profile) {
            return $this->error(__('Profil non trouvé', 'ai-content-studio'), 'not_found', 404);
        }

        return $this->success($profile);
    }

    public function create_or_update_profile($request) {
        $user_id = $this->get_current_user_id();
        $params = $request->get_json_params();

        // Log received data
        if (class_exists('ACS\\Utils\\Logger')) {
            \ACS\Utils\Logger::info('Profile save attempt', [
                'user_id' => $user_id,
                'params' => $params
            ]);
        }

        // Validate required fields
        if (empty($params['business_name'])) {
            if (class_exists('ACS\\Utils\\Logger')) {
                \ACS\Utils\Logger::error('Profile save failed: missing business_name');
            }
            return $this->error(__('Le nom de l\'entreprise est requis', 'ai-content-studio'), 'missing_business_name', 400);
        }

        $model = new Business_Profile();
        $existing = $model->get_by_user($user_id);

        $params['user_id'] = $user_id;

        if ($existing) {
            $result = $model->update($user_id, $params);
            $message = __('Profil mis à jour', 'ai-content-studio');

            if (class_exists('ACS\\Utils\\Logger')) {
                \ACS\Utils\Logger::info('Profile updated', ['result' => $result]);
            }
        } else {
            $result = $model->create($params);
            $message = __('Profil créé', 'ai-content-studio');

            if (class_exists('ACS\\Utils\\Logger')) {
                \ACS\Utils\Logger::info('Profile created', ['result' => $result]);
            }

            // Mark onboarding as complete
            update_user_meta($user_id, 'acs_onboarding_completed', true);

            // Generate strategy automatically (skip if API keys not configured)
            $claude_api_key = get_option('acs_claude_api_key');
            if (!empty($claude_api_key)) {
                $claude = new Claude_Service();
                $strategy = $claude->generate_strategy($params);

                if (!is_wp_error($strategy)) {
                    global $wpdb;
                    $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'strategies';
                    $wpdb->insert($table, [
                        'user_id' => $user_id,
                        'content_strategy' => wp_json_encode($strategy),
                        'posting_schedule' => wp_json_encode($strategy['posting_schedule'] ?? []),
                        'content_pillars' => wp_json_encode($strategy['content_pillars'] ?? []),
                        'tone_style' => $strategy['tone_style'] ?? '',
                        'hashtags' => wp_json_encode($strategy['hashtag_strategy'] ?? []),
                    ]);
                }
            }
        }

        if ($result === false) {
            if (class_exists('ACS\\Utils\\Logger')) {
                \ACS\Utils\Logger::error('Profile save failed: database error');
            }
            return $this->error(__('Erreur lors de la sauvegarde', 'ai-content-studio'), 'database_error', 500);
        }

        return $this->success(['id' => $result], $message);
    }

    /**
     * Get user's preferred language
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_user_language($request) {
        $user_id = $this->get_current_user_id();
        $language = get_user_meta($user_id, 'acs_preferred_language', true);

        // Default to French if no language is set
        if (empty($language)) {
            $language = 'fr';
        }

        return $this->success(['language' => $language]);
    }
}
