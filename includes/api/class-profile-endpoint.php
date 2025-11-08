<?php
/**
 * Profile REST Endpoint
 *
 * @package ACS\API
 */

namespace ACS\API;

use ACS\Models\Business_Profile;
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
    }

    public function get_profile($request) {
        $model = new Business_Profile();
        $profile = $model->get_by_user($this->get_current_user_id());

        if (!$profile) {
            return $this->error(__('Profil non trouvé', 'ai-content-studio'), 'not_found', 404);
        }

        return $this->success($profile);
    }

    public function create_or_update_profile($request) {
        $user_id = $this->get_current_user_id();
        $params = $request->get_json_params();

        $model = new Business_Profile();
        $existing = $model->get_by_user($user_id);

        $params['user_id'] = $user_id;

        if ($existing) {
            $result = $model->update($user_id, $params);
            $message = __('Profil mis à jour', 'ai-content-studio');
        } else {
            $result = $model->create($params);
            $message = __('Profil créé', 'ai-content-studio');

            // Mark onboarding as complete
            update_user_meta($user_id, 'acs_onboarding_completed', true);

            // Generate strategy automatically
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

        if ($result === false) {
            return $this->error(__('Erreur lors de la sauvegarde', 'ai-content-studio'));
        }

        return $this->success(['id' => $result], $message);
    }
}
