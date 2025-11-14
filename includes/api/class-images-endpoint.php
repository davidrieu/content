<?php
/**
 * Images REST API Endpoint
 *
 * @package ACS
 * @subpackage API
 */

namespace ACS\API;

use ACS\Services\Dalle_Service;
use ACS\Services\Subscription_Service;
use ACS\Services\Usage_Service;
use ACS\Utils\Logger;
use WP_REST_Request;
use WP_Error;

/**
 * Class Images_Endpoint
 */
class Images_Endpoint extends REST_Controller {

    /**
     * Register routes
     */
    public function register_routes() {
        // Generate image
        register_rest_route($this->namespace, '/images/generate', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'generate_image'],
                'permission_callback' => [$this, 'permission_check'],
                'args'                => [
                    'prompt' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                        'validate_callback' => function($value) {
                            return !empty($value) && strlen($value) <= 4000;
                        }
                    ],
                    'format' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function($value) {
                            $allowed = [
                                'instagram_post', 'instagram_story', 'facebook_post',
                                'linkedin_post', 'twitter_post', 'blog_featured',
                                'blog_banner', 'custom_square', 'custom_landscape', 'custom_portrait'
                            ];
                            return in_array($value, $allowed, true);
                        }
                    ],
                    'quality' => [
                        'type'              => 'string',
                        'default'           => 'standard',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function($value) {
                            return in_array($value, ['standard', 'hd'], true);
                        }
                    ],
                    'style' => [
                        'type'              => 'string',
                        'default'           => 'vivid',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function($value) {
                            return in_array($value, ['vivid', 'natural'], true);
                        }
                    ],
                    'title' => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ]
                ],
            ],
        ]);

        // Get images list
        register_rest_route($this->namespace, '/images', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_images'],
                'permission_callback' => [$this, 'permission_check'],
                'args'                => [
                    'per_page' => [
                        'type'              => 'integer',
                        'default'           => 20,
                        'sanitize_callback' => 'absint',
                    ],
                    'page' => [
                        'type'              => 'integer',
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ]);

        // Get single image
        register_rest_route($this->namespace, '/images/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_image'],
                'permission_callback' => [$this, 'permission_check'],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ]);

        // Delete image
        register_rest_route($this->namespace, '/images/(?P<id>\d+)', [
            [
                'methods'             => 'DELETE',
                'callback'            => [$this, 'delete_image'],
                'permission_callback' => [$this, 'permission_check'],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ]);

        // Get formats list
        register_rest_route($this->namespace, '/images/formats', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_formats'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);
    }

    /**
     * Generate image
     *
     * @param WP_REST_Request $request Request object.
     * @return array|WP_Error
     */
    public function generate_image($request) {
        try {
            $user_id = $this->get_current_user_id();

            // Check subscription limits
            $subscription_service = new Subscription_Service();
            if (!$subscription_service->can_generate_image($user_id)) {
                Logger::warning('Image generation limit reached', [
                    'user_id' => $user_id,
                ], 'images');

                return $this->error(
                    __('Limite d\'images atteinte pour votre plan. Veuillez passer à un plan supérieur.', 'ai-content-studio'),
                    'limit_reached',
                    403
                );
            }

            $prompt  = $request->get_param('prompt');
            $format  = $request->get_param('format');
            $quality = $request->get_param('quality') ?? 'standard';
            $style   = $request->get_param('style') ?? 'vivid';
            $title   = $request->get_param('title') ?? '';

            // Convert format to DALL-E size
            $size = $this->format_to_size($format);

            Logger::info('Generating image', [
                'user_id' => $user_id,
                'format'  => $format,
                'size'    => $size,
                'quality' => $quality,
                'style'   => $style,
            ], 'images');

            // Generate image with DALL-E
            $dalle_service = new Dalle_Service();
            $result = $dalle_service->generate_image($prompt, $size, $quality, $style);

            if (is_wp_error($result)) {
                Logger::error('Image generation failed', [
                    'user_id' => $user_id,
                    'error'   => $result->get_error_message(),
                ], 'images');

                return $this->error(
                    $result->get_error_message(),
                    'generation_failed',
                    500
                );
            }

            // Save to database
            global $wpdb;
            $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'generated_images';

            $data = [
                'user_id'    => $user_id,
                'title'      => !empty($title) ? $title : substr($prompt, 0, 100),
                'url'        => $result['url'],
                'prompt'     => $prompt,
                'size'       => $size,
                'quality'    => $quality,
                'style'      => $style,
                'media_id'   => $result['media_id'],
                'status'     => 'success',
                'created_at' => current_time('mysql'),
            ];

            $wpdb->insert($table, $data);
            $image_id = $wpdb->insert_id;

            // Update usage stats
            $usage_service = new Usage_Service();
            $usage_service->increment_image_usage($user_id);

            Logger::info('Image generated successfully', [
                'user_id'  => $user_id,
                'image_id' => $image_id,
            ], 'images');

            return $this->success([
                'image' => array_merge(['id' => $image_id], $data),
                'message' => __('Image générée avec succès !', 'ai-content-studio'),
            ]);

        } catch (\Exception $e) {
            Logger::error('Image generation exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 'images');

            return $this->error(
                __('Une erreur est survenue lors de la génération.', 'ai-content-studio'),
                'exception',
                500
            );
        }
    }

    /**
     * Get images list
     *
     * @param WP_REST_Request $request Request object.
     * @return array
     */
    public function get_images($request) {
        global $wpdb;

        $user_id  = $this->get_current_user_id();
        $per_page = $request->get_param('per_page') ?? 20;
        $page     = $request->get_param('page') ?? 1;
        $offset   = ($page - 1) * $per_page;

        $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'generated_images';

        // Get images
        $images = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE user_id = %d
             ORDER BY created_at DESC
             LIMIT %d OFFSET %d",
            $user_id,
            $per_page,
            $offset
        ), ARRAY_A);

        // Get total count
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
            $user_id
        ));

        return $this->success([
            'images' => $images,
            'pagination' => [
                'total'       => (int) $total,
                'per_page'    => $per_page,
                'current_page' => $page,
                'total_pages' => ceil($total / $per_page),
            ],
        ]);
    }

    /**
     * Get single image
     *
     * @param WP_REST_Request $request Request object.
     * @return array|WP_Error
     */
    public function get_image($request) {
        global $wpdb;

        $user_id  = $this->get_current_user_id();
        $image_id = $request->get_param('id');

        $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'generated_images';

        $image = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d AND user_id = %d",
            $image_id,
            $user_id
        ), ARRAY_A);

        if (!$image) {
            return $this->error(
                __('Image non trouvée.', 'ai-content-studio'),
                'not_found',
                404
            );
        }

        return $this->success(['image' => $image]);
    }

    /**
     * Delete image
     *
     * @param WP_REST_Request $request Request object.
     * @return array|WP_Error
     */
    public function delete_image($request) {
        global $wpdb;

        $user_id  = $this->get_current_user_id();
        $image_id = $request->get_param('id');

        $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'generated_images';

        // Check ownership
        $image = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d AND user_id = %d",
            $image_id,
            $user_id
        ), ARRAY_A);

        if (!$image) {
            return $this->error(
                __('Image non trouvée.', 'ai-content-studio'),
                'not_found',
                404
            );
        }

        // Delete from WordPress media library if exists
        if (!empty($image['media_id'])) {
            wp_delete_attachment($image['media_id'], true);
        }

        // Delete from database
        $wpdb->delete($table, ['id' => $image_id], ['%d']);

        Logger::info('Image deleted', [
            'user_id'  => $user_id,
            'image_id' => $image_id,
        ], 'images');

        return $this->success([
            'message' => __('Image supprimée avec succès.', 'ai-content-studio'),
        ]);
    }

    /**
     * Get available formats
     *
     * @return array
     */
    public function get_formats() {
        $formats = [
            // Social Media
            [
                'id'          => 'instagram_post',
                'name'        => 'Instagram Post',
                'category'    => 'social',
                'size'        => '1024x1024',
                'description' => 'Format carré parfait pour les posts Instagram',
                'icon'        => 'instagram',
            ],
            [
                'id'          => 'instagram_story',
                'name'        => 'Instagram Story',
                'category'    => 'social',
                'size'        => '1024x1792',
                'description' => 'Format vertical pour les stories Instagram',
                'icon'        => 'instagram',
            ],
            [
                'id'          => 'facebook_post',
                'name'        => 'Facebook Post',
                'category'    => 'social',
                'size'        => '1792x1024',
                'description' => 'Format paysage pour les posts Facebook',
                'icon'        => 'facebook',
            ],
            [
                'id'          => 'linkedin_post',
                'name'        => 'LinkedIn Post',
                'category'    => 'social',
                'size'        => '1792x1024',
                'description' => 'Format professionnel pour LinkedIn',
                'icon'        => 'linkedin',
            ],
            [
                'id'          => 'twitter_post',
                'name'        => 'Twitter/X Post',
                'category'    => 'social',
                'size'        => '1792x1024',
                'description' => 'Format optimal pour Twitter/X',
                'icon'        => 'twitter',
            ],

            // Blog
            [
                'id'          => 'blog_featured',
                'name'        => 'Image à la Une',
                'category'    => 'blog',
                'size'        => '1792x1024',
                'description' => 'Image featured pour article de blog',
                'icon'        => 'file-text',
            ],
            [
                'id'          => 'blog_banner',
                'name'        => 'Bannière Blog',
                'category'    => 'blog',
                'size'        => '1792x1024',
                'description' => 'Grande bannière pour en-tête d\'article',
                'icon'        => 'image',
            ],

            // Custom
            [
                'id'          => 'custom_square',
                'name'        => 'Carré Personnalisé',
                'category'    => 'custom',
                'size'        => '1024x1024',
                'description' => 'Format carré polyvalent',
                'icon'        => 'square',
            ],
            [
                'id'          => 'custom_landscape',
                'name'        => 'Paysage Personnalisé',
                'category'    => 'custom',
                'size'        => '1792x1024',
                'description' => 'Format horizontal',
                'icon'        => 'maximize',
            ],
            [
                'id'          => 'custom_portrait',
                'name'        => 'Portrait Personnalisé',
                'category'    => 'custom',
                'size'        => '1024x1792',
                'description' => 'Format vertical',
                'icon'        => 'smartphone',
            ],
        ];

        return $this->success(['formats' => $formats]);
    }

    /**
     * Convert format to DALL-E size
     *
     * @param string $format Format ID.
     * @return string DALL-E size.
     */
    private function format_to_size($format) {
        $map = [
            'instagram_post'     => '1024x1024',
            'instagram_story'    => '1024x1792',
            'facebook_post'      => '1792x1024',
            'linkedin_post'      => '1792x1024',
            'twitter_post'       => '1792x1024',
            'blog_featured'      => '1792x1024',
            'blog_banner'        => '1792x1024',
            'custom_square'      => '1024x1024',
            'custom_landscape'   => '1792x1024',
            'custom_portrait'    => '1024x1792',
        ];

        return $map[$format] ?? '1024x1024';
    }
}
