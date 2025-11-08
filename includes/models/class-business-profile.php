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

        $sanitized = [
            'user_id' => Sanitizer::int($data['user_id']),
            'business_type' => Sanitizer::text($data['business_type']),
            'business_name' => Sanitizer::text($data['business_name']),
            'description' => Sanitizer::textarea($data['description'] ?? ''),
            'niche' => Sanitizer::text($data['niche'] ?? ''),
            'location' => Sanitizer::text($data['location'] ?? ''),
            'target_audience' => Sanitizer::json(wp_json_encode($data['target_audience'] ?? [])),
            'goals' => Sanitizer::json(wp_json_encode($data['goals'] ?? [])),
            'platforms' => Sanitizer::json(wp_json_encode($data['platforms'] ?? [])),
            'languages' => Sanitizer::json(wp_json_encode($data['languages'] ?? [])),
            'has_blog' => Sanitizer::bool($data['has_blog'] ?? false),
        ];

        $result = $wpdb->insert($this->table_name, $sanitized, ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d']);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get profile by user ID
     */
    public function get_by_user($user_id) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE user_id = %d", $user_id), ARRAY_A);

        if ($row) {
            $row['target_audience'] = json_decode($row['target_audience'], true);
            $row['goals'] = json_decode($row['goals'], true);
            $row['platforms'] = json_decode($row['platforms'], true);
            $row['languages'] = json_decode($row['languages'], true);
        }

        return $row;
    }

    /**
     * Update profile
     */
    public function update($user_id, $data) {
        global $wpdb;

        $sanitized = [
            'business_type' => Sanitizer::text($data['business_type']),
            'business_name' => Sanitizer::text($data['business_name']),
            'description' => Sanitizer::textarea($data['description'] ?? ''),
            'niche' => Sanitizer::text($data['niche'] ?? ''),
            'location' => Sanitizer::text($data['location'] ?? ''),
            'target_audience' => Sanitizer::json(wp_json_encode($data['target_audience'] ?? [])),
            'goals' => Sanitizer::json(wp_json_encode($data['goals'] ?? [])),
            'platforms' => Sanitizer::json(wp_json_encode($data['platforms'] ?? [])),
            'languages' => Sanitizer::json(wp_json_encode($data['languages'] ?? [])),
            'has_blog' => Sanitizer::bool($data['has_blog'] ?? false),
        ];

        return $wpdb->update($this->table_name, $sanitized, ['user_id' => $user_id], ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'], ['%d']);
    }

    /**
     * Delete profile
     */
    public function delete($user_id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, ['user_id' => $user_id], ['%d']);
    }
}
