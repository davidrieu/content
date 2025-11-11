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
            'free_trial' => [
                'name' => __('Free Trial', 'ai-content-studio'),
                'price' => 0,
                'description' => __('Essai gratuit - 1 article + 1 post', 'ai-content-studio'),
                'is_trial' => true,
                'limits' => [
                    'posts_per_month' => 1,
                    'articles_per_month' => 1,
                    'images_per_month' => 0,
                    'videos_per_month' => 0,
                    'languages' => -1, // Toutes les langues en trial
                    'platforms' => -1, // Toutes les plateformes en trial
                    'scheduling' => false,
                    'auto_publish' => false,
                    'brand_kit' => false,
                    'templates' => -1,
                    'trends' => true,
                    'hashtags' => true,
                    'analytics' => 'basic',
                    'social_connections' => 0,
                    'support' => 'none',
                ],
            ],
            'starter' => [
                'name' => __('Starter', 'ai-content-studio'),
                'price' => 29, // USD
                'currency' => 'USD',
                'description' => __('Pour les entrepreneurs et créateurs', 'ai-content-studio'),
                'wc_product_id' => get_option('acs_product_starter_id'),
                'stripe_price_id' => get_option('acs_stripe_price_starter'),
                'limits' => [
                    'posts_per_month' => 50,
                    'articles_per_month' => 5,
                    'images_per_month' => 25,
                    'videos_per_month' => 0,
                    'languages' => -1,
                    'platforms' => -1,
                    'scheduling' => true,
                    'auto_publish' => true,
                    'brand_kit' => 'basic',
                    'templates' => -1,
                    'trends' => true,
                    'hashtags' => true,
                    'analytics' => 'standard',
                    'social_connections' => 5,
                    'competitor_analysis' => 0,
                    'support' => 'email',
                    'team_members' => 1,
                ],
            ],
            'professional' => [
                'name' => __('Professional', 'ai-content-studio'),
                'price' => 59, // USD
                'currency' => 'USD',
                'description' => __('Pour les professionnels du marketing', 'ai-content-studio'),
                'wc_product_id' => get_option('acs_product_professional_id'),
                'stripe_price_id' => get_option('acs_stripe_price_professional'),
                'popular' => true, // Badge "Populaire"
                'limits' => [
                    'posts_per_month' => 300,
                    'articles_per_month' => 30,
                    'images_per_month' => 150,
                    'videos_per_month' => 0,
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
                    'competitor_analysis' => 3,
                    'ab_testing' => true,
                    'support' => 'priority',
                    'team_members' => 1,
                ],
            ],
            'business' => [
                'name' => __('Business', 'ai-content-studio'),
                'price' => 149, // USD
                'currency' => 'USD',
                'description' => __('Pour les agences et entreprises', 'ai-content-studio'),
                'wc_product_id' => get_option('acs_product_business_id'),
                'stripe_price_id' => get_option('acs_stripe_price_business'),
                'limits' => [
                    'posts_per_month' => -1, // Illimité
                    'articles_per_month' => -1,
                    'images_per_month' => -1,
                    'videos_per_month' => 0,
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
                    'competitor_analysis' => -1,
                    'ab_testing' => true,
                    'team_members' => 5,
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
        return 'free_trial';
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
