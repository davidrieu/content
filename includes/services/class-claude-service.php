<?php
/**
 * Claude AI Service
 *
 * @package ACS\Services
 */

namespace ACS\Services;

use ACS\Data\Prompts_Library;
use ACS\Utils\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Claude_Service class
 */
class Claude_Service {

    /**
     * API key
     *
     * @var string
     */
    private $api_key;

    /**
     * API URL
     *
     * @var string
     */
    private $api_url = 'https://api.anthropic.com/v1/messages';

    /**
     * Model
     *
     * @var string
     */
    private $model;

    /**
     * Max retries
     *
     * @var int
     */
    private $max_retries = 3;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('acs_claude_api_key', '');
        $this->model = get_option('acs_claude_model', 'claude-sonnet-4-20250514');
        $this->max_retries = get_option('acs_max_retries', 3);
    }

    /**
     * Generate content strategy
     *
     * @param array $profile Business profile
     * @return array|WP_Error
     */
    public function generate_strategy($profile) {
        $prompt = Prompts_Library::get_strategy_prompt($profile);

        $response = $this->call_api($prompt, 4096);

        if (is_wp_error($response)) {
            return $response;
        }

        // Clean and parse JSON response
        $clean_response = $this->clean_json_response($response);
        $strategy = json_decode($clean_response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('Failed to parse strategy JSON', [
                'raw_response' => substr($response, 0, 200),
                'cleaned_response' => substr($clean_response, 0, 200),
                'json_error' => json_last_error_msg()
            ], 'api');
            return new \WP_Error('json_parse_error', __('Erreur de parsing JSON', 'ai-content-studio'));
        }

        return $strategy;
    }

    /**
     * Generate social media posts
     *
     * @param array $params Generation parameters
     * @return array|WP_Error
     */
    public function generate_social_posts($params) {
        $prompt = Prompts_Library::get_social_post_prompt($params);

        $response = $this->call_api($prompt, 2048);

        if (is_wp_error($response)) {
            return $response;
        }

        // Clean and parse JSON response
        $clean_response = $this->clean_json_response($response);
        $data = json_decode($clean_response, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['variants'])) {
            Logger::error('Failed to parse social posts JSON', [
                'raw_response' => substr($response, 0, 200),
                'cleaned_response' => substr($clean_response, 0, 200),
                'json_error' => json_last_error_msg()
            ], 'api');
            return new \WP_Error('json_parse_error', __('Erreur de parsing JSON', 'ai-content-studio'));
        }

        return $data['variants'];
    }

    /**
     * Generate article ideas
     *
     * @param array $profile Business profile
     * @param int $count Number of ideas
     * @return array|WP_Error
     */
    public function generate_article_ideas($profile, $count = 10) {
        $prompt = Prompts_Library::get_article_ideas_prompt($profile, $count);

        $response = $this->call_api($prompt, 3000);

        if (is_wp_error($response)) {
            return $response;
        }

        // Clean and parse JSON response
        $clean_response = $this->clean_json_response($response);
        $data = json_decode($clean_response, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['ideas'])) {
            Logger::error('Failed to parse article ideas JSON', [
                'json_error' => json_last_error_msg()
            ], 'api');
            return new \WP_Error('json_parse_error', __('Erreur de parsing JSON', 'ai-content-studio'));
        }

        return $data['ideas'];
    }

    /**
     * Generate full blog article
     *
     * @param array $params Article parameters
     * @return array|WP_Error
     */
    public function generate_full_article($params) {
        $prompt = Prompts_Library::get_article_generation_prompt($params);

        $response = $this->call_api($prompt, 8000);

        if (is_wp_error($response)) {
            return $response;
        }

        // Clean and parse JSON response
        $clean_response = $this->clean_json_response($response);
        $article = json_decode($clean_response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('Failed to parse article JSON', [
                'json_error' => json_last_error_msg()
            ], 'api');
            return new \WP_Error('json_parse_error', __('Erreur de parsing JSON', 'ai-content-studio'));
        }

        return $article;
    }

    /**
     * Optimize image prompt for DALL-E
     *
     * @param string $user_prompt User's prompt
     * @param array $context Additional context
     * @return string|WP_Error
     */
    public function optimize_image_prompt($user_prompt, $context = []) {
        $prompt = Prompts_Library::get_image_prompt_optimizer($user_prompt, $context);

        $response = $this->call_api($prompt, 500);

        if (is_wp_error($response)) {
            return $response;
        }

        return trim($response);
    }

    /**
     * Generate hashtags
     *
     * @param string $content Post content
     * @param string $platform Platform name
     * @param int $count Number of hashtags
     * @return array|WP_Error
     */
    public function generate_hashtags($content, $platform, $count = 10) {
        $prompt = Prompts_Library::get_hashtag_prompt($content, $platform, $count);

        $response = $this->call_api($prompt, 1000);

        if (is_wp_error($response)) {
            return $response;
        }

        // Clean and parse JSON response
        $clean_response = $this->clean_json_response($response);
        $data = json_decode($clean_response, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['hashtags'])) {
            Logger::error('Failed to parse hashtags JSON', [
                'json_error' => json_last_error_msg()
            ], 'api');
            return new \WP_Error('json_parse_error', __('Erreur de parsing JSON', 'ai-content-studio'));
        }

        return $data['hashtags'];
    }

    /**
     * Generate post ideas based on previous posts
     *
     * @param array $params Parameters with recent_posts, profile, platform
     * @return array|WP_Error
     */
    public function generate_post_ideas($params) {
        $prompt = Prompts_Library::get_post_ideas_prompt($params);

        $response = $this->call_api($prompt, 1500);

        if (is_wp_error($response)) {
            return $response;
        }

        // Clean and parse JSON response
        $clean_response = $this->clean_json_response($response);
        $data = json_decode($clean_response, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['ideas'])) {
            Logger::error('Failed to parse post ideas JSON', [
                'json_error' => json_last_error_msg()
            ], 'api');
            return new \WP_Error('json_parse_error', __('Erreur de parsing JSON', 'ai-content-studio'));
        }

        return $data['ideas'];
    }

    /**
     * Analyze trend relevance
     *
     * @param string $trend Trend keyword
     * @param array $profile Business profile
     * @return array|WP_Error
     */
    public function analyze_trend($trend, $profile) {
        $prompt = Prompts_Library::get_trend_analysis_prompt($trend, $profile);

        $response = $this->call_api($prompt, 2048);

        if (is_wp_error($response)) {
            return $response;
        }

        // Clean and parse JSON response
        $clean_response = $this->clean_json_response($response);
        $analysis = json_decode($clean_response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('Failed to parse trend analysis JSON', [
                'json_error' => json_last_error_msg()
            ], 'api');
            return new \WP_Error('json_parse_error', __('Erreur de parsing JSON', 'ai-content-studio'));
        }

        return $analysis;
    }

    /**
     * Analyze SEO
     *
     * @param string $content Article content
     * @param string $keyword Target keyword
     * @return array|WP_Error
     */
    public function analyze_seo($content, $keyword) {
        $prompt = Prompts_Library::get_seo_analysis_prompt($content, $keyword);

        $response = $this->call_api($prompt, 2048);

        if (is_wp_error($response)) {
            return $response;
        }

        // Clean and parse JSON response
        $clean_response = $this->clean_json_response($response);
        $analysis = json_decode($clean_response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('Failed to parse SEO analysis JSON', [
                'json_error' => json_last_error_msg()
            ], 'api');
            return new \WP_Error('json_parse_error', __('Erreur de parsing JSON', 'ai-content-studio'));
        }

        return $analysis;
    }

    /**
     * Clean JSON response from Claude (remove markdown code blocks)
     *
     * @param string $response Raw response from Claude
     * @return string Cleaned JSON string
     */
    private function clean_json_response($response) {
        // Remove markdown code blocks (```json ... ``` or ``` ... ```)
        $cleaned = preg_replace('/^```(?:json)?\s*\n/m', '', $response);
        $cleaned = preg_replace('/\n```\s*$/m', '', $cleaned);

        return trim($cleaned);
    }

    /**
     * Call Claude API
     *
     * @param string $user_message User message
     * @param int $max_tokens Maximum tokens
     * @param string $system_prompt System prompt
     * @return string|WP_Error
     */
    private function call_api($user_message, $max_tokens = 4096, $system_prompt = '') {
        if (empty($this->api_key)) {
            Logger::error('Claude API key not configured', [], 'api');
            return new \WP_Error('no_api_key', __('Clé API Claude non configurée', 'ai-content-studio'));
        }

        if (empty($system_prompt)) {
            $system_prompt = 'Tu es un assistant expert en création de contenu. Tu fournis toujours des réponses au format JSON valide quand demandé.';
        }

        $body = [
            'model' => $this->model,
            'max_tokens' => $max_tokens,
            'system' => $system_prompt,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $user_message,
                ],
            ],
        ];

        Logger::info('Calling Claude API', [
            'model' => $this->model,
            'max_tokens' => $max_tokens,
        ], 'api');

        $attempt = 0;

        while ($attempt < $this->max_retries) {
            $attempt++;

            $response = wp_remote_post($this->api_url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->api_key,
                    'anthropic-version' => '2023-06-01',
                ],
                'body' => wp_json_encode($body),
                'timeout' => 60,
            ]);

            if (is_wp_error($response)) {
                Logger::error('Claude API request failed', [
                    'attempt' => $attempt,
                    'error' => $response->get_error_message(),
                ], 'api');

                if ($attempt >= $this->max_retries) {
                    return $response;
                }

                sleep(2 * $attempt);
                continue;
            }

            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            if ($status_code !== 200) {
                Logger::error('Claude API returned error', [
                    'status_code' => $status_code,
                    'response' => substr($response_body, 0, 500), // Limit response body length
                    'attempt' => $attempt,
                ], 'api');

                if ($attempt >= $this->max_retries) {
                    return new \WP_Error('api_error', sprintf(
                        __('Erreur API Claude (%d)', 'ai-content-studio'),
                        $status_code
                    ));
                }

                sleep(2 * $attempt);
                continue;
            }

            $data = json_decode($response_body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Logger::error('Failed to parse Claude API response', ['response' => substr($response_body, 0, 500)], 'api');
                return new \WP_Error('json_error', __('Erreur de parsing de la réponse API', 'ai-content-studio'));
            }

            if (!isset($data['content'][0]['text'])) {
                Logger::error('Unexpected Claude API response format', ['data' => $data], 'api');
                return new \WP_Error('unexpected_format', __('Format de réponse inattendu', 'ai-content-studio'));
            }

            $text = $data['content'][0]['text'];

            Logger::info('Claude API call successful', [
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            ], 'api');

            return $text;
        }

        return new \WP_Error('max_retries', __('Nombre maximum de tentatives atteint', 'ai-content-studio'));
    }
}
