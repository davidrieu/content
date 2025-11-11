<?php
/**
 * WooCommerce Product Generator
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
 * Product_Generator class
 * Generates WooCommerce Subscription products automatically
 */
class Product_Generator {

    /**
     * Generate all subscription products
     *
     * @return array Results with success/error messages
     */
    public static function generate_all_products() {
        if (!class_exists('WooCommerce')) {
            return [
                'success' => false,
                'message' => __('WooCommerce n\'est pas installé.', 'ai-content-studio'),
            ];
        }

        if (!class_exists('WC_Subscriptions')) {
            return [
                'success' => false,
                'message' => __('WooCommerce Subscriptions n\'est pas installé.', 'ai-content-studio'),
            ];
        }

        $plans = Plans_Config::get_paid_plans();
        $results = [];
        $created_count = 0;
        $updated_count = 0;
        $skipped_count = 0;

        foreach ($plans as $plan_slug => $plan_data) {
            $result = self::create_or_update_product($plan_slug, $plan_data);
            $results[$plan_slug] = $result;

            if ($result['action'] === 'created') {
                $created_count++;
            } elseif ($result['action'] === 'updated') {
                $updated_count++;
            } else {
                $skipped_count++;
            }
        }

        Logger::info('Products generated', [
            'created' => $created_count,
            'updated' => $updated_count,
            'skipped' => $skipped_count,
        ]);

        return [
            'success' => true,
            'message' => sprintf(
                __('%d produits créés, %d mis à jour, %d ignorés', 'ai-content-studio'),
                $created_count,
                $updated_count,
                $skipped_count
            ),
            'details' => $results,
        ];
    }

    /**
     * Create or update a subscription product
     *
     * @param string $plan_slug
     * @param array $plan_data
     * @return array
     */
    private static function create_or_update_product($plan_slug, $plan_data) {
        $product_id = get_option('acs_product_' . $plan_slug . '_id');
        $action = 'skipped';

        // Check if product exists and is valid
        if ($product_id) {
            $product = wc_get_product($product_id);
            if (!$product || $product->get_status() === 'trash') {
                $product_id = null; // Product doesn't exist, create new one
            }
        }

        if ($product_id) {
            // Update existing product
            $product = new \WC_Product_Subscription($product_id);
            $action = 'updated';
        } else {
            // Create new product
            $product = new \WC_Product_Subscription();
            $action = 'created';
        }

        // Set basic product data
        $product->set_name($plan_data['name'] . ' - AI Content Studio');
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden'); // Hidden from shop
        $product->set_description(self::generate_product_description($plan_slug, $plan_data));
        $product->set_short_description($plan_data['description']);

        // Set pricing
        $currency = $plan_data['currency'] ?? 'USD';
        $product->set_regular_price($plan_data['price']);

        // Set subscription data
        $product->update_meta_data('_subscription_price', $plan_data['price']);
        $product->update_meta_data('_subscription_period', 'month');
        $product->update_meta_data('_subscription_period_interval', '1');
        $product->update_meta_data('_subscription_length', '0'); // 0 = never expire

        // No sign-up fee
        $product->update_meta_data('_subscription_sign_up_fee', '0');

        // Trial period (0 days = no trial)
        $product->update_meta_data('_subscription_trial_length', '0');
        $product->update_meta_data('_subscription_trial_period', 'day');

        // Set to virtual and downloadable
        $product->set_virtual(true);
        $product->set_downloadable(false);

        // No stock management for digital products
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');

        // Set plan meta data for identification
        $product->update_meta_data('_acs_plan_slug', $plan_slug);
        $product->update_meta_data('_acs_posts_limit', $plan_data['limits']['posts_per_month']);
        $product->update_meta_data('_acs_articles_limit', $plan_data['limits']['articles_per_month']);
        $product->update_meta_data('_acs_images_limit', $plan_data['limits']['images_per_month']);

        // Save product
        $product_id = $product->save();

        // Store product ID in options
        update_option('acs_product_' . $plan_slug . '_id', $product_id);

        Logger::info('Product ' . $action, [
            'plan' => $plan_slug,
            'product_id' => $product_id,
            'price' => $plan_data['price'],
        ]);

        return [
            'action' => $action,
            'product_id' => $product_id,
            'plan_name' => $plan_data['name'],
            'price' => $plan_data['price'],
        ];
    }

    /**
     * Generate detailed product description
     *
     * @param string $plan_slug
     * @param array $plan_data
     * @return string
     */
    private static function generate_product_description($plan_slug, $plan_data) {
        $limits = $plan_data['limits'];

        $description = '<h3>' . __('Plan', 'ai-content-studio') . ' ' . $plan_data['name'] . '</h3>';
        $description .= '<p>' . $plan_data['description'] . '</p>';

        $description .= '<h4>' . __('Limites mensuelles:', 'ai-content-studio') . '</h4>';
        $description .= '<ul>';

        // Posts
        if ($limits['posts_per_month'] === -1) {
            $description .= '<li><strong>' . __('Posts sociaux:', 'ai-content-studio') . '</strong> ' . __('ILLIMITÉ', 'ai-content-studio') . '</li>';
        } else {
            $description .= '<li><strong>' . __('Posts sociaux:', 'ai-content-studio') . '</strong> ' . $limits['posts_per_month'] . ' ' . __('par mois', 'ai-content-studio') . '</li>';
        }

        // Articles
        if ($limits['articles_per_month'] === -1) {
            $description .= '<li><strong>' . __('Articles de blog:', 'ai-content-studio') . '</strong> ' . __('ILLIMITÉ', 'ai-content-studio') . '</li>';
        } else {
            $description .= '<li><strong>' . __('Articles de blog:', 'ai-content-studio') . '</strong> ' . $limits['articles_per_month'] . ' ' . __('par mois', 'ai-content-studio') . '</li>';
        }

        // Images
        if ($limits['images_per_month'] === -1) {
            $description .= '<li><strong>' . __('Images AI:', 'ai-content-studio') . '</strong> ' . __('ILLIMITÉ', 'ai-content-studio') . '</li>';
        } else {
            $description .= '<li><strong>' . __('Images AI:', 'ai-content-studio') . '</strong> ' . $limits['images_per_month'] . ' ' . __('par mois', 'ai-content-studio') . '</li>';
        }

        $description .= '</ul>';

        $description .= '<h4>' . __('Fonctionnalités:', 'ai-content-studio') . '</h4>';
        $description .= '<ul>';

        if ($limits['languages'] === -1) {
            $description .= '<li>' . __('Toutes les langues disponibles', 'ai-content-studio') . '</li>';
        }

        if ($limits['platforms'] === -1) {
            $description .= '<li>' . __('Toutes les plateformes sociales', 'ai-content-studio') . '</li>';
        }

        if ($limits['scheduling']) {
            $description .= '<li>' . __('Planification de contenu', 'ai-content-studio') . '</li>';
        }

        if ($limits['auto_publish']) {
            $description .= '<li>' . __('Publication automatique', 'ai-content-studio') . '</li>';
        }

        if (isset($limits['analytics']) && $limits['analytics'] !== 'basic') {
            $description .= '<li>' . __('Analytics avancées', 'ai-content-studio') . '</li>';
        }

        if (isset($limits['team_members']) && $limits['team_members'] > 1) {
            $description .= '<li>' . sprintf(__('%d membres d\'équipe', 'ai-content-studio'), $limits['team_members']) . '</li>';
        }

        if (isset($limits['api_access']) && $limits['api_access']) {
            $description .= '<li>' . __('Accès API', 'ai-content-studio') . '</li>';
        }

        $description .= '</ul>';

        return $description;
    }

    /**
     * Delete all subscription products
     *
     * @return array
     */
    public static function delete_all_products() {
        $plans = Plans_Config::get_paid_plans();
        $deleted_count = 0;

        foreach ($plans as $plan_slug => $plan_data) {
            $product_id = get_option('acs_product_' . $plan_slug . '_id');

            if ($product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $product->delete(true); // Force delete
                    $deleted_count++;
                }

                delete_option('acs_product_' . $plan_slug . '_id');
            }
        }

        Logger::info('Products deleted', ['count' => $deleted_count]);

        return [
            'success' => true,
            'message' => sprintf(__('%d produits supprimés', 'ai-content-studio'), $deleted_count),
        ];
    }

    /**
     * Get all generated product IDs
     *
     * @return array
     */
    public static function get_all_product_ids() {
        $plans = Plans_Config::get_paid_plans();
        $product_ids = [];

        foreach ($plans as $plan_slug => $plan_data) {
            $product_id = get_option('acs_product_' . $plan_slug . '_id');
            if ($product_id) {
                $product_ids[$plan_slug] = $product_id;
            }
        }

        return $product_ids;
    }

    /**
     * Check if products are already created
     *
     * @return bool
     */
    public static function products_exist() {
        $product_ids = self::get_all_product_ids();
        return !empty($product_ids);
    }
}
