<?php
/**
 * Subscription Service
 *
 * @package ACS\Services
 */

namespace ACS\Services;

use ACS\Config\Plans_Config;
use ACS\Utils\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Subscription_Service class
 */
class Subscription_Service {

    /**
     * Get user's current plan
     *
     * @param int|null $user_id
     * @return string
     */
    public function get_user_plan($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $plan = get_user_meta($user_id, 'acs_subscription_plan', true);
        return $plan ?: Plans_Config::get_default_plan();
    }

    /**
     * Get user's subscription status
     *
     * @param int|null $user_id
     * @return string
     */
    public function get_subscription_status($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return get_user_meta($user_id, 'acs_subscription_status', true) ?: 'active';
    }

    /**
     * Get plan limits for a plan
     *
     * @param string $plan_slug
     * @return array
     */
    public function get_plan_limits($plan_slug) {
        return Plans_Config::get_plan_limits($plan_slug);
    }

    /**
     * Get user's plan limits
     *
     * @param int|null $user_id
     * @return array
     */
    public function get_user_limits($user_id = null) {
        $plan = $this->get_user_plan($user_id);
        return $this->get_plan_limits($plan);
    }

    /**
     * Check if user can generate a post
     *
     * @param int|null $user_id
     * @return bool
     */
    public function can_generate_post($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Check subscription status
        if ($this->get_subscription_status($user_id) !== 'active') {
            return false;
        }

        $limits = $this->get_user_limits($user_id);
        $posts_limit = $limits['posts_per_month'] ?? 0;

        // -1 means unlimited
        if ($posts_limit === -1) {
            return true;
        }

        $usage_service = new Usage_Service();
        $usage = $usage_service->get_usage_stats($user_id);
        $posts_this_month = $usage['posts_this_month'] ?? 0;

        return $posts_this_month < $posts_limit;
    }

    /**
     * Check if user can generate an article
     *
     * @param int|null $user_id
     * @return bool
     */
    public function can_generate_article($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if ($this->get_subscription_status($user_id) !== 'active') {
            return false;
        }

        $limits = $this->get_user_limits($user_id);
        $articles_limit = $limits['articles_per_month'] ?? 0;

        if ($articles_limit === -1) {
            return true;
        }

        $usage_service = new Usage_Service();
        $usage = $usage_service->get_usage_stats($user_id);
        $articles_this_month = $usage['articles_this_month'] ?? 0;

        return $articles_this_month < $articles_limit;
    }

    /**
     * Check if user can generate an image
     *
     * @param int|null $user_id
     * @return bool
     */
    public function can_generate_image($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if ($this->get_subscription_status($user_id) !== 'active') {
            return false;
        }

        $limits = $this->get_user_limits($user_id);
        $images_limit = $limits['images_per_month'] ?? 0;

        if ($images_limit === -1) {
            return true;
        }

        $usage_service = new Usage_Service();
        $usage = $usage_service->get_usage_stats($user_id);
        $images_this_month = $usage['images_this_month'] ?? 0;

        return $images_this_month < $images_limit;
    }

    /**
     * Update user's plan
     *
     * @param int $user_id
     * @param string $plan_slug
     * @return bool
     */
    public function update_user_plan($user_id, $plan_slug) {
        if (!Plans_Config::plan_exists($plan_slug)) {
            Logger::error('Attempted to assign invalid plan: ' . $plan_slug, ['user_id' => $user_id]);
            return false;
        }

        $old_plan = $this->get_user_plan($user_id);
        update_user_meta($user_id, 'acs_subscription_plan', $plan_slug);

        Logger::info(
            sprintf('User plan updated from %s to %s', $old_plan, $plan_slug),
            ['user_id' => $user_id]
        );

        // Trigger action for potential integrations
        do_action('acs_user_plan_updated', $user_id, $plan_slug, $old_plan);

        return true;
    }

    /**
     * Update user's subscription status
     *
     * @param int $user_id
     * @param string $status
     * @return bool
     */
    public function update_subscription_status($user_id, $status) {
        $allowed_statuses = ['active', 'canceled', 'expired', 'trialing', 'pending'];

        if (!in_array($status, $allowed_statuses)) {
            Logger::error('Invalid subscription status: ' . $status, ['user_id' => $user_id]);
            return false;
        }

        $old_status = $this->get_subscription_status($user_id);
        update_user_meta($user_id, 'acs_subscription_status', $status);

        Logger::info(
            sprintf('Subscription status updated from %s to %s', $old_status, $status),
            ['user_id' => $user_id]
        );

        do_action('acs_subscription_status_updated', $user_id, $status, $old_status);

        return true;
    }

    /**
     * Get remaining quota for user
     *
     * @param int|null $user_id
     * @return array
     */
    public function get_remaining_quota($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $limits = $this->get_user_limits($user_id);
        $usage_service = new Usage_Service();
        $usage = $usage_service->get_usage_stats($user_id);

        $remaining = [];

        // Posts
        if ($limits['posts_per_month'] === -1) {
            $remaining['posts'] = -1; // Unlimited
        } else {
            $remaining['posts'] = max(0, $limits['posts_per_month'] - ($usage['posts_this_month'] ?? 0));
        }

        // Articles
        if ($limits['articles_per_month'] === -1) {
            $remaining['articles'] = -1;
        } else {
            $remaining['articles'] = max(0, $limits['articles_per_month'] - ($usage['articles_this_month'] ?? 0));
        }

        // Images
        if ($limits['images_per_month'] === -1) {
            $remaining['images'] = -1;
        } else {
            $remaining['images'] = max(0, $limits['images_per_month'] - ($usage['images_this_month'] ?? 0));
        }

        return $remaining;
    }

    /**
     * Check if user has feature access
     *
     * @param string $feature
     * @param int|null $user_id
     * @return bool
     */
    public function has_feature($feature, $user_id = null) {
        $plan = $this->get_user_plan($user_id);
        return Plans_Config::has_feature($plan, $feature);
    }

    /**
     * Get subscription info for display
     *
     * @param int|null $user_id
     * @return array
     */
    public function get_subscription_info($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $plan_slug = $this->get_user_plan($user_id);
        $plan = Plans_Config::get_plan($plan_slug);
        $status = $this->get_subscription_status($user_id);
        $remaining = $this->get_remaining_quota($user_id);
        $limits = $this->get_user_limits($user_id);

        return [
            'plan' => $plan_slug,
            'plan_name' => $plan['name'] ?? $plan_slug,
            'plan_price' => $plan['price'] ?? 0,
            'status' => $status,
            'limits' => $limits,
            'remaining' => $remaining,
            'wc_subscription_id' => get_user_meta($user_id, 'acs_wc_subscription_id', true),
        ];
    }

    /**
     * Check if plan is upgradeable
     *
     * @param string $from_plan
     * @param string $to_plan
     * @return bool
     */
    public function can_upgrade($from_plan, $to_plan) {
        $plan_hierarchy = ['free', 'starter', 'pro', 'business'];

        $from_index = array_search($from_plan, $plan_hierarchy);
        $to_index = array_search($to_plan, $plan_hierarchy);

        return $to_index > $from_index;
    }
}
