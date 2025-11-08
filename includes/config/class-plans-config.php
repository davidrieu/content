<?php
/**
 * Plans Configuration
 *
 * @package ACS
 */

namespace ACS\Config;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plans_Config class
 */
class Plans_Config {

    /**
     * Get all available plans
     *
     * @return array
     */
    public static function get_plans() {
        return [
            'free' => [
                'name' => __('Gratuit', 'ai-content-studio'),
                'price' => 0,
                'description' => __('Pour découvrir AI Content Studio', 'ai-content-studio'),
                'limits' => [
                    'posts_per_month' => 5,
                    'articles_per_month' => 1,
                    'images_per_month' => 2,
                    'videos_per_month' => 0,
                    'languages' => 1,
                    'platforms' => 2,
                    'scheduling' => false,
                    'auto_publish' => false,
                    'brand_kit' => false,
                    'templates' => 50,
                    'trends' => true,
                    'hashtags' => true,
                    'analytics' => 'basic',
                    'social_connections' => 0,
                    'support' => 'community',
                ],
            ],
            'starter' => [
                'name' => __('Starter', 'ai-content-studio'),
                'price' => 19,
                'description' => __('Pour les créateurs de contenu', 'ai-content-studio'),
                'wc_product_id' => get_option('acs_product_starter_id'),
                'stripe_price_id' => get_option('acs_stripe_price_starter'),
                'limits' => [
                    'posts_per_month' => 50,
                    'articles_per_month' => 5,
                    'images_per_month' => 20,
                    'videos_per_month' => 0,
                    'languages' => 3,
                    'platforms' => 5,
                    'scheduling' => true,
                    'auto_publish' => true,
                    'brand_kit' => 'basic',
                    'templates' => 200,
                    'trends' => true,
                    'hashtags' => true,
                    'analytics' => 'standard',
                    'social_connections' => 3,
                    'competitor_analysis' => 0,
                    'support' => 'email',
                ],
            ],
            'pro' => [
                'name' => __('Pro', 'ai-content-studio'),
                'price' => 49,
                'description' => __('Pour les professionnels du marketing', 'ai-content-studio'),
                'wc_product_id' => get_option('acs_product_pro_id'),
                'stripe_price_id' => get_option('acs_stripe_price_pro'),
                'limits' => [
                    'posts_per_month' => 200,
                    'articles_per_month' => 20,
                    'images_per_month' => 100,
                    'videos_per_month' => 0,
                    'languages' => -1, // Illimité
                    'platforms' => -1,
                    'scheduling' => true,
                    'auto_publish' => true,
                    'brand_kit' => 'complete',
                    'templates' => -1,
                    'trends' => true,
                    'hashtags' => true,
                    'analytics' => 'advanced',
                    'social_connections' => 10,
                    'competitor_analysis' => 3,
                    'ab_testing' => true,
                    'support' => 'priority',
                ],
            ],
            'business' => [
                'name' => __('Business', 'ai-content-studio'),
                'price' => 99,
                'description' => __('Pour les agences et entreprises', 'ai-content-studio'),
                'wc_product_id' => get_option('acs_product_business_id'),
                'stripe_price_id' => get_option('acs_stripe_price_business'),
                'limits' => [
                    'posts_per_month' => -1, // Illimité
                    'articles_per_month' => -1,
                    'images_per_month' => -1,
                    'videos_per_month' => 50,
                    'languages' => -1,
                    'platforms' => -1,
                    'scheduling' => true,
                    'auto_publish' => true,
                    'brand_kit' => 'complete',
                    'templates' => -1,
                    'trends' => true,
                    'hashtags' => true,
                    'analytics' => 'advanced',
                    'social_connections' => -1,
                    'competitor_analysis' => 10,
                    'ab_testing' => true,
                    'team_members' => 3,
                    'api_access' => true,
                    'white_label' => false,
                    'support' => 'dedicated',
                ],
            ],
        ];
    }

    /**
     * Get plan configuration
     *
     * @param string $plan_slug
     * @return array|null
     */
    public static function get_plan($plan_slug) {
        $plans = self::get_plans();
        return $plans[$plan_slug] ?? null;
    }

    /**
     * Get plan limits
     *
     * @param string $plan_slug
     * @return array
     */
    public static function get_plan_limits($plan_slug) {
        $plan = self::get_plan($plan_slug);
        return $plan ? $plan['limits'] : [];
    }

    /**
     * Check if plan has feature
     *
     * @param string $plan_slug
     * @param string $feature
     * @return bool
     */
    public static function has_feature($plan_slug, $feature) {
        $limits = self::get_plan_limits($plan_slug);
        return isset($limits[$feature]) && $limits[$feature] !== false && $limits[$feature] !== 0;
    }

    /**
     * Get default plan
     *
     * @return string
     */
    public static function get_default_plan() {
        return 'free';
    }

    /**
     * Check if plan exists
     *
     * @param string $plan_slug
     * @return bool
     */
    public static function plan_exists($plan_slug) {
        $plans = self::get_plans();
        return isset($plans[$plan_slug]);
    }

    /**
     * Get all plan slugs
     *
     * @return array
     */
    public static function get_plan_slugs() {
        return array_keys(self::get_plans());
    }

    /**
     * Get paid plans only
     *
     * @return array
     */
    public static function get_paid_plans() {
        $plans = self::get_plans();
        return array_filter($plans, function($plan) {
            return $plan['price'] > 0;
        });
    }
}
