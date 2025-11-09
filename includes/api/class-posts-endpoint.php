<?php
/**
 * Social Posts REST Endpoint
 *
 * @package ACS\API
 */

namespace ACS\API;

use ACS\Models\Social_Post;
use ACS\Models\Business_Profile;
use ACS\Services\Claude_Service;
use ACS\Services\Subscription_Service;
use ACS\Services\Usage_Service;

class Posts_Endpoint extends REST_Controller {

    public function register_routes() {
        register_rest_route($this->namespace, '/posts', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_posts'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_post'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/posts/generate', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_posts'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route($this->namespace, '/posts/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_post'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_post'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_post'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);
    }

    public function get_posts($request) {
        $user_id = $this->get_current_user_id();
        $filters = [
            'platform' => $request->get_param('platform'),
            'status' => $request->get_param('status'),
            'limit' => $request->get_param('limit') ?? 50,
            'offset' => $request->get_param('offset') ?? 0,
        ];

        $model = new Social_Post();
        $posts = $model->get_by_user($user_id, $filters);

        return $this->success($posts);
    }

    public function get_post($request) {
        $id = $request->get_param('id');
        $model = new Social_Post();
        $post = $model->get($id, $this->get_current_user_id());

        if (!$post) {
            return $this->error(__('Post introuvable', 'ai-content-studio'), 'not_found', 404);
        }

        return $this->success($post);
    }

    public function generate_posts($request) {
        $user_id = $this->get_current_user_id();

        \ACS\Utils\Logger::info('Post generation started', [
            'user_id' => $user_id,
        ], 'generation');

        // VÉRIFICATION LIMITE CRITIQUE
        $subscription_service = new Subscription_Service();
        if (!$subscription_service->can_generate_post($user_id)) {
            $plan = $subscription_service->get_user_plan($user_id);
            $limits = $subscription_service->get_plan_limits($plan);

            \ACS\Utils\Logger::warning('Post generation limit reached', [
                'user_id' => $user_id,
                'plan' => $plan,
                'limit' => $limits['posts_per_month'] ?? 0,
            ], 'generation');

            return $this->error(
                sprintf(__('Vous avez atteint votre limite de %d posts pour le plan %s. Passez à un plan supérieur.', 'ai-content-studio'), $limits['posts_per_month'] ?? 0, $plan),
                'limit_reached',
                403
            );
        }

        $params = $request->get_json_params();

        \ACS\Utils\Logger::info('Post generation params received', [
            'user_id' => $user_id,
            'platform' => $params['platform'] ?? 'unknown',
            'topic' => $params['topic'] ?? '',
            'language' => $params['language'] ?? '',
            'content_type' => $params['content_type'] ?? '',
            'template_id' => $params['template_id'] ?? '',
        ], 'generation');

        // Get profile and strategy
        $profile_model = new Business_Profile();
        $profile = $profile_model->get_by_user($user_id);

        if (!$profile) {
            \ACS\Utils\Logger::error('Post generation failed: no profile found', [
                'user_id' => $user_id,
            ], 'generation');

            return $this->error(__('Créez d\'abord votre profil business', 'ai-content-studio'), 'no_profile', 400);
        }

        $params['profile'] = $profile;

        // Generate posts with Claude
        $claude = new Claude_Service();
        $variants = $claude->generate_social_posts($params);

        if (is_wp_error($variants)) {
            \ACS\Utils\Logger::error('Post generation failed: Claude API error', [
                'user_id' => $user_id,
                'error_message' => $variants->get_error_message(),
                'error_code' => $variants->get_error_code(),
            ], 'generation');

            return $this->error($variants->get_error_message(), 'generation_error');
        }

        // Save posts to database
        $post_model = new Social_Post();
        $saved_posts = [];

        foreach ($variants as $variant) {
            $post_data = [
                'user_id' => $user_id,
                'platform' => $params['platform'],
                'content' => $variant['content'],
                'hashtags' => $variant['hashtags'] ?? [],
                'language' => $params['language'] ?? 'fr',
                'status' => 'draft',
                'metadata' => $variant,
            ];

            $post_id = $post_model->create($post_data);
            if ($post_id) {
                $saved_posts[] = $post_model->get($post_id);
            }
        }

        // Increment usage
        $usage_service = new Usage_Service();
        $usage_service->increment_post_usage($user_id);

        return $this->success($saved_posts, __('Posts générés avec succès', 'ai-content-studio'));
    }

    public function create_post($request) {
        $params = $request->get_json_params();
        $params['user_id'] = $this->get_current_user_id();

        $model = new Social_Post();
        $id = $model->create($params);

        if (!$id) {
            return $this->error(__('Erreur lors de la création', 'ai-content-studio'));
        }

        return $this->success(['id' => $id], __('Post créé', 'ai-content-studio'));
    }

    public function update_post($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params();

        $model = new Social_Post();
        $result = $model->update($id, $params, $this->get_current_user_id());

        if ($result === false) {
            return $this->error(__('Erreur lors de la mise à jour', 'ai-content-studio'));
        }

        return $this->success(null, __('Post mis à jour', 'ai-content-studio'));
    }

    public function delete_post($request) {
        $id = $request->get_param('id');

        $model = new Social_Post();
        $result = $model->delete($id, $this->get_current_user_id());

        if ($result === false) {
            return $this->error(__('Erreur lors de la suppression', 'ai-content-studio'));
        }

        return $this->success(null, __('Post supprimé', 'ai-content-studio'));
    }
}
