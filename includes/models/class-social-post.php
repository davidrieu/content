<?php
/**
 * Social Post Model
 *
 * @package ACS\Models
 */

namespace ACS\Models;

use ACS\Database;
use ACS\Utils\Sanitizer;

if (!defined('ABSPATH')) {
    exit;
}

class Social_Post {

    protected $table_name;

    public function __construct() {
        $this->table_name = Database::get_table_name('social_posts');
    }

    public function create($data) {
        global $wpdb;

        $sanitized = [
            'user_id' => Sanitizer::int($data['user_id']),
            'platform' => Sanitizer::platform($data['platform']),
            'content' => Sanitizer::textarea($data['content']),
            'hashtags' => Sanitizer::json(wp_json_encode($data['hashtags'] ?? [])),
            'language' => Sanitizer::language($data['language'] ?? 'fr'),
            'status' => Sanitizer::text($data['status'] ?? 'draft'),
            'scheduled_for' => $data['scheduled_for'] ?? null,
            'metadata' => Sanitizer::json(wp_json_encode($data['metadata'] ?? [])),
        ];

        $result = $wpdb->insert($this->table_name, $sanitized, ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);
        return $result ? $wpdb->insert_id : false;
    }

    public function get($id, $user_id = null) {
        global $wpdb;
        $where = $wpdb->prepare("id = %d", $id);
        if ($user_id) {
            $where .= $wpdb->prepare(" AND user_id = %d", $user_id);
        }

        $row = $wpdb->get_row("SELECT * FROM {$this->table_name} WHERE {$where}", ARRAY_A);

        if ($row) {
            $row['hashtags'] = json_decode($row['hashtags'], true);
            $row['metadata'] = json_decode($row['metadata'], true);
        }

        return $row;
    }

    public function get_by_user($user_id, $filters = []) {
        global $wpdb;
        $where = $wpdb->prepare("user_id = %d", $user_id);

        if (!empty($filters['platform'])) {
            $where .= $wpdb->prepare(" AND platform = %s", $filters['platform']);
        }
        if (!empty($filters['status'])) {
            $where .= $wpdb->prepare(" AND status = %s", $filters['status']);
        }

        $limit = isset($filters['limit']) ? intval($filters['limit']) : 50;
        $offset = isset($filters['offset']) ? intval($filters['offset']) : 0;

        $results = $wpdb->get_results("SELECT * FROM {$this->table_name} WHERE {$where} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}", ARRAY_A);

        foreach ($results as &$row) {
            $row['hashtags'] = json_decode($row['hashtags'], true);
            $row['metadata'] = json_decode($row['metadata'], true);
        }

        return $results;
    }

    public function update($id, $data, $user_id = null) {
        global $wpdb;

        $sanitized = [];
        if (isset($data['content'])) $sanitized['content'] = Sanitizer::textarea($data['content']);
        if (isset($data['hashtags'])) $sanitized['hashtags'] = Sanitizer::json(wp_json_encode($data['hashtags']));
        if (isset($data['status'])) $sanitized['status'] = Sanitizer::text($data['status']);
        if (isset($data['scheduled_for'])) $sanitized['scheduled_for'] = $data['scheduled_for'];

        $where = ['id' => $id];
        if ($user_id) $where['user_id'] = $user_id;

        return $wpdb->update($this->table_name, $sanitized, $where);
    }

    public function delete($id, $user_id = null) {
        global $wpdb;
        $where = ['id' => $id];
        if ($user_id) $where['user_id'] = $user_id;
        return $wpdb->delete($this->table_name, $where);
    }
}
