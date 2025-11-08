<?php
/**
 * Usage Tracking Service
 *
 * @package ACS\Services
 */

namespace ACS\Services;

use ACS\Database;
use ACS\Utils\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Usage_Service class
 */
class Usage_Service {

    /**
     * Get usage statistics for user
     *
     * @param int|null $user_id
     * @return array
     */
    public function get_usage_stats($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        global $wpdb;
        $table = Database::get_table_name('usage_stats');

        $stats = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d", $user_id),
            ARRAY_A
        );

        if (!$stats) {
            // Create initial stats
            $this->create_usage_stats($user_id);
            return $this->get_usage_stats($user_id);
        }

        return $stats;
    }

    /**
     * Create initial usage stats for user
     *
     * @param int $user_id
     * @return bool
     */
    private function create_usage_stats($user_id) {
        global $wpdb;
        $table = Database::get_table_name('usage_stats');

        $result = $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'posts_generated' => 0,
                'posts_this_month' => 0,
                'articles_generated' => 0,
                'articles_this_month' => 0,
                'images_generated' => 0,
                'images_this_month' => 0,
                'videos_generated' => 0,
                'videos_this_month' => 0,
                'last_reset_date' => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s']
        );

        return $result !== false;
    }

    /**
     * Increment post usage
     *
     * @param int|null $user_id
     * @return bool
     */
    public function increment_post_usage($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        global $wpdb;
        $table = Database::get_table_name('usage_stats');

        // Ensure stats exist
        $this->get_usage_stats($user_id);

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table}
                SET posts_generated = posts_generated + 1,
                    posts_this_month = posts_this_month + 1,
                    updated_at = %s
                WHERE user_id = %d",
                current_time('mysql'),
                $user_id
            )
        );

        if ($result) {
            Logger::debug('Post usage incremented', ['user_id' => $user_id]);
            do_action('acs_post_generated', $user_id);
        }

        return $result !== false;
    }

    /**
     * Increment article usage
     *
     * @param int|null $user_id
     * @return bool
     */
    public function increment_article_usage($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        global $wpdb;
        $table = Database::get_table_name('usage_stats');

        $this->get_usage_stats($user_id);

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table}
                SET articles_generated = articles_generated + 1,
                    articles_this_month = articles_this_month + 1,
                    updated_at = %s
                WHERE user_id = %d",
                current_time('mysql'),
                $user_id
            )
        );

        if ($result) {
            Logger::debug('Article usage incremented', ['user_id' => $user_id]);
            do_action('acs_article_generated', $user_id);
        }

        return $result !== false;
    }

    /**
     * Increment image usage
     *
     * @param int|null $user_id
     * @return bool
     */
    public function increment_image_usage($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        global $wpdb;
        $table = Database::get_table_name('usage_stats');

        $this->get_usage_stats($user_id);

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table}
                SET images_generated = images_generated + 1,
                    images_this_month = images_this_month + 1,
                    updated_at = %s
                WHERE user_id = %d",
                current_time('mysql'),
                $user_id
            )
        );

        if ($result) {
            Logger::debug('Image usage incremented', ['user_id' => $user_id]);
            do_action('acs_image_generated', $user_id);
        }

        return $result !== false;
    }

    /**
     * Reset monthly usage for a user
     *
     * @param int $user_id
     * @return bool
     */
    public function reset_monthly_usage($user_id) {
        global $wpdb;
        $table = Database::get_table_name('usage_stats');

        $result = $wpdb->update(
            $table,
            [
                'posts_this_month' => 0,
                'articles_this_month' => 0,
                'images_this_month' => 0,
                'videos_this_month' => 0,
                'last_reset_date' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['user_id' => $user_id],
            ['%d', '%d', '%d', '%d', '%s', '%s'],
            ['%d']
        );

        if ($result !== false) {
            Logger::info('Monthly usage reset', ['user_id' => $user_id]);
            do_action('acs_usage_reset', $user_id);
        }

        return $result !== false;
    }

    /**
     * Reset monthly usage for all users
     *
     * @return int Number of users reset
     */
    public static function reset_all_monthly_usage() {
        global $wpdb;
        $table = Database::get_table_name('usage_stats');

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table}
                SET posts_this_month = 0,
                    articles_this_month = 0,
                    images_this_month = 0,
                    videos_this_month = 0,
                    last_reset_date = %s,
                    updated_at = %s",
                current_time('mysql'),
                current_time('mysql')
            )
        );

        Logger::info('Monthly usage reset for all users', ['count' => $result]);

        return $result;
    }

    /**
     * Get usage percentage for a metric
     *
     * @param int $used
     * @param int $limit
     * @return float
     */
    public function get_usage_percentage($used, $limit) {
        if ($limit === -1) {
            return 0; // Unlimited
        }

        if ($limit === 0) {
            return 100;
        }

        return min(100, ($used / $limit) * 100);
    }

    /**
     * Get usage summary for user
     *
     * @param int|null $user_id
     * @return array
     */
    public function get_usage_summary($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $stats = $this->get_usage_stats($user_id);
        $subscription_service = new Subscription_Service();
        $limits = $subscription_service->get_user_limits($user_id);

        return [
            'posts' => [
                'used' => $stats['posts_this_month'] ?? 0,
                'limit' => $limits['posts_per_month'] ?? 0,
                'percentage' => $this->get_usage_percentage(
                    $stats['posts_this_month'] ?? 0,
                    $limits['posts_per_month'] ?? 0
                ),
                'total_all_time' => $stats['posts_generated'] ?? 0,
            ],
            'articles' => [
                'used' => $stats['articles_this_month'] ?? 0,
                'limit' => $limits['articles_per_month'] ?? 0,
                'percentage' => $this->get_usage_percentage(
                    $stats['articles_this_month'] ?? 0,
                    $limits['articles_per_month'] ?? 0
                ),
                'total_all_time' => $stats['articles_generated'] ?? 0,
            ],
            'images' => [
                'used' => $stats['images_this_month'] ?? 0,
                'limit' => $limits['images_per_month'] ?? 0,
                'percentage' => $this->get_usage_percentage(
                    $stats['images_this_month'] ?? 0,
                    $limits['images_per_month'] ?? 0
                ),
                'total_all_time' => $stats['images_generated'] ?? 0,
            ],
            'last_reset' => $stats['last_reset_date'] ?? null,
        ];
    }

    /**
     * Check if user needs reset (for manual triggers)
     *
     * @param int $user_id
     * @return bool
     */
    public function needs_reset($user_id) {
        $stats = $this->get_usage_stats($user_id);
        $last_reset = $stats['last_reset_date'] ?? null;

        if (!$last_reset) {
            return true;
        }

        $last_reset_time = strtotime($last_reset);
        $current_month_start = strtotime('first day of this month 00:00:00');

        return $last_reset_time < $current_month_start;
    }
}
