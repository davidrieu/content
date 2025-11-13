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
            return $this->error(
                __('WooCommerce n\'est pas installé. Veuillez installer et activer WooCommerce pour gérer les abonnements.', 'ai-content-studio'),
                'woocommerce_not_active',
                400
            );
        }

        // Check if WooCommerce Subscriptions is active
        if (!class_exists('WC_Subscriptions')) {
            return $this->error(
                __('WooCommerce Subscriptions n\'est pas installé. Ce plugin est requis pour gérer les abonnements récurrents.', 'ai-content-studio'),
                'woocommerce_subscriptions_not_active',
                400
            );
        }

        // Get product ID for this plan
        $product_id = get_option('acs_product_' . $plan_slug . '_id');

        if (!$product_id) {
            $is_admin = current_user_can('manage_options');
            $error_message = $is_admin
                ? __('Les produits d\'abonnement n\'ont pas encore été créés. Veuillez aller dans "AI Content Studio > Abonnements" pour créer les produits WooCommerce.', 'ai-content-studio')
                : __('Les produits d\'abonnement ne sont pas encore configurés. Veuillez contacter l\'administrateur du site.', 'ai-content-studio');

            return $this->error($error_message, 'product_not_configured', 404);
        }

        // Verify product exists
        $product = wc_get_product($product_id);
        if (!$product) {
            $is_admin = current_user_can('manage_options');
            $error_message = $is_admin
                ? sprintf(
                    __('Le produit WooCommerce (ID: %d) n\'existe plus. Veuillez recréer les produits dans "AI Content Studio > Abonnements".', 'ai-content-studio'),
                    $product_id
                )
                : __('Le produit d\'abonnement n\'existe plus. Veuillez contacter l\'administrateur du site.', 'ai-content-studio');

            return $this->error($error_message, 'product_not_exists', 404);
        }

        // Clear cart
        WC()->cart->empty_cart();

        // Add product to cart
        $added = WC()->cart->add_to_cart($product_id);

        if (!$added) {
            return $this->error(
                __('Impossible d\'ajouter le produit au panier. Veuillez réessayer.', 'ai-content-studio'),
                'cart_add_failed',
                500
            );
        }

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
