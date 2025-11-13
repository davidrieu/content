<?php
/**
 * Project Model
 *
 * @package ACS\Models
 */

namespace ACS\Models;

use ACS\Database;
use ACS\Utils\Sanitizer;

if (!defined('ABSPATH')) {
    exit;
}

class Project {

    protected $table_name;

    public function __construct() {
        $this->table_name = Database::get_table_name('projects');
    }

    /**
     * Create a new project
     *
     * @param array $data Project data
     * @return int|false Project ID on success, false on failure
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
            'project_name' => Sanitizer::text($data['project_name'] ?? $data['business_name'] ?? 'Mon Projet'),
            'is_active' => Sanitizer::bool($data['is_active'] ?? false),
            'business_type' => Sanitizer::text($user_type),
            'business_name' => Sanitizer::text($data['business_name']),
            'description' => Sanitizer::textarea($data['description'] ?? ''),
            'niche' => Sanitizer::text($data['niche'] ?? ''),
            'location' => Sanitizer::text($data['location'] ?? ''),
            'target_audience' => Sanitizer::text($target_audience),
            'goals' => Sanitizer::json(wp_json_encode($data['goals'] ?? [])),
            'platforms' => Sanitizer::json(wp_json_encode($social_platforms)),
            'languages' => Sanitizer::json(wp_json_encode($data['languages'] ?? [])),
            'has_blog' => Sanitizer::bool($data['has_blog'] ?? false),
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
            ['%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result) {
            $project_id = $wpdb->insert_id;

            // If this is marked as active, deactivate all other projects for this user
            if ($sanitized['is_active']) {
                $this->set_active_project($sanitized['user_id'], $project_id);
            }

            return $project_id;
        }

        return false;
    }

    /**
     * Get all projects for a user
     *
     * @param int $user_id User ID
     * @return array Array of projects
     */
    public function get_by_user($user_id) {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d ORDER BY is_active DESC, created_at DESC",
            $user_id
        ), ARRAY_A);

        if ($rows) {
            foreach ($rows as &$row) {
                // Decode JSON fields
                $row['goals'] = json_decode($row['goals'], true) ?: [];
                $row['platforms'] = json_decode($row['platforms'], true) ?: [];
                $row['languages'] = json_decode($row['languages'], true) ?: [];
                $row['social_platforms'] = json_decode($row['social_platforms'], true) ?: [];
                $row['seo_goals'] = json_decode($row['seo_goals'], true) ?: [];
                $row['blog_topics'] = json_decode($row['blog_topics'], true) ?: [];
                $row['primary_keywords'] = json_decode($row['primary_keywords'], true) ?: [];
                $row['is_active'] = (bool) $row['is_active'];
                $row['has_blog'] = (bool) $row['has_blog'];
            }
        }

        return $rows ?: [];
    }

    /**
     * Get active project for a user
     *
     * @param int $user_id User ID
     * @return array|null Active project or null
     */
    public function get_active_project($user_id) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND is_active = 1 LIMIT 1",
            $user_id
        ), ARRAY_A);

        if ($row) {
            // Decode JSON fields
            $row['goals'] = json_decode($row['goals'], true) ?: [];
            $row['platforms'] = json_decode($row['platforms'], true) ?: [];
            $row['languages'] = json_decode($row['languages'], true) ?: [];
            $row['social_platforms'] = json_decode($row['social_platforms'], true) ?: [];
            $row['seo_goals'] = json_decode($row['seo_goals'], true) ?: [];
            $row['blog_topics'] = json_decode($row['blog_topics'], true) ?: [];
            $row['primary_keywords'] = json_decode($row['primary_keywords'], true) ?: [];
            $row['is_active'] = (bool) $row['is_active'];
            $row['has_blog'] = (bool) $row['has_blog'];
        }

        return $row;
    }

    /**
     * Get project by ID
     *
     * @param int $project_id Project ID
     * @return array|null Project data or null
     */
    public function get_by_id($project_id) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $project_id
        ), ARRAY_A);

        if ($row) {
            // Decode JSON fields
            $row['goals'] = json_decode($row['goals'], true) ?: [];
            $row['platforms'] = json_decode($row['platforms'], true) ?: [];
            $row['languages'] = json_decode($row['languages'], true) ?: [];
            $row['social_platforms'] = json_decode($row['social_platforms'], true) ?: [];
            $row['seo_goals'] = json_decode($row['seo_goals'], true) ?: [];
            $row['blog_topics'] = json_decode($row['blog_topics'], true) ?: [];
            $row['primary_keywords'] = json_decode($row['primary_keywords'], true) ?: [];
            $row['is_active'] = (bool) $row['is_active'];
            $row['has_blog'] = (bool) $row['has_blog'];
        }

        return $row;
    }

    /**
     * Update a project
     *
     * @param int $project_id Project ID
     * @param array $data Updated data
     * @return int|false Number of rows updated, or false on error
     */
    public function update($project_id, $data) {
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
            'project_name' => Sanitizer::text($data['project_name'] ?? ''),
            'business_type' => Sanitizer::text($user_type),
            'business_name' => Sanitizer::text($data['business_name']),
            'description' => Sanitizer::textarea($data['description'] ?? ''),
            'niche' => Sanitizer::text($data['niche'] ?? ''),
            'location' => Sanitizer::text($data['location'] ?? ''),
            'target_audience' => Sanitizer::text($target_audience),
            'goals' => Sanitizer::json(wp_json_encode($data['goals'] ?? [])),
            'platforms' => Sanitizer::json(wp_json_encode($social_platforms)),
            'languages' => Sanitizer::json(wp_json_encode($data['languages'] ?? [])),
            'has_blog' => Sanitizer::bool($data['has_blog'] ?? false),
            'user_type' => Sanitizer::text($user_type),
            'sector' => Sanitizer::text($data['sector'] ?? ''),
            'website' => Sanitizer::text($data['website'] ?? ''),
            'social_platforms' => Sanitizer::json(wp_json_encode($social_platforms)),
            'posting_frequency' => Sanitizer::text($data['posting_frequency'] ?? ''),
            'seo_goals' => Sanitizer::json(wp_json_encode($data['seo_goals'] ?? [])),
            'blog_topics' => Sanitizer::json(wp_json_encode($data['blog_topics'] ?? [])),
            'primary_keywords' => Sanitizer::json(wp_json_encode($data['primary_keywords'] ?? [])),
        ];

        // Remove empty keys
        $sanitized = array_filter($sanitized, function($value) {
            return $value !== '';
        });

        return $wpdb->update(
            $this->table_name,
            $sanitized,
            ['id' => $project_id],
            array_fill(0, count($sanitized), '%s'),
            ['%d']
        );
    }

    /**
     * Set active project for a user (and deactivate all others)
     *
     * @param int $user_id User ID
     * @param int $project_id Project ID to activate
     * @return bool Success
     */
    public function set_active_project($user_id, $project_id) {
        global $wpdb;

        // Verify the project belongs to this user
        $project = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d AND user_id = %d",
            $project_id,
            $user_id
        ));

        if (!$project) {
            return false;
        }

        // Deactivate all projects for this user
        $wpdb->update(
            $this->table_name,
            ['is_active' => 0],
            ['user_id' => $user_id],
            ['%d'],
            ['%d']
        );

        // Activate the specified project
        $wpdb->update(
            $this->table_name,
            ['is_active' => 1],
            ['id' => $project_id, 'user_id' => $user_id],
            ['%d'],
            ['%d', '%d']
        );

        return true;
    }

    /**
     * Delete a project
     *
     * @param int $project_id Project ID
     * @param int $user_id User ID (for security)
     * @return int|false Number of rows deleted, or false on error
     */
    public function delete($project_id, $user_id) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            ['id' => $project_id, 'user_id' => $user_id],
            ['%d', '%d']
        );
    }

    /**
     * Count total projects for a user
     *
     * @param int $user_id User ID
     * @return int Number of projects
     */
    public function count_by_user($user_id) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));
    }
}
