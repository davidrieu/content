<?php
/**
 * Subscription REST Endpoint
 *
 * @package ACS\API
 */

namespace ACS\API;

use ACS\Services\Subscription_Service;
use ACS\Services\Usage_Service;

class Subscription_Endpoint extends REST_Controller {

    public function register_routes() {
        register_rest_route($this->namespace, '/subscription', [
            'methods' => 'GET',
            'callback' => [$this, 'get_subscription'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route($this->namespace, '/subscription/usage', [
            'methods' => 'GET',
            'callback' => [$this, 'get_usage'],
            'permission_callback' => [$this, 'permission_check'],
        ]);
    }

    public function get_subscription($request) {
        $subscription_service = new Subscription_Service();
        $info = $subscription_service->get_subscription_info($this->get_current_user_id());

        return $this->success($info);
    }

    public function get_usage($request) {
        $usage_service = new Usage_Service();
        $summary = $usage_service->get_usage_summary($this->get_current_user_id());

        return $this->success($summary);
    }
}
