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
        // Verify nonce (use authNonce for non-logged users)
        $nonce = $_POST['authNonce'] ?? $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'acs_auth_action')) {
            \ACS\Utils\Logger::error('Login failed: Invalid nonce', ['nonce_provided' => !empty($nonce)]);
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
        \ACS\Utils\Logger::info('=== Register attempt started ===');

        // Verify nonce (use authNonce for non-logged users)
        $nonce = $_POST['authNonce'] ?? $_POST['nonce'] ?? '';
        \ACS\Utils\Logger::info('Nonce check', ['nonce_provided' => !empty($nonce)]);

        if (!wp_verify_nonce($nonce, 'acs_auth_action')) {
            \ACS\Utils\Logger::error('Register failed: Invalid nonce', [
                'nonce_provided' => !empty($nonce),
                'nonce_value' => $nonce,
            ]);
            wp_send_json_error(['message' => __('Erreur de sécurité', 'ai-content-studio')], 403);
        }

        // Check if registration is enabled
        if (!get_option('users_can_register')) {
            \ACS\Utils\Logger::warning('Register failed: Registration disabled');
            wp_send_json_error([
                'message' => __('Les inscriptions sont désactivées', 'ai-content-studio')
            ], 403);
        }

        $username = sanitize_user($_POST['username'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $language = sanitize_text_field($_POST['language'] ?? 'fr');

        \ACS\Utils\Logger::info('Register data received', [
            'username' => $username,
            'email' => $email,
            'password_length' => strlen($password),
            'language' => $language,
        ]);

        if (empty($username) || empty($email) || empty($password)) {
            \ACS\Utils\Logger::error('Register failed: Missing fields', [
                'has_username' => !empty($username),
                'has_email' => !empty($email),
                'has_password' => !empty($password),
            ]);
            wp_send_json_error(['message' => __('Veuillez remplir tous les champs', 'ai-content-studio')], 400);
        }

        // Validate email
        if (!is_email($email)) {
            \ACS\Utils\Logger::error('Register failed: Invalid email', ['email' => $email]);
            wp_send_json_error(['message' => __('Adresse email invalide', 'ai-content-studio')], 400);
        }

        // Check if username exists
        if (username_exists($username)) {
            \ACS\Utils\Logger::warning('Register failed: Username exists', ['username' => $username]);
            wp_send_json_error(['message' => __('Ce nom d\'utilisateur existe déjà', 'ai-content-studio')], 400);
        }

        // Check if email exists
        if (email_exists($email)) {
            \ACS\Utils\Logger::warning('Register failed: Email exists', ['email' => $email]);
            wp_send_json_error(['message' => __('Cette adresse email est déjà utilisée', 'ai-content-studio')], 400);
        }

        \ACS\Utils\Logger::info('Validation passed, creating user...');

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            \ACS\Utils\Logger::error('Register failed: wp_create_user error', [
                'error_code' => $user_id->get_error_code(),
                'error_message' => $user_id->get_error_message(),
            ]);
            wp_send_json_error([
                'message' => $user_id->get_error_message()
            ], 500);
        }

        \ACS\Utils\Logger::info('User created successfully', ['user_id' => $user_id]);

        // Save user language preference
        update_user_meta($user_id, 'acs_preferred_language', $language);
        \ACS\Utils\Logger::info('User language preference saved', ['language' => $language]);

        // If WooCommerce is active, set the user as a customer
        if (class_exists('WooCommerce')) {
            \ACS\Utils\Logger::info('Setting up WooCommerce customer data');
            update_user_meta($user_id, 'billing_email', $email);
            update_user_meta($user_id, 'first_name', $username);

            // Add customer role
            $user = new \WP_User($user_id);
            $user->set_role('customer');
            \ACS\Utils\Logger::info('WooCommerce customer role set');
        }

        // Log the user in
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        \ACS\Utils\Logger::info('User logged in with auth cookie');

        // Send welcome email
        wp_new_user_notification($user_id, null, 'user');
        \ACS\Utils\Logger::info('Welcome email sent');

        \ACS\Utils\Logger::info('=== Register completed successfully ===', ['user_id' => $user_id]);

        wp_send_json_success([
            'message' => __('Compte créé avec succès', 'ai-content-studio'),
            'user_id' => $user_id,
        ]);
    }
}
