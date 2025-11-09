<?php
/**
 * Business Profile Model
 *
 * @package ACS\Models
 */

namespace ACS\Models;

use ACS\Database;
use ACS\Utils\Sanitizer;
use ACS\Utils\Validator;

if (!defined('ABSPATH')) {
    exit;
}

class Business_Profile {

    protected $table_name;

    public function __construct() {
        $this->table_name = Database::get_table_name('business_profiles');
    }

    /**
     * Create profile
     */
    public function create($data) {
        global $wpdb;

        // Handle legacy and new field names
        $user_type = $data['user_type'] ?? $data['business_type'] ?? '';
        $social_platforms = $data['social_platforms'] ?? $data['platforms'] ?? [];

        // Handle target_audience - can be string or array (legacy)
        $target_audience = $data['target_audience'] ?? '';
        if (is_array($target_audience)) {
            $target_audience = isset($target_audience['type']) ? $target_audience['type'] : '';
        }

        $sanitized = [
            'user_id' => Sanitizer::int($data['user_id']),
            'business_type' => Sanitizer::text($user_type), // Keep for backward compatibility
            'business_name' => Sanitizer::text($data['business_name']),
            'description' => Sanitizer::textarea($data['description'] ?? ''),
            'niche' => Sanitizer::text($data['niche'] ?? ''),
            'location' => Sanitizer::text($data['location'] ?? ''),
            'target_audience' => Sanitizer::text($target_audience),
            'goals' => Sanitizer::json(wp_json_encode($data['goals'] ?? [])),
            'platforms' => Sanitizer::json(wp_json_encode($social_platforms)), // Keep for backward compatibility
            'languages' => Sanitizer::json(wp_json_encode($data['languages'] ?? [])),
            'has_blog' => Sanitizer::bool($data['has_blog'] ?? false),
            // New onboarding fields
            'user_type' => Sanitizer::text($user_type),
            'sector' => Sanitizer::text($data['sector'] ?? ''),
            'website' => Sanitizer::text($data['website'] ?? ''),
            'social_platforms' => Sanitizer::json(wp_json_encode($social_platforms)),
            'posting_frequency' => Sanitizer::text($data['posting_frequency'] ?? ''),
            'seo_goals' => Sanitizer::json(wp_json_encode($data['seo_goals'] ?? [])),
            'blog_topics' => Sanitizer::json(wp_json_encode($data['blog_topics'] ?? [])),
            'primary_keywords' => Sanitizer::json(wp_json_encode($data['primary_keywords'] ?? [])),
        ];

        $result = $wpdb->insert(
            $this->table_name,
            $sanitized,
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get profile by user ID
     */
    public function get_by_user($user_id) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE user_id = %d", $user_id), ARRAY_A);

        if ($row) {
            // Decode JSON fields
            $row['goals'] = json_decode($row['goals'], true) ?: [];
            $row['platforms'] = json_decode($row['platforms'], true) ?: [];
            $row['languages'] = json_decode($row['languages'], true) ?: [];

            // Decode new JSON fields
            $row['social_platforms'] = json_decode($row['social_platforms'], true) ?: [];
            $row['seo_goals'] = json_decode($row['seo_goals'], true) ?: [];
            $row['blog_topics'] = json_decode($row['blog_topics'], true) ?: [];
            $row['primary_keywords'] = json_decode($row['primary_keywords'], true) ?: [];
        }

        return $row;
    }

    /**
     * Update profile
     */
    public function update($user_id, $data) {
        global $wpdb;

        // Handle legacy and new field names
        $user_type = $data['user_type'] ?? $data['business_type'] ?? '';
        $social_platforms = $data['social_platforms'] ?? $data['platforms'] ?? [];

        // Handle target_audience - can be string or array (legacy)
        $target_audience = $data['target_audience'] ?? '';
        if (is_array($target_audience)) {
            $target_audience = isset($target_audience['type']) ? $target_audience['type'] : '';
        }

        $sanitized = [
            'business_type' => Sanitizer::text($user_type), // Keep for backward compatibility
            'business_name' => Sanitizer::text($data['business_name']),
            'description' => Sanitizer::textarea($data['description'] ?? ''),
            'niche' => Sanitizer::text($data['niche'] ?? ''),
            'location' => Sanitizer::text($data['location'] ?? ''),
            'target_audience' => Sanitizer::text($target_audience),
            'goals' => Sanitizer::json(wp_json_encode($data['goals'] ?? [])),
            'platforms' => Sanitizer::json(wp_json_encode($social_platforms)), // Keep for backward compatibility
            'languages' => Sanitizer::json(wp_json_encode($data['languages'] ?? [])),
            'has_blog' => Sanitizer::bool($data['has_blog'] ?? false),
            // New onboarding fields
            'user_type' => Sanitizer::text($user_type),
            'sector' => Sanitizer::text($data['sector'] ?? ''),
            'website' => Sanitizer::text($data['website'] ?? ''),
            'social_platforms' => Sanitizer::json(wp_json_encode($social_platforms)),
            'posting_frequency' => Sanitizer::text($data['posting_frequency'] ?? ''),
            'seo_goals' => Sanitizer::json(wp_json_encode($data['seo_goals'] ?? [])),
            'blog_topics' => Sanitizer::json(wp_json_encode($data['blog_topics'] ?? [])),
            'primary_keywords' => Sanitizer::json(wp_json_encode($data['primary_keywords'] ?? [])),
        ];

        return $wpdb->update(
            $this->table_name,
            $sanitized,
            ['user_id' => $user_id],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );
    }

    /**
     * Delete profile
     */
    public function delete($user_id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, ['user_id' => $user_id], ['%d']);
    }
}
