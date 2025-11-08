<?php
/**
 * Logger Utility
 *
 * @package ACS\Utils
 */

namespace ACS\Utils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logger class
 */
class Logger {

    /**
     * Log levels
     */
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
    const CRITICAL = 'critical';

    /**
     * Log a message
     *
     * @param string $message
     * @param string $level
     * @param array $context
     * @return void
     */
    public static function log($message, $level = self::INFO, $context = []) {
        // Only log if debug is enabled or level is error/critical
        if (!self::should_log($level)) {
            return;
        }

        $log_entry = self::format_log_entry($message, $level, $context);

        // Log to error_log
        error_log($log_entry);

        // Optionally save to database for admin dashboard
        if (self::should_save_to_db($level)) {
            self::save_to_database($message, $level, $context);
        }
    }

    /**
     * Log debug message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function debug($message, $context = []) {
        self::log($message, self::DEBUG, $context);
    }

    /**
     * Log info message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function info($message, $context = []) {
        self::log($message, self::INFO, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function warning($message, $context = []) {
        self::log($message, self::WARNING, $context);
    }

    /**
     * Log error message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function error($message, $context = []) {
        self::log($message, self::ERROR, $context);
    }

    /**
     * Log critical message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function critical($message, $context = []) {
        self::log($message, self::CRITICAL, $context);
    }

    /**
     * Check if should log based on level
     *
     * @param string $level
     * @return bool
     */
    private static function should_log($level) {
        $debug_enabled = get_option('acs_enable_debug', false);

        // Always log errors and critical
        if (in_array($level, [self::ERROR, self::CRITICAL])) {
            return true;
        }

        // Only log debug/info/warning if debug is enabled or WP_DEBUG is true
        return $debug_enabled || (defined('WP_DEBUG') && WP_DEBUG);
    }

    /**
     * Check if should save to database
     *
     * @param string $level
     * @return bool
     */
    private static function should_save_to_db($level) {
        // Only save errors and critical to database
        return in_array($level, [self::ERROR, self::CRITICAL]);
    }

    /**
     * Format log entry
     *
     * @param string $message
     * @param string $level
     * @param array $context
     * @return string
     */
    private static function format_log_entry($message, $level, $context) {
        $timestamp = current_time('Y-m-d H:i:s');
        $context_str = !empty($context) ? ' | Context: ' . wp_json_encode($context) : '';

        return sprintf(
            '[%s] [ACS] [%s] %s%s',
            $timestamp,
            strtoupper($level),
            $message,
            $context_str
        );
    }

    /**
     * Save log to database
     *
     * @param string $message
     * @param string $level
     * @param array $context
     * @return void
     */
    private static function save_to_database($message, $level, $context) {
        global $wpdb;

        // Use analytics table for now (could create dedicated logs table)
        $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'analytics';

        $wpdb->insert(
            $table,
            [
                'user_id' => get_current_user_id() ?: 0,
                'metric_type' => 'system_log_' . $level,
                'metric_value' => 1,
                'metadata' => wp_json_encode([
                    'message' => $message,
                    'context' => $context,
                    'timestamp' => current_time('mysql'),
                ]),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%d', '%s', '%s']
        );
    }

    /**
     * Get recent logs from database
     *
     * @param int $limit
     * @param string $level
     * @return array
     */
    public static function get_recent_logs($limit = 100, $level = null) {
        global $wpdb;
        $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'analytics';

        $where = "metric_type LIKE 'system_log_%'";

        if ($level) {
            $where .= $wpdb->prepare(" AND metric_type = %s", 'system_log_' . $level);
        }

        $query = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d",
            $limit
        );

        return $wpdb->get_results($query);
    }
}
