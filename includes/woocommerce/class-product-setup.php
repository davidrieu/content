<?php
/**
 * WooCommerce Product Setup
 *
 * @package ACS\WooCommerce
 */

namespace ACS\WooCommerce;

use ACS\Config\Plans_Config;
use ACS\Utils\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Product_Setup class
 */
class Product_Setup {

    /**
     * Create subscription products
     *
     * @return array Created product IDs
     */
    public static function create_subscription_products() {
        if (!class_exists('WC_Product_Subscription')) {
            Logger::error('WooCommerce Subscriptions not active');
            return [];
        }

        $created_products = [];
        $plans = Plans_Config::get_paid_plans();

        foreach ($plans as $plan_slug => $plan) {
            // Check if product already exists
            $existing_id = get_option('acs_product_' . $plan_slug . '_id');

            if ($existing_id && get_post($existing_id)) {
                Logger::info('Product already exists for plan: ' . $plan_slug, ['product_id' => $existing_id]);
                $created_products[$plan_slug] = $existing_id;
                continue;
            }

            // Create new subscription product
            $product = new \WC_Product_Subscription();

            // Basic product info
            $product->set_name($plan['name'] . ' - AI Content Studio');
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            $product->set_description(self::get_product_description($plan_slug, $plan));
            $product->set_short_description($plan['description'] ?? '');

            // Pricing
            $product->set_regular_price($plan['price']);
            $product->set_price($plan['price']);

            // Subscription settings
            $product->set_subscription_price($plan['price']);
            $product->set_subscription_period('month');
            $product->set_subscription_period_interval(1);
            $product->set_subscription_length(0); // Never expires

            // Trial period (optional - 7 days for starter)
            if ($plan_slug === 'starter') {
                $product->update_meta_data('_subscription_trial_period', 'day');
                $product->update_meta_data('_subscription_trial_length', '7');
            }

            // Sign-up fee (if applicable)
            $product->set_subscription_sign_up_fee(0);

            // Categories and tags
            $product->set_category_ids([self::get_or_create_category()]);

            // Virtual product
            $product->set_virtual(true);
            $product->set_sold_individually(true);

            // Custom meta
            $product->update_meta_data('_acs_plan_slug', $plan_slug);
            $product->update_meta_data('_acs_plan_limits', wp_json_encode($plan['limits']));

            // Save product
            $product_id = $product->save();

            if ($product_id) {
                // Save product ID to options
                update_option('acs_product_' . $plan_slug . '_id', $product_id);

                $created_products[$plan_slug] = $product_id;

                Logger::info('Created subscription product for plan: ' . $plan_slug, ['product_id' => $product_id]);
            }
        }

        return $created_products;
    }

    /**
     * Get or create AI Content Studio category
     *
     * @return int Category ID
     */
    private static function get_or_create_category() {
        $category_name = 'AI Content Studio';
        $category_slug = 'ai-content-studio';

        $category = get_term_by('slug', $category_slug, 'product_cat');

        if ($category) {
            return $category->term_id;
        }

        $result = wp_insert_term($category_name, 'product_cat', [
            'slug' => $category_slug,
            'description' => 'Plans d\'abonnement AI Content Studio',
        ]);

        if (is_wp_error($result)) {
            Logger::error('Failed to create product category', ['error' => $result->get_error_message()]);
            return 0;
        }

        return $result['term_id'];
    }

    /**
     * Get product description
     *
     * @param string $plan_slug
     * @param array $plan
     * @return string
     */
    private static function get_product_description($plan_slug, $plan) {
        $limits = $plan['limits'];

        $features = [];

        // Posts
        if ($limits['posts_per_month'] === -1) {
            $features[] = '✓ Posts réseaux sociaux illimités';
        } else {
            $features[] = sprintf('✓ %d posts réseaux sociaux par mois', $limits['posts_per_month']);
        }

        // Articles
        if ($limits['articles_per_month'] === -1) {
            $features[] = '✓ Articles de blog illimités';
        } else {
            $features[] = sprintf('✓ %d articles de blog par mois', $limits['articles_per_month']);
        }

        // Images
        if ($limits['images_per_month'] === -1) {
            $features[] = '✓ Images IA illimitées';
        } else {
            $features[] = sprintf('✓ %d images IA par mois', $limits['images_per_month']);
        }

        // Platforms
        if ($limits['platforms'] === -1) {
            $features[] = '✓ Toutes les plateformes sociales';
        } else {
            $features[] = sprintf('✓ Jusqu\'à %d plateformes', $limits['platforms']);
        }

        // Languages
        if ($limits['languages'] === -1) {
            $features[] = '✓ Toutes les langues disponibles';
        } else {
            $features[] = sprintf('✓ Jusqu\'à %d langues', $limits['languages']);
        }

        // Scheduling
        if ($limits['scheduling']) {
            $features[] = '✓ Planification de contenu';
        }

        // Auto-publish
        if ($limits['auto_publish']) {
            $features[] = '✓ Publication automatique';
        }

        // Brand Kit
        if (isset($limits['brand_kit']) && $limits['brand_kit']) {
            $features[] = '✓ Brand Kit ' . ($limits['brand_kit'] === 'complete' ? 'complet' : 'basique');
        }

        // Templates
        if ($limits['templates'] === -1) {
            $features[] = '✓ Tous les templates';
        } else {
            $features[] = sprintf('✓ Accès à %d templates', $limits['templates']);
        }

        // Analytics
        if (isset($limits['analytics'])) {
            $features[] = '✓ Analytics ' . $limits['analytics'];
        }

        // Support
        if (isset($limits['support'])) {
            $features[] = '✓ Support ' . $limits['support'];
        }

        $description = '<h3>' . $plan['name'] . ' - ' . $plan['price'] . '€/mois</h3>';
        $description .= '<p>' . ($plan['description'] ?? '') . '</p>';
        $description .= '<ul>';

        foreach ($features as $feature) {
            $description .= '<li>' . $feature . '</li>';
        }

        $description .= '</ul>';

        return $description;
    }

    /**
     * Delete all subscription products
     *
     * @return int Number of products deleted
     */
    public static function delete_subscription_products() {
        $deleted = 0;
        $plan_slugs = Plans_Config::get_plan_slugs();

        foreach ($plan_slugs as $slug) {
            $product_id = get_option('acs_product_' . $slug . '_id');

            if ($product_id) {
                wp_delete_post($product_id, true);
                delete_option('acs_product_' . $slug . '_id');
                $deleted++;
            }
        }

        Logger::info('Deleted subscription products', ['count' => $deleted]);

        return $deleted;
    }

    /**
     * Update product prices
     *
     * @param string $plan_slug
     * @param float $new_price
     * @return bool
     */
    public static function update_product_price($plan_slug, $new_price) {
        $product_id = get_option('acs_product_' . $plan_slug . '_id');

        if (!$product_id) {
            return false;
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            return false;
        }

        $product->set_regular_price($new_price);
        $product->set_price($new_price);
        $product->set_subscription_price($new_price);
        $product->save();

        Logger::info('Updated product price', [
            'plan' => $plan_slug,
            'product_id' => $product_id,
            'new_price' => $new_price,
        ]);

        return true;
    }
}
