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
     * @param string $category Optional category (api, generation, auth, database, etc)
     * @return void
     */
    public static function log($message, $level = self::INFO, $context = [], $category = 'general') {
        // Only log if debug is enabled or level is error/critical/warning
        if (!self::should_log($level)) {
            return;
        }

        $log_entry = self::format_log_entry($message, $level, $context);

        // Log to error_log
        error_log($log_entry);

        // Save to database for admin dashboard
        if (self::should_save_to_db($level)) {
            self::save_to_database($message, $level, $context, $category);
        }
    }

    /**
     * Log debug message
     *
     * @param string $message
     * @param array $context
     * @param string $category
     * @return void
     */
    public static function debug($message, $context = [], $category = 'general') {
        self::log($message, self::DEBUG, $context, $category);
    }

    /**
     * Log info message
     *
     * @param string $message
     * @param array $context
     * @param string $category
     * @return void
     */
    public static function info($message, $context = [], $category = 'general') {
        self::log($message, self::INFO, $context, $category);
    }

    /**
     * Log warning message
     *
     * @param string $message
     * @param array $context
     * @param string $category
     * @return void
     */
    public static function warning($message, $context = [], $category = 'general') {
        self::log($message, self::WARNING, $context, $category);
    }

    /**
     * Log error message
     *
     * @param string $message
     * @param array $context
     * @param string $category
     * @return void
     */
    public static function error($message, $context = [], $category = 'general') {
        self::log($message, self::ERROR, $context, $category);
    }

    /**
     * Log critical message
     *
     * @param string $message
     * @param array $context
     * @param string $category
     * @return void
     */
    public static function critical($message, $context = [], $category = 'general') {
        self::log($message, self::CRITICAL, $context, $category);
    }

    /**
     * Check if should log based on level
     *
     * @param string $level
     * @return bool
     */
    private static function should_log($level) {
        $debug_enabled = get_option('acs_enable_debug', false);

        // Always log warnings, errors and critical
        if (in_array($level, [self::WARNING, self::ERROR, self::CRITICAL])) {
            return true;
        }

        // Only log debug/info if debug is enabled or WP_DEBUG is true
        return $debug_enabled || (defined('WP_DEBUG') && WP_DEBUG);
    }

    /**
     * Check if should save to database
     *
     * @param string $level
     * @return bool
     */
    private static function should_save_to_db($level) {
        $debug_enabled = get_option('acs_enable_debug', false);

        // Always save warnings, errors and critical to database
        if (in_array($level, [self::WARNING, self::ERROR, self::CRITICAL])) {
            return true;
        }

        // Save info if debug enabled
        return $debug_enabled;
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
     * @param string $category
     * @return void
     */
    private static function save_to_database($message, $level, $context, $category = 'general') {
        global $wpdb;

        $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'system_logs';

        // Get backtrace to identify file/line
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        $caller = isset($backtrace[3]) ? $backtrace[3] : (isset($backtrace[2]) ? $backtrace[2] : null);

        // Get request info
        $ip_address = self::get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : '';
        $request_uri = isset($_SERVER['REQUEST_URI']) ? substr($_SERVER['REQUEST_URI'], 0, 500) : '';

        $wpdb->insert(
            $table,
            [
                'user_id' => get_current_user_id() ?: 0,
                'level' => $level,
                'category' => $category,
                'message' => $message,
                'context' => !empty($context) ? wp_json_encode($context) : null,
                'file' => $caller ? (isset($caller['file']) ? basename($caller['file']) : '') : '',
                'line' => $caller ? (isset($caller['line']) ? $caller['line'] : null) : null,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'request_uri' => $request_uri,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private static function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    /**
     * Get recent logs from database
     *
     * @param int $limit
     * @param string $level
     * @param string $category
     * @param int $user_id
     * @return array
     */
    public static function get_recent_logs($limit = 100, $level = null, $category = null, $user_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'system_logs';

        $where = ['1=1'];
        $params = [];

        if ($level) {
            $where[] = 'level = %s';
            $params[] = $level;
        }

        if ($category) {
            $where[] = 'category = %s';
            $params[] = $category;
        }

        if ($user_id !== null) {
            $where[] = 'user_id = %d';
            $params[] = $user_id;
        }

        $where_clause = implode(' AND ', $where);
        $params[] = $limit;

        if (count($params) > 1) {
            $query = $wpdb->prepare(
                "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d",
                $params
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
                $limit
            );
        }

        $results = $wpdb->get_results($query, ARRAY_A);

        // Decode JSON context
        foreach ($results as &$result) {
            if (!empty($result['context'])) {
                $result['context'] = json_decode($result['context'], true);
            }
        }

        return $results;
    }

    /**
     * Clear old logs (older than N days)
     *
     * @param int $days Number of days to keep
     * @return int Number of deleted rows
     */
    public static function clear_old_logs($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'system_logs';

        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));

        return $result;
    }

    /**
     * Get log statistics
     *
     * @return array
     */
    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'system_logs';

        $stats = $wpdb->get_row("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN level = 'error' THEN 1 ELSE 0 END) as errors,
                SUM(CASE WHEN level = 'critical' THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN level = 'warning' THEN 1 ELSE 0 END) as warnings,
                SUM(CASE WHEN level = 'info' THEN 1 ELSE 0 END) as info,
                SUM(CASE WHEN level = 'debug' THEN 1 ELSE 0 END) as debug,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 ELSE 0 END) as last_hour,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as last_24h
            FROM {$table}
        ", ARRAY_A);

        return $stats;
    }
}
