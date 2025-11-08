<?php
/**
 * DALL-E Image Generation Service
 *
 * @package ACS\Services
 */

namespace ACS\Services;

use ACS\Utils\Logger;
use ACS\Utils\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dalle_Service class
 */
class Dalle_Service {

    private $api_key;
    private $api_url = 'https://api.openai.com/v1/images/generations';

    public function __construct() {
        $this->api_key = get_option('acs_dalle_api_key', '');
    }

    /**
     * Generate image
     *
     * @param string $prompt Image prompt
     * @param string $size Image size (1024x1024, 1792x1024, 1024x1792)
     * @param string $quality Quality (standard, hd)
     * @param string $style Style (vivid, natural)
     * @return array|WP_Error
     */
    public function generate_image($prompt, $size = '1024x1024', $quality = 'standard', $style = 'natural') {
        if (empty($this->api_key)) {
            return new \WP_Error('no_api_key', __('Clé API DALL-E non configurée', 'ai-content-studio'));
        }

        // Optimize prompt if needed using Claude
        $claude_service = new Claude_Service();
        $optimized_prompt = $claude_service->optimize_image_prompt($prompt, compact('style', 'size'));

        if (is_wp_error($optimized_prompt)) {
            $optimized_prompt = $prompt; // Fallback to original
        }

        $body = [
            'model' => 'dall-e-3',
            'prompt' => $optimized_prompt,
            'n' => 1,
            'size' => $size,
            'quality' => $quality,
            'style' => $style,
        ];

        $response = wp_remote_post($this->api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'body' => wp_json_encode($body),
            'timeout' => 120,
        ]);

        if (is_wp_error($response)) {
            Logger::error('DALL-E API request failed', ['error' => $response->get_error_message()]);
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($status_code !== 200) {
            Logger::error('DALL-E API error', ['status' => $status_code, 'response' => $response_body]);
            return new \WP_Error('api_error', sprintf(__('Erreur API DALL-E (%d)', 'ai-content-studio'), $status_code));
        }

        $data = json_decode($response_body, true);

        if (!isset($data['data'][0]['url'])) {
            return new \WP_Error('unexpected_format', __('Format de réponse inattendu', 'ai-content-studio'));
        }

        $image_url = $data['data'][0]['url'];

        // Upload to WordPress media library
        $attachment_id = $this->upload_to_media_library($image_url, $prompt);

        return [
            'url' => $image_url,
            'optimized_prompt' => $optimized_prompt,
            'attachment_id' => $attachment_id,
            'size' => $size,
            'quality' => $quality,
            'style' => $style,
        ];
    }

    /**
     * Upload image to WordPress media library
     *
     * @param string $image_url Remote image URL
     * @param string $description Image description
     * @return int|null Attachment ID
     */
    private function upload_to_media_library($image_url, $description) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($image_url);

        if (is_wp_error($tmp)) {
            Logger::error('Failed to download image', ['error' => $tmp->get_error_message()]);
            return null;
        }

        $file_array = [
            'name' => 'ai-generated-' . time() . '.png',
            'tmp_name' => $tmp,
        ];

        $id = media_handle_sideload($file_array, 0, $description);

        if (is_wp_error($id)) {
            @unlink($tmp);
            Logger::error('Failed to upload to media library', ['error' => $id->get_error_message()]);
            return null;
        }

        return $id;
    }
}
