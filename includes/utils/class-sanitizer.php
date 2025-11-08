<?php
/**
 * Sanitizer Utility
 *
 * @package ACS\Utils
 */

namespace ACS\Utils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sanitizer class
 */
class Sanitizer {

    /**
     * Sanitize text field
     *
     * @param string $value
     * @return string
     */
    public static function text($value) {
        return sanitize_text_field($value);
    }

    /**
     * Sanitize textarea
     *
     * @param string $value
     * @return string
     */
    public static function textarea($value) {
        return sanitize_textarea_field($value);
    }

    /**
     * Sanitize email
     *
     * @param string $value
     * @return string
     */
    public static function email($value) {
        return sanitize_email($value);
    }

    /**
     * Sanitize URL
     *
     * @param string $value
     * @return string
     */
    public static function url($value) {
        return esc_url_raw($value);
    }

    /**
     * Sanitize HTML content
     *
     * @param string $value
     * @return string
     */
    public static function html($value) {
        return wp_kses_post($value);
    }

    /**
     * Sanitize integer
     *
     * @param mixed $value
     * @return int
     */
    public static function int($value) {
        return absint($value);
    }

    /**
     * Sanitize float
     *
     * @param mixed $value
     * @return float
     */
    public static function float($value) {
        return floatval($value);
    }

    /**
     * Sanitize boolean
     *
     * @param mixed $value
     * @return bool
     */
    public static function bool($value) {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Sanitize JSON
     *
     * @param string $value
     * @return string|null
     */
    public static function json($value) {
        if (empty($value)) {
            return null;
        }

        // Try to decode to validate
        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        // Return re-encoded to ensure proper format
        return wp_json_encode($decoded);
    }

    /**
     * Sanitize array of values
     *
     * @param array $array
     * @param string $type
     * @return array
     */
    public static function array($array, $type = 'text') {
        if (!is_array($array)) {
            return [];
        }

        return array_map(function($value) use ($type) {
            return self::sanitize_by_type($value, $type);
        }, $array);
    }

    /**
     * Sanitize by type
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    public static function sanitize_by_type($value, $type) {
        switch ($type) {
            case 'email':
                return self::email($value);
            case 'url':
                return self::url($value);
            case 'int':
            case 'integer':
                return self::int($value);
            case 'float':
            case 'number':
                return self::float($value);
            case 'bool':
            case 'boolean':
                return self::bool($value);
            case 'html':
                return self::html($value);
            case 'textarea':
                return self::textarea($value);
            case 'json':
                return self::json($value);
            case 'text':
            default:
                return self::text($value);
        }
    }

    /**
     * Sanitize hex color
     *
     * @param string $color
     * @return string
     */
    public static function hex_color($color) {
        if (empty($color)) {
            return '';
        }

        // Remove # if present
        $color = ltrim($color, '#');

        // Validate hex color
        if (preg_match('/^[A-Fa-f0-9]{6}$/', $color)) {
            return '#' . $color;
        }

        return '';
    }

    /**
     * Sanitize slug
     *
     * @param string $value
     * @return string
     */
    public static function slug($value) {
        return sanitize_title($value);
    }

    /**
     * Sanitize key
     *
     * @param string $value
     * @return string
     */
    public static function key($value) {
        return sanitize_key($value);
    }

    /**
     * Sanitize file name
     *
     * @param string $value
     * @return string
     */
    public static function filename($value) {
        return sanitize_file_name($value);
    }

    /**
     * Sanitize hashtag
     *
     * @param string $value
     * @return string
     */
    public static function hashtag($value) {
        // Remove spaces and special chars, keep only alphanumeric and underscore
        $value = preg_replace('/[^a-zA-Z0-9_]/', '', $value);

        // Ensure it starts with #
        return '#' . ltrim($value, '#');
    }

    /**
     * Sanitize array of hashtags
     *
     * @param array $hashtags
     * @return array
     */
    public static function hashtags($hashtags) {
        if (!is_array($hashtags)) {
            return [];
        }

        return array_map([self::class, 'hashtag'], $hashtags);
    }

    /**
     * Sanitize platform name
     *
     * @param string $platform
     * @return string
     */
    public static function platform($platform) {
        $allowed_platforms = [
            'instagram',
            'facebook',
            'linkedin',
            'twitter',
            'tiktok',
            'youtube',
        ];

        $platform = strtolower(self::text($platform));

        return in_array($platform, $allowed_platforms) ? $platform : '';
    }

    /**
     * Sanitize language code
     *
     * @param string $language
     * @return string
     */
    public static function language($language) {
        // Basic language code validation (ISO 639-1)
        if (preg_match('/^[a-z]{2}$/', $language)) {
            return $language;
        }

        return 'fr'; // Default to French
    }

    /**
     * Sanitize post data recursively
     *
     * @param array $data
     * @param array $rules
     * @return array
     */
    public static function sanitize_post_data($data, $rules = []) {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $type = $rules[$key] ?? 'text';

            if (is_array($value)) {
                $sanitized[$key] = self::sanitize_post_data($value, is_array($type) ? $type : []);
            } else {
                $sanitized[$key] = self::sanitize_by_type($value, $type);
            }
        }

        return $sanitized;
    }
}
