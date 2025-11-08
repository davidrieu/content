<?php
/**
 * Validator Utility
 *
 * @package ACS\Utils
 */

namespace ACS\Utils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validator class
 */
class Validator {

    /**
     * Validation errors
     *
     * @var array
     */
    private static $errors = [];

    /**
     * Validate required field
     *
     * @param mixed $value
     * @param string $field_name
     * @return bool
     */
    public static function required($value, $field_name = 'field') {
        if (empty($value) && $value !== '0' && $value !== 0) {
            self::add_error($field_name, sprintf(
                __('Le champ %s est requis.', 'ai-content-studio'),
                $field_name
            ));
            return false;
        }
        return true;
    }

    /**
     * Validate email
     *
     * @param string $value
     * @param string $field_name
     * @return bool
     */
    public static function email($value, $field_name = 'email') {
        if (!is_email($value)) {
            self::add_error($field_name, sprintf(
                __('%s n\'est pas une adresse email valide.', 'ai-content-studio'),
                $field_name
            ));
            return false;
        }
        return true;
    }

    /**
     * Validate URL
     *
     * @param string $value
     * @param string $field_name
     * @return bool
     */
    public static function url($value, $field_name = 'URL') {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            self::add_error($field_name, sprintf(
                __('%s n\'est pas une URL valide.', 'ai-content-studio'),
                $field_name
            ));
            return false;
        }
        return true;
    }

    /**
     * Validate integer
     *
     * @param mixed $value
     * @param string $field_name
     * @return bool
     */
    public static function integer($value, $field_name = 'field') {
        if (!is_numeric($value) || intval($value) != $value) {
            self::add_error($field_name, sprintf(
                __('%s doit être un nombre entier.', 'ai-content-studio'),
                $field_name
            ));
            return false;
        }
        return true;
    }

    /**
     * Validate minimum value
     *
     * @param mixed $value
     * @param int $min
     * @param string $field_name
     * @return bool
     */
    public static function min($value, $min, $field_name = 'field') {
        if (is_numeric($value) && $value < $min) {
            self::add_error($field_name, sprintf(
                __('%s doit être supérieur ou égal à %d.', 'ai-content-studio'),
                $field_name,
                $min
            ));
            return false;
        }
        return true;
    }

    /**
     * Validate maximum value
     *
     * @param mixed $value
     * @param int $max
     * @param string $field_name
     * @return bool
     */
    public static function max($value, $max, $field_name = 'field') {
        if (is_numeric($value) && $value > $max) {
            self::add_error($field_name, sprintf(
                __('%s doit être inférieur ou égal à %d.', 'ai-content-studio'),
                $field_name,
                $max
            ));
            return false;
        }
        return true;
    }

    /**
     * Validate minimum length
     *
     * @param string $value
     * @param int $min_length
     * @param string $field_name
     * @return bool
     */
    public static function min_length($value, $min_length, $field_name = 'field') {
        if (strlen($value) < $min_length) {
            self::add_error($field_name, sprintf(
                __('%s doit contenir au moins %d caractères.', 'ai-content-studio'),
                $field_name,
                $min_length
            ));
            return false;
        }
        return true;
    }

    /**
     * Validate maximum length
     *
     * @param string $value
     * @param int $max_length
     * @param string $field_name
     * @return bool
     */
    public static function max_length($value, $max_length, $field_name = 'field') {
        if (strlen($value) > $max_length) {
            self::add_error($field_name, sprintf(
                __('%s ne peut pas dépasser %d caractères.', 'ai-content-studio'),
                $field_name,
                $max_length
            ));
            return false;
        }
        return true;
    }

    /**
     * Validate JSON
     *
     * @param string $value
     * @param string $field_name
     * @return bool
     */
    public static function json($value, $field_name = 'field') {
        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::add_error($field_name, sprintf(
                __('%s n\'est pas un JSON valide.', 'ai-content-studio'),
                $field_name
            ));
            return false;
        }
        return true;
    }

    /**
     * Validate hex color
     *
     * @param string $value
     * @param string $field_name
     * @return bool
     */
    public static function hex_color($value, $field_name = 'color') {
        $value = ltrim($value, '#');
        if (!preg_match('/^[A-Fa-f0-9]{6}$/', $value)) {
            self::add_error($field_name, sprintf(
                __('%s n\'est pas une couleur hexadécimale valide.', 'ai-content-studio'),
                $field_name
            ));
            return false;
        }
        return true;
    }

    /**
     * Validate in array
     *
     * @param mixed $value
     * @param array $allowed_values
     * @param string $field_name
     * @return bool
     */
    public static function in_array($value, $allowed_values, $field_name = 'field') {
        if (!in_array($value, $allowed_values, true)) {
            self::add_error($field_name, sprintf(
                __('%s contient une valeur non autorisée.', 'ai-content-studio'),
                $field_name
            ));
            return false;
        }
        return true;
    }

    /**
     * Validate platform
     *
     * @param string $value
     * @param string $field_name
     * @return bool
     */
    public static function platform($value, $field_name = 'platform') {
        $allowed = ['instagram', 'facebook', 'linkedin', 'twitter', 'tiktok', 'youtube'];
        return self::in_array(strtolower($value), $allowed, $field_name);
    }

    /**
     * Validate language code
     *
     * @param string $value
     * @param string $field_name
     * @return bool
     */
    public static function language($value, $field_name = 'language') {
        if (!preg_match('/^[a-z]{2}$/', $value)) {
            self::add_error($field_name, sprintf(
                __('%s n\'est pas un code langue valide.', 'ai-content-studio'),
                $field_name
            ));
            return false;
        }
        return true;
    }

    /**
     * Validate date
     *
     * @param string $value
     * @param string $format
     * @param string $field_name
     * @return bool
     */
    public static function date($value, $format = 'Y-m-d', $field_name = 'date') {
        $d = \DateTime::createFromFormat($format, $value);
        if (!$d || $d->format($format) !== $value) {
            self::add_error($field_name, sprintf(
                __('%s n\'est pas une date valide.', 'ai-content-studio'),
                $field_name
            ));
            return false;
        }
        return true;
    }

    /**
     * Validate API key format
     *
     * @param string $value
     * @param string $field_name
     * @return bool
     */
    public static function api_key($value, $field_name = 'API key') {
        // Basic validation - at least 20 characters
        if (strlen($value) < 20) {
            self::add_error($field_name, sprintf(
                __('%s ne semble pas valide (trop court).', 'ai-content-studio'),
                $field_name
            ));
            return false;
        }
        return true;
    }

    /**
     * Validate user exists
     *
     * @param int $user_id
     * @param string $field_name
     * @return bool
     */
    public static function user_exists($user_id, $field_name = 'user_id') {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            self::add_error($field_name, __('Utilisateur introuvable.', 'ai-content-studio'));
            return false;
        }
        return true;
    }

    /**
     * Validate data against rules
     *
     * @param array $data
     * @param array $rules
     * @return bool
     */
    public static function validate($data, $rules) {
        self::clear_errors();

        foreach ($rules as $field => $field_rules) {
            $value = $data[$field] ?? null;

            foreach ($field_rules as $rule => $params) {
                // Handle rules without parameters
                if (is_int($rule)) {
                    $rule = $params;
                    $params = [];
                }

                // Handle array parameters
                if (!is_array($params)) {
                    $params = [$params];
                }

                // Add value as first parameter
                array_unshift($params, $value);

                // Add field name as last parameter
                $params[] = $field;

                // Call validation method
                if (method_exists(self::class, $rule)) {
                    call_user_func_array([self::class, $rule], $params);
                }
            }
        }

        return !self::has_errors();
    }

    /**
     * Add validation error
     *
     * @param string $field
     * @param string $message
     * @return void
     */
    private static function add_error($field, $message) {
        if (!isset(self::$errors[$field])) {
            self::$errors[$field] = [];
        }
        self::$errors[$field][] = $message;
    }

    /**
     * Get all errors
     *
     * @return array
     */
    public static function get_errors() {
        return self::$errors;
    }

    /**
     * Get errors for specific field
     *
     * @param string $field
     * @return array
     */
    public static function get_field_errors($field) {
        return self::$errors[$field] ?? [];
    }

    /**
     * Check if has errors
     *
     * @return bool
     */
    public static function has_errors() {
        return !empty(self::$errors);
    }

    /**
     * Clear all errors
     *
     * @return void
     */
    public static function clear_errors() {
        self::$errors = [];
    }

    /**
     * Get first error message
     *
     * @return string|null
     */
    public static function get_first_error() {
        if (empty(self::$errors)) {
            return null;
        }

        $first_field = array_key_first(self::$errors);
        return self::$errors[$first_field][0] ?? null;
    }
}
