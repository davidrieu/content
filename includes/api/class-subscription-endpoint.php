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

        register_rest_route($this->namespace, '/subscription/checkout-url', [
            'methods' => 'POST',
            'callback' => [$this, 'get_checkout_url'],
            'permission_callback' => [$this, 'permission_check'],
            'args' => [
                'plan' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
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

    public function get_checkout_url($request) {
        $plan_slug = $request->get_param('plan');
        $user_id = $this->get_current_user_id();

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return $this->error(__('WooCommerce n\'est pas installé.', 'ai-content-studio'), 'woocommerce_not_active', 400);
        }

        // Get product ID for this plan
        $product_id = get_option('acs_product_' . $plan_slug . '_id');

        if (!$product_id) {
            return $this->error(__('Produit non trouvé pour ce plan.', 'ai-content-studio'), 'product_not_found', 404);
        }

        // Verify product exists
        $product = wc_get_product($product_id);
        if (!$product) {
            return $this->error(__('Le produit n\'existe pas.', 'ai-content-studio'), 'product_not_exists', 404);
        }

        // Clear cart
        WC()->cart->empty_cart();

        // Add product to cart
        WC()->cart->add_to_cart($product_id);

        // Get checkout URL
        $checkout_url = wc_get_checkout_url();

        return $this->success([
            'checkout_url' => $checkout_url,
            'product_id' => $product_id,
            'product_name' => $product->get_name(),
            'price' => $product->get_price(),
        ]);
    }
}
