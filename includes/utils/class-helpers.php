<?php
/**
 * Helper Utility Functions
 *
 * @package ACS\Utils
 */

namespace ACS\Utils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helpers class
 */
class Helpers {

    /**
     * Get current user plan
     *
     * @param int|null $user_id
     * @return string
     */
    public static function get_user_plan($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return get_user_meta($user_id, 'acs_subscription_plan', true) ?: 'free';
    }

    /**
     * Get current user subscription status
     *
     * @param int|null $user_id
     * @return string
     */
    public static function get_subscription_status($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return get_user_meta($user_id, 'acs_subscription_status', true) ?: 'active';
    }

    /**
     * Check if user has completed onboarding
     *
     * @param int|null $user_id
     * @return bool
     */
    public static function has_completed_onboarding($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return (bool) get_user_meta($user_id, 'acs_onboarding_completed', true);
    }

    /**
     * Get user mode (simple/expert)
     *
     * @param int|null $user_id
     * @return string
     */
    public static function get_user_mode($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return get_user_meta($user_id, 'acs_mode', true) ?: 'simple';
    }

    /**
     * Format number with abbreviation
     *
     * @param int $number
     * @return string
     */
    public static function format_number($number) {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        return (string) $number;
    }

    /**
     * Calculate reading time
     *
     * @param string $content
     * @param int $words_per_minute
     * @return int
     */
    public static function calculate_reading_time($content, $words_per_minute = 200) {
        $word_count = str_word_count(strip_tags($content));
        $reading_time = ceil($word_count / $words_per_minute);
        return max(1, $reading_time);
    }

    /**
     * Get word count
     *
     * @param string $content
     * @return int
     */
    public static function get_word_count($content) {
        return str_word_count(strip_tags($content));
    }

    /**
     * Truncate text
     *
     * @param string $text
     * @param int $length
     * @param string $suffix
     * @return string
     */
    public static function truncate($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length - strlen($suffix)) . $suffix;
    }

    /**
     * Format date for display
     *
     * @param string $date
     * @param string $format
     * @return string
     */
    public static function format_date($date, $format = null) {
        if (!$format) {
            $format = get_option('date_format') . ' ' . get_option('time_format');
        }

        return date_i18n($format, strtotime($date));
    }

    /**
     * Get time ago
     *
     * @param string $datetime
     * @return string
     */
    public static function time_ago($datetime) {
        $timestamp = strtotime($datetime);
        return sprintf(
            /* translators: %s: Human-readable time difference */
            __('Il y a %s', 'ai-content-studio'),
            human_time_diff($timestamp, current_time('timestamp'))
        );
    }

    /**
     * Generate random string
     *
     * @param int $length
     * @return string
     */
    public static function generate_random_string($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Check if JSON
     *
     * @param string $string
     * @return bool
     */
    public static function is_json($string) {
        if (!is_string($string)) {
            return false;
        }

        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Safely decode JSON
     *
     * @param string $json
     * @param mixed $default
     * @return mixed
     */
    public static function decode_json($json, $default = []) {
        if (!self::is_json($json)) {
            return $default;
        }

        return json_decode($json, true);
    }

    /**
     * Get platform icon
     *
     * @param string $platform
     * @return string
     */
    public static function get_platform_icon($platform) {
        $icons = [
            'instagram' => 'dashicons-instagram',
            'facebook' => 'dashicons-facebook',
            'linkedin' => 'dashicons-linkedin',
            'twitter' => 'dashicons-twitter',
            'tiktok' => 'dashicons-video-alt3',
            'youtube' => 'dashicons-youtube',
        ];

        return $icons[$platform] ?? 'dashicons-share';
    }

    /**
     * Get platform color
     *
     * @param string $platform
     * @return string
     */
    public static function get_platform_color($platform) {
        $colors = [
            'instagram' => '#E4405F',
            'facebook' => '#1877F2',
            'linkedin' => '#0A66C2',
            'twitter' => '#1DA1F2',
            'tiktok' => '#000000',
            'youtube' => '#FF0000',
        ];

        return $colors[$platform] ?? '#666666';
    }

    /**
     * Get platform display name
     *
     * @param string $platform
     * @return string
     */
    public static function get_platform_name($platform) {
        $names = [
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'linkedin' => 'LinkedIn',
            'twitter' => 'Twitter/X',
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
        ];

        return $names[$platform] ?? ucfirst($platform);
    }

    /**
     * Get platform character limits
     *
     * @param string $platform
     * @return int
     */
    public static function get_platform_char_limit($platform) {
        $limits = [
            'instagram' => 2200,
            'facebook' => 63206,
            'linkedin' => 3000,
            'twitter' => 280,
            'tiktok' => 2200,
            'youtube' => 5000,
        ];

        return $limits[$platform] ?? 1000;
    }

    /**
     * Get available platforms
     *
     * @return array
     */
    public static function get_available_platforms() {
        return [
            'instagram',
            'facebook',
            'linkedin',
            'twitter',
            'tiktok',
            'youtube',
        ];
    }

    /**
     * Get available languages
     *
     * @return array
     */
    public static function get_available_languages() {
        return [
            'fr' => 'Français',
            'en' => 'English',
            'es' => 'Español',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            'nl' => 'Nederlands',
            'pl' => 'Polski',
            'ru' => 'Русский',
            'ja' => '日本語',
            'zh' => '中文',
            'ar' => 'العربية',
        ];
    }

    /**
     * Get language name
     *
     * @param string $code
     * @return string
     */
    public static function get_language_name($code) {
        $languages = self::get_available_languages();
        return $languages[$code] ?? $code;
    }

    /**
     * Check if feature is available for plan
     *
     * @param string $feature
     * @param string|null $plan
     * @return bool
     */
    public static function has_feature($feature, $plan = null) {
        if (!$plan) {
            $plan = self::get_user_plan();
        }

        if (!class_exists('ACS\\Config\\Plans_Config')) {
            return false;
        }

        return \ACS\Config\Plans_Config::has_feature($plan, $feature);
    }

    /**
     * Get upload directory for plugin
     *
     * @return array
     */
    public static function get_upload_dir() {
        $upload_dir = wp_upload_dir();
        $acs_dir = $upload_dir['basedir'] . '/ai-content-studio';
        $acs_url = $upload_dir['baseurl'] . '/ai-content-studio';

        // Create directory if it doesn't exist
        if (!file_exists($acs_dir)) {
            wp_mkdir_p($acs_dir);
        }

        return [
            'path' => $acs_dir,
            'url' => $acs_url,
        ];
    }

    /**
     * Send JSON response
     *
     * @param mixed $data
     * @param int $status_code
     * @return void
     */
    public static function send_json($data, $status_code = 200) {
        status_header($status_code);
        wp_send_json($data);
    }

    /**
     * Send JSON success response
     *
     * @param mixed $data
     * @param string $message
     * @return void
     */
    public static function send_json_success($data = null, $message = '') {
        wp_send_json_success([
            'data' => $data,
            'message' => $message,
        ]);
    }

    /**
     * Send JSON error response
     *
     * @param string $message
     * @param string $code
     * @param int $status_code
     * @return void
     */
    public static function send_json_error($message, $code = 'error', $status_code = 400) {
        status_header($status_code);
        wp_send_json_error([
            'code' => $code,
            'message' => $message,
        ]);
    }

    /**
     * Get option with default
     *
     * @param string $option
     * @param mixed $default
     * @return mixed
     */
    public static function get_option($option, $default = false) {
        return get_option('acs_' . $option, $default);
    }

    /**
     * Update option
     *
     * @param string $option
     * @param mixed $value
     * @return bool
     */
    public static function update_option($option, $value) {
        return update_option('acs_' . $option, $value);
    }

    /**
     * Delete option
     *
     * @param string $option
     * @return bool
     */
    public static function delete_option($option) {
        return delete_option('acs_' . $option);
    }
}
