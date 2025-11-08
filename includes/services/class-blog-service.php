<?php
/**
 * Blog Article Generation Service
 *
 * @package ACS\Services
 */

namespace ACS\Services;

use ACS\Utils\Logger;
use ACS\Utils\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

class Blog_Service {

    /**
     * Generate article ideas
     *
     * @param array $profile Business profile
     * @param int $count Number of ideas
     * @return array|WP_Error
     */
    public function generate_ideas($profile, $count = 10) {
        $claude = new Claude_Service();
        return $claude->generate_article_ideas($profile, $count);
    }

    /**
     * Generate full article
     *
     * @param array $params Article parameters
     * @return array|WP_Error
     */
    public function generate_article($params) {
        $claude = new Claude_Service();
        $article = $claude->generate_full_article($params);

        if (is_wp_error($article)) {
            return $article;
        }

        // Calculate word count and reading time
        $article['word_count'] = Helpers::get_word_count($article['content']);
        $article['reading_time'] = Helpers::calculate_reading_time($article['content']);

        // Run SEO analysis
        if (isset($article['title']) && isset($params['keyword'])) {
            $seo_analyzer = new SEO_Analyzer();
            $seo_analysis = $seo_analyzer->analyze($article['content'], $article['title'], $params['keyword']);
            $article['seo_score'] = $seo_analysis['score'] ?? 0;
            $article['seo_analysis'] = $seo_analysis;
        }

        return $article;
    }

    /**
     * Publish article to WordPress
     *
     * @param int $article_id Internal article ID
     * @param int $user_id User ID
     * @return int|WP_Error WordPress post ID
     */
    public function publish_to_wordpress($article_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'blog_articles';

        $article = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND user_id = %d", $article_id, $user_id), ARRAY_A);

        if (!$article) {
            return new \WP_Error('not_found', __('Article introuvable', 'ai-content-studio'));
        }

        $post_data = [
            'post_title' => $article['title'],
            'post_content' => $article['content'],
            'post_excerpt' => $article['excerpt'],
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_type' => 'post',
        ];

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Update meta description
        if (!empty($article['meta_description'])) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $article['meta_description']);
        }

        // Update focus keyword
        if (!empty($article['focus_keyword'])) {
            update_post_meta($post_id, '_yoast_wpseo_focuskw', $article['focus_keyword']);
        }

        // Update article with published post ID
        $wpdb->update($table, ['published_post_id' => $post_id, 'status' => 'published'], ['id' => $article_id], ['%d', '%s'], ['%d']);

        Logger::info('Article published to WordPress', ['article_id' => $article_id, 'post_id' => $post_id]);

        return $post_id;
    }
}
