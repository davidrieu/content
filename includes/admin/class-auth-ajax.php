<?php
/**
 * Authentication AJAX Handler
 *
 * @package ACS
 */

namespace ACS\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Auth AJAX class
 */
class Auth_Ajax {

    /**
     * Constructor
     */
    public function __construct() {
        // Login/Register actions (for non-logged users)
        add_action('wp_ajax_nopriv_acs_login', [$this, 'handle_login']);
        add_action('wp_ajax_nopriv_acs_register', [$this, 'handle_register']);
    }

    /**
     * Handle login AJAX request
     *
     * @return void
     */
    public function handle_login() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_rest')) {
            wp_send_json_error(['message' => __('Erreur de sécurité', 'ai-content-studio')], 403);
        }

        $username = sanitize_text_field($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            wp_send_json_error(['message' => __('Veuillez remplir tous les champs', 'ai-content-studio')], 400);
        }

        // Try to authenticate
        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            wp_send_json_error([
                'message' => __('Identifiants incorrects', 'ai-content-studio')
            ], 401);
        }

        // Log the user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        wp_send_json_success([
            'message' => __('Connexion réussie', 'ai-content-studio'),
            'user_id' => $user->ID,
        ]);
    }

    /**
     * Handle register AJAX request
     *
     * @return void
     */
    public function handle_register() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_rest')) {
            wp_send_json_error(['message' => __('Erreur de sécurité', 'ai-content-studio')], 403);
        }

        // Check if registration is enabled
        if (!get_option('users_can_register')) {
            wp_send_json_error([
                'message' => __('Les inscriptions sont désactivées', 'ai-content-studio')
            ], 403);
        }

        $username = sanitize_user($_POST['username'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(['message' => __('Veuillez remplir tous les champs', 'ai-content-studio')], 400);
        }

        // Validate email
        if (!is_email($email)) {
            wp_send_json_error(['message' => __('Adresse email invalide', 'ai-content-studio')], 400);
        }

        // Check if username exists
        if (username_exists($username)) {
            wp_send_json_error(['message' => __('Ce nom d\'utilisateur existe déjà', 'ai-content-studio')], 400);
        }

        // Check if email exists
        if (email_exists($email)) {
            wp_send_json_error(['message' => __('Cette adresse email est déjà utilisée', 'ai-content-studio')], 400);
        }

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error([
                'message' => $user_id->get_error_message()
            ], 500);
        }

        // If WooCommerce is active, set the user as a customer
        if (class_exists('WooCommerce')) {
            update_user_meta($user_id, 'billing_email', $email);
            update_user_meta($user_id, 'first_name', $username);

            // Add customer role
            $user = new \WP_User($user_id);
            $user->set_role('customer');
        }

        // Log the user in
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        // Send welcome email
        wp_new_user_notification($user_id, null, 'user');

        wp_send_json_success([
            'message' => __('Compte créé avec succès', 'ai-content-studio'),
            'user_id' => $user_id,
        ]);
    }
}
