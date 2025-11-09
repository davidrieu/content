<?php
/**
 * Plugin Name: AI Content Studio
 * Plugin URI: https://aicontentstudio.com
 * Description: Plateforme SaaS complète pour générer du contenu réseaux sociaux et articles de blog avec IA. Inclut détection de tendances, générateur d'images, calendrier éditorial, et bien plus.
 * Version: 1.2.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: AI Content Studio Team
 * Author URI: https://aicontentstudio.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-content-studio
 * Domain Path: /languages
 *
 * WC requires at least: 8.0
 * WC tested up to: 8.9
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// =====================================================
// CONSTANTS
// =====================================================
define('ACS_VERSION', '1.2.0');
define('ACS_PLUGIN_FILE', __FILE__);
define('ACS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ACS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ACS_MIN_PHP_VERSION', '8.1');
define('ACS_MIN_WP_VERSION', '6.4');
define('ACS_TABLE_PREFIX', 'acs_');

// =====================================================
// PSR-4 AUTOLOADER
// =====================================================
spl_autoload_register(function ($class) {
    $prefix = 'ACS\\';
    $base_dir = ACS_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);

    // Convert namespace separators to directory separators
    // Convert PascalCase to kebab-case for file names
    $file_parts = explode('\\', $relative_class);
    $file_name = array_pop($file_parts);

    // Convert class name: My_Class -> class-my-class.php
    $file_name = 'class-' . strtolower(str_replace('_', '-', $file_name)) . '.php';

    $directory = $base_dir . strtolower(implode('/', $file_parts));
    if (!empty($file_parts)) {
        $directory .= '/';
    }
    $file = $directory . $file_name;

    if (file_exists($file)) {
        require $file;
    }
});

// =====================================================
// DEPENDENCIES CHECK ON ACTIVATION
// =====================================================
register_activation_hook(__FILE__, function() {
    // Check PHP version
    if (version_compare(PHP_VERSION, ACS_MIN_PHP_VERSION, '<')) {
        deactivate_plugins(ACS_PLUGIN_BASENAME);
        wp_die(sprintf(
            /* translators: 1: Required PHP version, 2: Current PHP version */
            __('AI Content Studio requiert PHP %1$s ou supérieur. Vous utilisez PHP %2$s.', 'ai-content-studio'),
            ACS_MIN_PHP_VERSION,
            PHP_VERSION
        ), 'Plugin Activation Error', ['back_link' => true]);
    }

    // Check WordPress version
    if (version_compare($GLOBALS['wp_version'], ACS_MIN_WP_VERSION, '<')) {
        deactivate_plugins(ACS_PLUGIN_BASENAME);
        wp_die(sprintf(
            /* translators: 1: Required WordPress version, 2: Current WordPress version */
            __('AI Content Studio requiert WordPress %1$s ou supérieur. Vous utilisez WordPress %2$s.', 'ai-content-studio'),
            ACS_MIN_WP_VERSION,
            $GLOBALS['wp_version']
        ), 'Plugin Activation Error', ['back_link' => true]);
    }

    // Run activation
    if (class_exists('ACS\\Activator')) {
        ACS\Activator::activate();
    }
});

// =====================================================
// CHECK WOOCOMMERCE & WOOCOMMERCE SUBSCRIPTIONS
// =====================================================
add_action('admin_init', function() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>';
            echo '<strong>' . esc_html__('AI Content Studio', 'ai-content-studio') . '</strong> ';
            echo esc_html__('requiert WooCommerce pour fonctionner. Veuillez installer et activer WooCommerce.', 'ai-content-studio');
            echo '</p></div>';
        });
        deactivate_plugins(ACS_PLUGIN_BASENAME);
        return;
    }

    // Check if WooCommerce Subscriptions is active
    if (!class_exists('WC_Subscriptions')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>' . esc_html__('AI Content Studio', 'ai-content-studio') . '</strong> ';
            echo esc_html__('recommande WooCommerce Subscriptions pour gérer les abonnements. Certaines fonctionnalités pourraient être limitées.', 'ai-content-studio');
            echo '</p></div>';
        });
    }
});

// =====================================================
// WOOCOMMERCE HPOS COMPATIBILITY
// =====================================================
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// =====================================================
// DEACTIVATION HOOK
// =====================================================
register_deactivation_hook(__FILE__, function() {
    if (class_exists('ACS\\Activator')) {
        ACS\Activator::deactivate();
    }
});

// =====================================================
// UNINSTALL HOOK
// =====================================================
register_uninstall_hook(__FILE__, ['ACS\\Activator', 'uninstall']);

// =====================================================
// PLUGIN INITIALIZATION
// =====================================================
add_action('plugins_loaded', function() {
    // Load text domain
    load_plugin_textdomain(
        'ai-content-studio',
        false,
        dirname(ACS_PLUGIN_BASENAME) . '/languages'
    );

    // Load plans configuration
    if (file_exists(ACS_PLUGIN_DIR . 'includes/config/class-plans-config.php')) {
        require_once ACS_PLUGIN_DIR . 'includes/config/class-plans-config.php';
    }

    // Initialize plugin
    if (class_exists('ACS\\Plugin')) {
        $plugin = ACS\Plugin::get_instance();
        $plugin->run();
    }

}, 20); // Priority 20 to load after WooCommerce (10) and WC Subscriptions (11)

// =====================================================
// PLUGIN LINKS
// =====================================================
add_filter('plugin_action_links_' . ACS_PLUGIN_BASENAME, function($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=ai-content-studio-settings'),
        __('Réglages', 'ai-content-studio')
    );
    array_unshift($links, $settings_link);
    return $links;
});

add_filter('plugin_row_meta', function($links, $file) {
    if ($file === ACS_PLUGIN_BASENAME) {
        $row_meta = [
            'docs' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://aicontentstudio.com/docs',
                __('Documentation', 'ai-content-studio')
            ),
            'support' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://aicontentstudio.com/support',
                __('Support', 'ai-content-studio')
            ),
        ];
        return array_merge($links, $row_meta);
    }
    return $links;
}, 10, 2);
