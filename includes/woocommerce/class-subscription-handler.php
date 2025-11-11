<?php
/**
 * WooCommerce Subscription Handler
 *
 * @package ACS\WooCommerce
 */

namespace ACS\WooCommerce;

use ACS\Services\Subscription_Service;
use ACS\Services\Usage_Service;
use ACS\Utils\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Subscription_Handler class
 */
class Subscription_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Subscription status changes
        add_action('woocommerce_subscription_status_active', [$this, 'on_subscription_activated'], 10, 1);
        add_action('woocommerce_subscription_status_on-hold', [$this, 'on_subscription_on_hold'], 10, 1);
        add_action('woocommerce_subscription_status_cancelled', [$this, 'on_subscription_cancelled'], 10, 1);
        add_action('woocommerce_subscription_status_expired', [$this, 'on_subscription_expired'], 10, 1);
        add_action('woocommerce_subscription_status_pending-cancel', [$this, 'on_subscription_pending_cancel'], 10, 1);

        // Renewal payment
        add_action('woocommerce_subscription_renewal_payment_complete', [$this, 'on_renewal_payment_complete'], 10, 2);
        add_action('woocommerce_subscription_renewal_payment_failed', [$this, 'on_renewal_payment_failed'], 10, 2);

        // Trial period
        add_action('woocommerce_scheduled_subscription_trial_end', [$this, 'on_trial_end'], 10, 1);

        // Product purchase
        add_action('woocommerce_subscription_payment_complete', [$this, 'on_payment_complete'], 10, 1);
    }

    /**
     * Handle subscription activation
     *
     * @param \WC_Subscription $subscription
     */
    public function on_subscription_activated($subscription) {
        $user_id = $subscription->get_user_id();
        $plan_slug = $this->get_plan_from_subscription($subscription);

        if (!$plan_slug) {
            Logger::error('Could not determine plan from subscription', ['subscription_id' => $subscription->get_id()]);
            return;
        }

        $subscription_service = new Subscription_Service();

        // Update user plan
        $subscription_service->update_user_plan($user_id, $plan_slug);

        // Set subscription status to active
        $subscription_service->update_subscription_status($user_id, 'active');

        // Save WC subscription ID
        update_user_meta($user_id, 'acs_wc_subscription_id', $subscription->get_id());

        // Reset usage if this is a renewal
        $usage_service = new Usage_Service();
        if ($usage_service->needs_reset($user_id)) {
            $usage_service->reset_monthly_usage($user_id);
        }

        Logger::info('Subscription activated', [
            'user_id' => $user_id,
            'plan' => $plan_slug,
            'subscription_id' => $subscription->get_id(),
        ]);

        // Send welcome email (optional)
        do_action('acs_subscription_activated', $user_id, $plan_slug, $subscription);
    }

    /**
     * Handle subscription on hold
     *
     * @param \WC_Subscription $subscription
     */
    public function on_subscription_on_hold($subscription) {
        $user_id = $subscription->get_user_id();

        $subscription_service = new Subscription_Service();
        $subscription_service->update_subscription_status($user_id, 'on-hold');

        Logger::warning('Subscription on hold', [
            'user_id' => $user_id,
            'subscription_id' => $subscription->get_id(),
        ]);
    }

    /**
     * Handle subscription cancellation
     *
     * @param \WC_Subscription $subscription
     */
    public function on_subscription_cancelled($subscription) {
        $user_id = $subscription->get_user_id();

        $subscription_service = new Subscription_Service();

        // Downgrade to free trial plan
        $subscription_service->update_user_plan($user_id, 'free_trial');
        $subscription_service->update_subscription_status($user_id, 'canceled');

        // Clear WC subscription ID
        delete_user_meta($user_id, 'acs_wc_subscription_id');

        Logger::info('Subscription cancelled - downgraded to free_trial', [
            'user_id' => $user_id,
            'subscription_id' => $subscription->get_id(),
        ]);

        do_action('acs_subscription_cancelled', $user_id, $subscription);
    }

    /**
     * Handle subscription expiration
     *
     * @param \WC_Subscription $subscription
     */
    public function on_subscription_expired($subscription) {
        $user_id = $subscription->get_user_id();

        $subscription_service = new Subscription_Service();

        // Downgrade to free trial plan
        $subscription_service->update_user_plan($user_id, 'free_trial');
        $subscription_service->update_subscription_status($user_id, 'expired');

        delete_user_meta($user_id, 'acs_wc_subscription_id');

        Logger::info('Subscription expired - downgraded to free_trial', [
            'user_id' => $user_id,
            'subscription_id' => $subscription->get_id(),
        ]);

        do_action('acs_subscription_expired', $user_id, $subscription);
    }

    /**
     * Handle pending cancellation
     *
     * @param \WC_Subscription $subscription
     */
    public function on_subscription_pending_cancel($subscription) {
        $user_id = $subscription->get_user_id();

        $subscription_service = new Subscription_Service();
        $subscription_service->update_subscription_status($user_id, 'pending-cancel');

        Logger::info('Subscription pending cancellation', [
            'user_id' => $user_id,
            'subscription_id' => $subscription->get_id(),
        ]);
    }

    /**
     * Handle renewal payment complete
     *
     * @param \WC_Subscription $subscription
     * @param \WC_Order $order
     */
    public function on_renewal_payment_complete($subscription, $order) {
        $user_id = $subscription->get_user_id();

        // Reset monthly usage
        $usage_service = new Usage_Service();
        $usage_service->reset_monthly_usage($user_id);

        // Ensure subscription is active
        $subscription_service = new Subscription_Service();
        $subscription_service->update_subscription_status($user_id, 'active');

        Logger::info('Renewal payment complete - usage reset', [
            'user_id' => $user_id,
            'subscription_id' => $subscription->get_id(),
            'order_id' => $order->get_id(),
        ]);

        do_action('acs_subscription_renewed', $user_id, $subscription, $order);
    }

    /**
     * Handle renewal payment failure
     *
     * @param \WC_Subscription $subscription
     * @param \WC_Order $order
     */
    public function on_renewal_payment_failed($subscription, $order) {
        $user_id = $subscription->get_user_id();

        Logger::warning('Renewal payment failed', [
            'user_id' => $user_id,
            'subscription_id' => $subscription->get_id(),
            'order_id' => $order->get_id(),
        ]);

        // Notify user
        do_action('acs_renewal_payment_failed', $user_id, $subscription, $order);
    }

    /**
     * Handle trial period end
     *
     * @param int $subscription_id
     */
    public function on_trial_end($subscription_id) {
        $subscription = wcs_get_subscription($subscription_id);

        if (!$subscription) {
            return;
        }

        $user_id = $subscription->get_user_id();

        Logger::info('Trial period ended', [
            'user_id' => $user_id,
            'subscription_id' => $subscription_id,
        ]);

        do_action('acs_trial_ended', $user_id, $subscription);
    }

    /**
     * Handle payment complete (initial purchase)
     *
     * @param \WC_Subscription $subscription
     */
    public function on_payment_complete($subscription) {
        $user_id = $subscription->get_user_id();
        $plan_slug = $this->get_plan_from_subscription($subscription);

        if (!$plan_slug) {
            return;
        }

        Logger::info('Initial subscription payment complete', [
            'user_id' => $user_id,
            'plan' => $plan_slug,
            'subscription_id' => $subscription->get_id(),
        ]);

        do_action('acs_subscription_created', $user_id, $plan_slug, $subscription);
    }

    /**
     * Get plan slug from subscription
     *
     * @param \WC_Subscription $subscription
     * @return string|null
     */
    private function get_plan_from_subscription($subscription) {
        $items = $subscription->get_items();

        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);

            if ($product) {
                $plan_slug = $product->get_meta('_acs_plan_slug');

                if ($plan_slug) {
                    return $plan_slug;
                }
            }
        }

        // Fallback: try to match by product ID
        $starter_id = get_option('acs_product_starter_id');
        $professional_id = get_option('acs_product_professional_id');
        $business_id = get_option('acs_product_business_id');

        foreach ($items as $item) {
            $product_id = $item->get_product_id();

            if ($product_id == $starter_id) {
                return 'starter';
            } elseif ($product_id == $professional_id) {
                return 'professional';
            } elseif ($product_id == $business_id) {
                return 'business';
            }
        }

        return null;
    }

    /**
     * Handle subscription switch/upgrade
     *
     * @param \WC_Subscription $subscription
     * @param array $item
     * @param array $old_item
     */
    public function on_subscription_switched($subscription, $item, $old_item) {
        $user_id = $subscription->get_user_id();
        $new_plan = $this->get_plan_from_subscription($subscription);

        if (!$new_plan) {
            return;
        }

        $subscription_service = new Subscription_Service();
        $old_plan = $subscription_service->get_user_plan($user_id);

        $subscription_service->update_user_plan($user_id, $new_plan);

        Logger::info('Subscription plan switched', [
            'user_id' => $user_id,
            'old_plan' => $old_plan,
            'new_plan' => $new_plan,
            'subscription_id' => $subscription->get_id(),
        ]);

        do_action('acs_subscription_switched', $user_id, $new_plan, $old_plan, $subscription);
    }
}
