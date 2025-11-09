<?php
/**
 * Shortcode Handler
 *
 * @package ACS
 */

namespace ACS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode class
 */
class Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('ai_content_studio', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_scripts']);

        // Auto-inject shortcode on configured page
        add_filter('the_content', [$this, 'maybe_inject_shortcode']);
    }

    /**
     * Render shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_shortcode($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return $this->render_login_message();
        }

        // Parse attributes
        $atts = shortcode_atts([
            'view' => 'dashboard', // dashboard, generator, blog, etc.
            'height' => '800px',
        ], $atts, 'ai_content_studio');

        // Enqueue scripts
        $this->enqueue_app_scripts();

        // Render container
        ob_start();
        ?>
        <style>
            /* Hide WordPress page elements when using AI Content Studio */
            .page .entry-header,
            .page .entry-title,
            .page-header,
            .site-breadcrumbs,
            body.page article .entry-meta,
            body.page article footer.entry-footer {
                display: none !important;
            }

            /* Ensure full-width layout */
            .page .entry-content {
                margin: 0 !important;
                padding: 0 !important;
                max-width: none !important;
            }

            .page article,
            .page .site-main {
                margin: 0 !important;
                padding: 0 !important;
            }
        </style>
        <div class="acs-frontend-wrapper" style="min-height: <?php echo esc_attr($atts['height']); ?>;">
            <div id="acs-frontend-root" data-view="<?php echo esc_attr($atts['view']); ?>"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render login message for non-logged users
     *
     * @return string
     */
    private function render_login_message() {
        ob_start();
        ?>
        <div class="acs-login-required">
            <div class="acs-notice">
                <h3><?php _e('Connexion Requise', 'ai-content-studio'); ?></h3>
                <p><?php _e('Vous devez être connecté pour accéder à AI Content Studio.', 'ai-content-studio'); ?></p>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="button button-primary">
                    <?php _e('Se connecter', 'ai-content-studio'); ?>
                </a>
                <?php if (get_option('users_can_register')) : ?>
                    <a href="<?php echo esc_url(wp_registration_url()); ?>" class="button">
                        <?php _e('Créer un compte', 'ai-content-studio'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <style>
            .acs-login-required {
                padding: 40px 20px;
                text-align: center;
            }
            .acs-notice {
                max-width: 500px;
                margin: 0 auto;
                padding: 30px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 8px;
            }
            .acs-notice h3 {
                margin-top: 0;
                color: #333;
            }
            .acs-notice .button {
                margin: 5px;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue app scripts and styles
     *
     * @return void
     */
    private function enqueue_app_scripts() {
        $asset_file = ACS_PLUGIN_DIR . 'admin/js/admin-script.asset.php';

        if (file_exists($asset_file)) {
            $asset = require $asset_file;

            // Enqueue React app
            wp_enqueue_script(
                'acs-frontend-app',
                ACS_PLUGIN_URL . 'admin/js/admin-script.js',
                $asset['dependencies'],
                $asset['version'],
                true
            );

            wp_enqueue_style(
                'acs-frontend-style',
                ACS_PLUGIN_URL . 'admin/css/admin-style.css',
                [],
                $asset['version']
            );

            // Localize script with frontend-specific data
            wp_localize_script('acs-frontend-app', 'acsData', [
                'apiUrl' => rest_url('acs/v1'),
                'nonce' => wp_create_nonce('wp_rest'),
                'currentUser' => get_current_user_id(),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'pluginUrl' => ACS_PLUGIN_URL,
                'isFrontend' => true,
                'homeUrl' => home_url(),
            ]);

            // Add inline script to mount React on frontend root
            wp_add_inline_script('acs-frontend-app', '
                document.addEventListener("DOMContentLoaded", function() {
                    const frontendRoot = document.getElementById("acs-frontend-root");
                    if (frontendRoot && window.acsData.isFrontend) {
                        // React app will automatically mount to #acs-frontend-root
                        // The view attribute can be read from data-view
                    }
                });
            ');
        }
    }

    /**
     * Maybe enqueue scripts on pages with shortcode
     *
     * @return void
     */
    public function maybe_enqueue_scripts() {
        global $post;

        // Check if current post has the shortcode
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'ai_content_studio')) {
            $this->enqueue_app_scripts();
        }
    }

    /**
     * Check if current page has shortcode
     *
     * @return bool
     */
    public static function has_shortcode_in_page() {
        global $post;
        return is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'ai_content_studio');
    }

    /**
     * Maybe inject shortcode automatically on configured page
     *
     * @param string $content Post content
     * @return string Modified content
     */
    public function maybe_inject_shortcode($content) {
        // Only on pages
        if (!is_page()) {
            return $content;
        }

        global $post;

        // Check if this is the configured default page
        $shortcode_page_id = get_option('acs_shortcode_page', 0);

        // If no page configured, return original content
        if (empty($shortcode_page_id) || $shortcode_page_id == 0) {
            return $content;
        }

        // If this is the configured page and doesn't already have the shortcode
        if ($post->ID == $shortcode_page_id && !has_shortcode($content, 'ai_content_studio')) {
            // Replace content with shortcode (or append if you prefer)
            // Using replace to make it truly "automatic"
            return do_shortcode('[ai_content_studio]');
        }

        return $content;
    }
}
