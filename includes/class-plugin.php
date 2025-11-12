<?php
/**
 * Main Plugin Class
 *
 * @package ACS
 */

namespace ACS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin class
 */
class Plugin {

    /**
     * Plugin instance
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Admin instance
     *
     * @var Admin\Admin
     */
    private $admin;

    /**
     * Get plugin instance
     *
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Private constructor to prevent direct instantiation
    }

    /**
     * Run the plugin
     *
     * @return void
     */
    public function run() {
        // Check if update is needed
        if (Activator::needs_update()) {
            Activator::update();
        }

        // Initialize hooks
        $this->init_hooks();

        // Load dependencies
        $this->load_dependencies();

        // Always run upgrade_tables to catch any missing columns
        // This is safe as it checks for column existence before altering
        if (class_exists('ACS\\Database')) {
            Database::upgrade_tables();
        }

        // Initialize components
        $this->init_admin();
        $this->init_api();
        $this->init_services();
        $this->init_woocommerce();
        $this->init_cron();
        $this->init_shortcode();
    }

    /**
     * Initialize hooks
     *
     * @return void
     */
    private function init_hooks() {
        // User registration hook - assign free plan
        add_action('user_register', [$this, 'on_user_register']);

        // Add custom cron schedules
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);

        // Enqueue scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // Add menu page
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    /**
     * Load dependencies
     *
     * @return void
     */
    private function load_dependencies() {
        // Utility classes
        $utils_dir = ACS_PLUGIN_DIR . 'includes/utils/';
        if (file_exists($utils_dir . 'class-logger.php')) {
            require_once $utils_dir . 'class-logger.php';
        }
        if (file_exists($utils_dir . 'class-sanitizer.php')) {
            require_once $utils_dir . 'class-sanitizer.php';
        }
        if (file_exists($utils_dir . 'class-validator.php')) {
            require_once $utils_dir . 'class-validator.php';
        }
        if (file_exists($utils_dir . 'class-helpers.php')) {
            require_once $utils_dir . 'class-helpers.php';
        }
    }

    /**
     * Initialize admin
     *
     * @return void
     */
    private function init_admin() {
        if (is_admin()) {
            // Load settings page
            $settings_file = ACS_PLUGIN_DIR . 'includes/admin/class-settings.php';
            if (file_exists($settings_file)) {
                require_once $settings_file;
                new Admin\Settings();
            }

            // Load subscriptions admin page
            $subscriptions_file = ACS_PLUGIN_DIR . 'includes/admin/class-subscriptions-admin.php';
            if (file_exists($subscriptions_file)) {
                require_once $subscriptions_file;
                new Admin\Subscriptions_Admin();
            }
        }

        // Load auth AJAX handler (works for both admin and frontend)
        $auth_ajax_file = ACS_PLUGIN_DIR . 'includes/admin/class-auth-ajax.php';
        if (file_exists($auth_ajax_file)) {
            require_once $auth_ajax_file;
            new Admin\Auth_Ajax();
        }
    }

    /**
     * Initialize REST API
     *
     * @return void
     */
    private function init_api() {
        add_action('rest_api_init', function() {
            $api_dir = ACS_PLUGIN_DIR . 'includes/api/';

            // List of API endpoint files
            $endpoints = [
                'class-rest-controller.php',
                'class-auth-endpoint.php',
                'class-profile-endpoint.php',
                'class-strategy-endpoint.php',
                'class-posts-endpoint.php',
                'class-blog-endpoint.php',
                'class-images-endpoint.php',
                'class-trends-endpoint.php',
                'class-hashtags-endpoint.php',
                'class-templates-endpoint.php',
                'class-calendar-endpoint.php',
                'class-analytics-endpoint.php',
                'class-social-connections-endpoint.php',
                'class-subscription-endpoint.php',
                'class-library-endpoint.php',
                'class-logs-endpoint.php',
                'class-diagnostic-endpoint.php',
            ];

            foreach ($endpoints as $endpoint) {
                $file = $api_dir . $endpoint;
                if (file_exists($file)) {
                    require_once $file;
                }
            }

            // Register endpoints
            if (class_exists('ACS\\API\\Profile_Endpoint')) {
                $profile = new API\Profile_Endpoint();
                $profile->register_routes();
            }

            if (class_exists('ACS\\API\\Strategy_Endpoint')) {
                $strategy = new API\Strategy_Endpoint();
                $strategy->register_routes();
            }

            if (class_exists('ACS\\API\\Posts_Endpoint')) {
                $posts = new API\Posts_Endpoint();
                $posts->register_routes();
            }

            if (class_exists('ACS\\API\\Blog_Endpoint')) {
                $blog = new API\Blog_Endpoint();
                $blog->register_routes();
            }

            if (class_exists('ACS\\API\\Subscription_Endpoint')) {
                $subscription = new API\Subscription_Endpoint();
                $subscription->register_routes();
            }

            if (class_exists('ACS\\API\\Calendar_Endpoint')) {
                $calendar = new API\Calendar_Endpoint();
                $calendar->register_routes();
            }

            if (class_exists('ACS\\API\\Library_Endpoint')) {
                $library = new API\Library_Endpoint();
                $library->register_routes();
            }

            if (class_exists('ACS\\API\\Logs_Endpoint')) {
                $logs = new API\Logs_Endpoint();
                $logs->register_routes();
            }

            if (class_exists('ACS\\API\\Diagnostic_Endpoint')) {
                $diagnostic = new API\Diagnostic_Endpoint();
                $diagnostic->register_routes();
            }

            // More endpoints will be registered as they are created
        });
    }

    /**
     * Initialize services
     *
     * @return void
     */
    private function init_services() {
        // Services are loaded on-demand via autoloader
        // No need to instantiate them here
    }

    /**
     * Initialize WooCommerce integration
     *
     * @return void
     */
    private function init_woocommerce() {
        if (class_exists('WooCommerce')) {
            $wc_dir = ACS_PLUGIN_DIR . 'includes/woocommerce/';

            // Load product generator
            if (file_exists($wc_dir . 'class-product-generator.php')) {
                require_once $wc_dir . 'class-product-generator.php';
            }

            // Load subscription handler
            if (file_exists($wc_dir . 'class-subscription-handler.php')) {
                require_once $wc_dir . 'class-subscription-handler.php';
                new WooCommerce\Subscription_Handler();
            }
        }
    }

    /**
     * Initialize cron jobs
     *
     * @return void
     */
    private function init_cron() {
        // Hook cron actions
        add_action('acs_reset_monthly_usage', function() {
            if (class_exists('ACS\\Services\\Usage_Service')) {
                Services\Usage_Service::reset_all_monthly_usage();
            }
        });

        add_action('acs_fetch_trends', function() {
            if (class_exists('ACS\\Services\\Trends_Service')) {
                $service = new Services\Trends_Service();
                $service->fetch_and_save_trends();
            }
        });

        add_action('acs_cleanup_trends', function() {
            if (class_exists('ACS\\Services\\Trends_Service')) {
                $service = new Services\Trends_Service();
                $service->cleanup_expired_trends();
            }
        });

        add_action('acs_refresh_tokens', function() {
            if (class_exists('ACS\\Integrations\\OAuth_Handler')) {
                Integrations\OAuth_Handler::refresh_expiring_tokens();
            }
        });

        add_action('acs_publish_scheduled_posts', function() {
            if (class_exists('ACS\\Services\\Social_Publisher')) {
                $service = new Services\Social_Publisher();
                $service->publish_scheduled_posts();
            }
        });
    }

    /**
     * Initialize shortcode
     *
     * @return void
     */
    private function init_shortcode() {
        if (file_exists(ACS_PLUGIN_DIR . 'includes/class-shortcode.php')) {
            require_once ACS_PLUGIN_DIR . 'includes/class-shortcode.php';
            new Shortcode();
        }
    }

    /**
     * Add custom cron schedules
     *
     * @param array $schedules
     * @return array
     */
    public function add_cron_schedules($schedules) {
        $schedules['fourhourly'] = [
            'interval' => 4 * HOUR_IN_SECONDS,
            'display' => __('Toutes les 4 heures', 'ai-content-studio'),
        ];

        $schedules['fifteen_minutes'] = [
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => __('Toutes les 15 minutes', 'ai-content-studio'),
        ];

        $schedules['monthly'] = [
            'interval' => 30 * DAY_IN_SECONDS,
            'display' => __('Mensuellement', 'ai-content-studio'),
        ];

        return $schedules;
    }

    /**
     * On user registration - assign free plan
     *
     * @param int $user_id
     * @return void
     */
    public function on_user_register($user_id) {
        // Assign free_trial plan by default
        update_user_meta($user_id, 'acs_subscription_plan', 'free_trial');
        update_user_meta($user_id, 'acs_subscription_status', 'active');
        update_user_meta($user_id, 'acs_onboarding_completed', false);
        update_user_meta($user_id, 'acs_mode', 'simple');

        // Create usage stats entry
        global $wpdb;
        $table = Database::get_table_name('usage_stats');

        $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'posts_generated' => 0,
                'posts_this_month' => 0,
                'articles_generated' => 0,
                'articles_this_month' => 0,
                'images_generated' => 0,
                'images_this_month' => 0,
                'last_reset_date' => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s']
        );
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook
     * @return void
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'ai-content-studio') === false) {
            return;
        }

        // FORCE: Désactiver TOUS les caches WordPress
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        if (!defined('DONOTCACHEDB')) {
            define('DONOTCACHEDB', true);
        }
        if (!defined('DONOTMINIFY')) {
            define('DONOTMINIFY', true);
        }
        if (!defined('DONOTCDN')) {
            define('DONOTCDN', true);
        }
        if (!defined('DONOTCACHEOBJECT')) {
            define('DONOTCACHEOBJECT', true);
        }

        // Nettoyer le cache WordPress
        wp_cache_flush();

        // Use direct file timestamp - no caching possible
        $js_file = ACS_PLUGIN_DIR . 'admin/js/admin-script.js';
        $css_file = ACS_PLUGIN_DIR . 'admin/css/admin-style.css';
        $asset_file = ACS_PLUGIN_DIR . 'admin/js/admin-script.asset.php';

        // Clear all possible PHP caches for asset file
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($asset_file, true);
            opcache_invalidate($js_file, true);
            opcache_invalidate($css_file, true);
        }
        clearstatcache(true, $asset_file);
        clearstatcache(true, $js_file);
        clearstatcache(true, $css_file);

        if (file_exists($asset_file)) {
            $asset = require $asset_file;

            // Use timestamp + random + microtime to absolutely force reload EVERY TIME
            $cache_buster = filemtime($js_file) . '.' . time() . '.' . wp_rand(1000, 9999);

            // Désactiver complètement la mise en cache de ces ressources
            wp_enqueue_script(
                'acs-admin-script',
                ACS_PLUGIN_URL . 'admin/js/admin-script.js',
                $asset['dependencies'],
                $cache_buster,  // Force unique version every single page load
                true
            );

            // Ajouter les headers no-cache
            add_filter('script_loader_tag', function($tag, $handle) {
                if ($handle === 'acs-admin-script') {
                    return str_replace('<script ', '<script data-no-cache="true" ', $tag);
                }
                return $tag;
            }, 10, 2);

            wp_enqueue_style(
                'acs-admin-style',
                ACS_PLUGIN_URL . 'admin/css/admin-style.css',
                [],
                filemtime($css_file) . '.' . time() . '.' . wp_rand(1000, 9999)
            );

            // Localize script
            wp_localize_script('acs-admin-script', 'acsData', [
                'apiUrl' => rest_url('acs/v1'),
                'nonce' => wp_create_nonce('wp_rest'),
                'currentUser' => get_current_user_id(),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'pluginUrl' => ACS_PLUGIN_URL,
                'cacheDebug' => [
                    'jsFile' => filemtime($js_file),
                    'currentTime' => time(),
                    'version' => $cache_buster,
                ],
            ]);
        }
    }

    /**
     * Add admin menu
     *
     * @return void
     */
    public function add_admin_menu() {
        add_menu_page(
            __('AI Content Studio', 'ai-content-studio'),
            __('AI Content Studio', 'ai-content-studio'),
            'read',
            'ai-content-studio',
            [$this, 'render_admin_page'],
            'dashicons-edit-large',
            30
        );

        add_submenu_page(
            'ai-content-studio',
            __('Tableau de bord', 'ai-content-studio'),
            __('Tableau de bord', 'ai-content-studio'),
            'read',
            'ai-content-studio',
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'ai-content-studio',
            __('Logs Système', 'ai-content-studio'),
            __('Logs Système', 'ai-content-studio'),
            'manage_options',
            'ai-content-studio-logs',
            [$this, 'render_logs_page']
        );

        add_submenu_page(
            'ai-content-studio',
            __('Diagnostic Système', 'ai-content-studio'),
            __('Diagnostic Système', 'ai-content-studio'),
            'manage_options',
            'ai-content-studio-diagnostic',
            [$this, 'render_diagnostic_page']
        );

        add_submenu_page(
            'ai-content-studio',
            __('Réglages', 'ai-content-studio'),
            __('Réglages', 'ai-content-studio'),
            'manage_options',
            'ai-content-studio-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Render admin page
     *
     * @return void
     */
    public function render_admin_page() {
        echo '<div class="wrap">';
        echo '<div id="acs-admin-root"></div>'; // React app mounts here
        echo '</div>';
    }

    /**
     * Render settings page
     *
     * @return void
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.', 'ai-content-studio'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Réglages AI Content Studio', 'ai-content-studio') . '</h1>';
        echo '<div id="acs-settings-root"></div>'; // Settings UI will be here
        echo '</div>';
    }

    /**
     * Render logs page
     *
     * @return void
     */
    public function render_logs_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.', 'ai-content-studio'));
        }

        require_once ACS_PLUGIN_DIR . 'includes/admin/views/logs-page.php';
    }

    /**
     * Render diagnostic page
     *
     * @return void
     */
    public function render_diagnostic_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.', 'ai-content-studio'));
        }

        require_once ACS_PLUGIN_DIR . 'includes/admin/views/diagnostic-page.php';
    }
}
