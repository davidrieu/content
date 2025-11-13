<?php
/**
 * Blog Articles Endpoint
 *
 * @package ACS
 */

namespace ACS\API;

use ACS\Services\Claude_Service;
use WP_REST_Request;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Blog_Endpoint class
 */
class Blog_Endpoint extends REST_Controller {

    /**
     * Claude service instance
     *
     * @var Claude_Service
     */
    private $claude;

    /**
     * Constructor
     */
    public function __construct() {
        $this->claude = new Claude_Service();
    }

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes() {
        // Generate title and keywords suggestions
        register_rest_route('acs/v1', '/blog/generate-suggestions', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_suggestions'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Generate article with streaming
        register_rest_route('acs/v1', '/blog/generate-article', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_article_stream'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Generate SEO meta
        register_rest_route('acs/v1', '/blog/generate-seo', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_seo_meta'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Save article
        register_rest_route('acs/v1', '/blog/save-article', [
            'methods' => 'POST',
            'callback' => [$this, 'save_article'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Get articles list
        register_rest_route('acs/v1', '/blog/articles', [
            'methods' => 'GET',
            'callback' => [$this, 'get_articles'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Delete article
        register_rest_route('acs/v1', '/blog/articles/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_article'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Generate blog strategy ideas (50 subjects)
        register_rest_route('acs/v1', '/blog/strategy-ideas', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_blog_strategy_ideas'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Get current blog strategy
        register_rest_route('acs/v1', '/blog/strategy/current', [
            'methods' => 'GET',
            'callback' => [$this, 'get_current_blog_strategy'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Save blog strategy
        register_rest_route('acs/v1', '/blog/strategy/save', [
            'methods' => 'POST',
            'callback' => [$this, 'save_blog_strategy'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Migration endpoint (temporary) - Create blog_strategies table
        register_rest_route('acs/v1', '/blog/migrate-table', [
            'methods' => 'POST',
            'callback' => [$this, 'migrate_blog_strategies_table'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * Check permissions
     *
     * @return bool
     */
    public function check_permissions() {
        return is_user_logged_in();
    }

    /**
     * Generate title and keywords suggestions (using Haiku for speed)
     *
     * @param WP_REST_Request $request
     * @return array
     */
    public function generate_suggestions($request) {
        $subject = sanitize_text_field($request->get_param('subject'));
        $language = sanitize_text_field($request->get_param('language') ?: 'fr');

        if (empty($subject)) {
            return rest_ensure_response([
                'success' => false,
                'message' => __('Le sujet est requis', 'ai-content-studio'),
            ]);
        }

        // Language instructions - Complete mapping
        $language_names = [
            'fr' => 'français', 'en' => 'anglais', 'es' => 'espagnol', 'pt' => 'portugais',
            'de' => 'allemand', 'it' => 'italien', 'zh' => 'chinois (mandarin)', 'ja' => 'japonais',
            'ko' => 'coréen', 'ar' => 'arabe', 'ru' => 'russe', 'hi' => 'hindi',
            'bn' => 'bengali', 'id' => 'indonésien', 'tr' => 'turc', 'vi' => 'vietnamien',
            'pl' => 'polonais', 'uk' => 'ukrainien', 'nl' => 'néerlandais', 'th' => 'thaï',
            'sv' => 'suédois', 'el' => 'grec', 'cs' => 'tchèque', 'ro' => 'roumain',
            'hu' => 'hongrois', 'da' => 'danois', 'fi' => 'finnois', 'no' => 'norvégien',
            'he' => 'hébreu', 'ca' => 'catalan'
        ];
        $language_name = $language_names[$language] ?? 'français';

        $prompt = "Tu es un expert en rédaction de contenu et SEO.

Sujet de l'article : {$subject}

LANGUE : Génère le titre et les mots-clés en {$language_name}.

Ta tâche :
1. Génère un titre d'article de blog accrocheur et optimisé SEO (60-70 caractères max)
2. Génère exactement 5 mots-clés pertinents pour ce sujet (le premier sera le mot-clé principal)

RÈGLES STRICTES :
- Réponds UNIQUEMENT avec un objet JSON
- N'ajoute AUCUN texte avant ou après le JSON
- N'utilise PAS de bloc de code markdown
- Respecte EXACTEMENT ce format :

{\"title\":\"Le titre ici\",\"keywords\":[\"mot-clé 1\",\"mot-clé 2\",\"mot-clé 3\",\"mot-clé 4\",\"mot-clé 5\"]}

Réponds maintenant avec le JSON uniquement :";

        // Use Haiku for speed
        $response = $this->claude->generate_completion(
            $prompt,
            'claude-3-5-haiku-20241022',
            1024
        );

        if (is_wp_error($response)) {
            return rest_ensure_response([
                'success' => false,
                'message' => $response->get_error_message(),
            ]);
        }

        // $response is already the text content, not an array
        $content = $response;

        // Log the raw response for debugging
        error_log('Claude raw response: ' . $content);

        // Try to extract JSON from response (handle markdown code blocks)
        $json_str = $content;

        // Remove markdown code blocks if present
        $json_str = preg_replace('/```json\s*/i', '', $json_str);
        $json_str = preg_replace('/```\s*$/i', '', $json_str);

        // Extract JSON object
        if (preg_match('/\{[^}]*"title"[^}]*"keywords"[^}]*\}/s', $json_str, $matches)) {
            $json_str = $matches[0];
        } elseif (preg_match('/\{.*\}/s', $json_str, $matches)) {
            $json_str = $matches[0];
        }

        // Trim whitespace
        $json_str = trim($json_str);

        error_log('Extracted JSON: ' . $json_str);

        $data = json_decode($json_str, true);

        if (!$data || !isset($data['title']) || !isset($data['keywords'])) {
            error_log('JSON decode error: ' . json_last_error_msg());
            return rest_ensure_response([
                'success' => false,
                'message' => __('Impossible de parser la réponse. Réponse reçue: ', 'ai-content-studio') . substr($content, 0, 200),
            ]);
        }

        // Validate keywords is an array
        if (!is_array($data['keywords']) || count($data['keywords']) !== 5) {
            return rest_ensure_response([
                'success' => false,
                'message' => __('Le format des mots-clés est invalide', 'ai-content-studio'),
            ]);
        }

        return rest_ensure_response([
            'success' => true,
            'data' => [
                'title' => $data['title'],
                'keywords' => $data['keywords'],
            ],
        ]);
    }

    /**
     * Generate article with streaming (SSE)
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function generate_article_stream($request) {
        // VÉRIFICATION LIMITE CRITIQUE
        $user_id = get_current_user_id();
        $subscription_service = new \ACS\Services\Subscription_Service();

        if (!$subscription_service->can_generate_article($user_id)) {
            $plan = $subscription_service->get_user_plan($user_id);
            $limits = $subscription_service->get_plan_limits($plan);

            // Send error event for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');

            echo "event: error\n";
            echo "data: " . wp_json_encode([
                'type' => 'error',
                'code' => 'limit_reached',
                'message' => sprintf(__('Vous avez atteint votre limite de %d articles pour le plan %s. Passez à un plan supérieur.', 'ai-content-studio'), $limits['articles_per_month'] ?? 0, $plan),
            ]) . "\n\n";
            flush();
            exit;
        }

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable nginx buffering

        // Get parameters
        $title = sanitize_text_field($request->get_param('title'));
        $keywords = $request->get_param('keywords');
        $main_keyword = sanitize_text_field($request->get_param('main_keyword'));
        $first_person = (bool) $request->get_param('first_person');
        $length = sanitize_text_field($request->get_param('length'));
        $language = sanitize_text_field($request->get_param('language') ?: 'fr');

        // Parse length range
        list($min_words, $max_words) = explode('-', $length);
        $target_words = (int) $max_words;

        // Calculate keyword density (aim for 1-2%)
        $keyword_occurrences = max(2, floor($target_words * 0.015)); // 1.5%

        // Build prompt
        $keywords_list = implode(', ', $keywords);
        $point_of_view = $first_person ? 'première personne (je, nous)' : 'troisième personne';

        // Language instructions - Complete mapping
        $language_names = [
            'fr' => 'français', 'en' => 'anglais', 'es' => 'espagnol', 'pt' => 'portugais',
            'de' => 'allemand', 'it' => 'italien', 'zh' => 'chinois (mandarin)', 'ja' => 'japonais',
            'ko' => 'coréen', 'ar' => 'arabe', 'ru' => 'russe', 'hi' => 'hindi',
            'bn' => 'bengali', 'id' => 'indonésien', 'tr' => 'turc', 'vi' => 'vietnamien',
            'pl' => 'polonais', 'uk' => 'ukrainien', 'nl' => 'néerlandais', 'th' => 'thaï',
            'sv' => 'suédois', 'el' => 'grec', 'cs' => 'tchèque', 'ro' => 'roumain',
            'hu' => 'hongrois', 'da' => 'danois', 'fi' => 'finnois', 'no' => 'norvégien',
            'he' => 'hébreu', 'ca' => 'catalan'
        ];
        $language_name = $language_names[$language] ?? 'français';

        $prompt = "Tu es un rédacteur web expert en SEO.

LANGUE : Rédige l'article ENTIÈREMENT en {$language_name}.

CONSIGNES :
- Titre de l'article : {$title}
- Mots-clés à intégrer naturellement : {$keywords_list}
- Mot-clé principal (à utiliser environ {$keyword_occurrences} fois) : {$main_keyword}
- Point de vue : {$point_of_view}
- Longueur cible : {$target_words} mots (entre {$min_words} et {$max_words} mots)

STRUCTURE OBLIGATOIRE :
1. Introduction engageante (10% du texte)
2. 3-5 sections principales avec sous-titres H2 (utilise ## devant le titre)
3. Chaque section peut avoir des sous-sections H3 (utilise ### devant le titre)
4. Chaque section doit contenir 2-3 paragraphes
5. Conclusion avec appel à l'action

RÈGLES SEO :
- Densité du mot-clé principal : 1-2% (environ {$keyword_occurrences} occurrences)
- Intégrer les mots-clés secondaires naturellement
- Utiliser des variations sémantiques
- Phrases courtes et claires
- Paragraphes de 3-5 lignes maximum

FORMAT DE RÉPONSE :
- Utilise ## pour les titres de section (H2)
- Utilise ### pour les sous-titres (H3)
- Sauts de ligne entre les paragraphes
- Texte brut sans balises HTML
- Ne mets PAS de titre H1 au début (il est déjà affiché)

EXEMPLE DE FORMAT :
Introduction ici...

## Première section

Paragraphe 1 de la section...

Paragraphe 2 de la section...

### Sous-section si nécessaire

Contenu de la sous-section...

## Deuxième section

Etc.

Commence la rédaction maintenant :";

        // Add images if requested
        $images_inserted = [];

        // Stream the response
        try {
            $section_count = 0;
            $last_content = '';

            $this->claude->stream_completion(
                $prompt,
                'claude-sonnet-4-20250514', // Sonnet 4
                $target_words * 5, // Tokens approximation
                function($chunk) use ($include_images, &$images_inserted, &$section_count, &$last_content, $keywords) {
                    if (isset($chunk['type'])) {
                        if ($chunk['type'] === 'content_block_delta') {
                            $text = $chunk['delta']['text'] ?? '';
                            if (!empty($text)) {
                                $last_content .= $text;

                                // Check if we just completed a H2 section and should insert an image
                                if ($include_images && preg_match('/##\s+.+\n/', $last_content, $matches)) {
                                    $section_count++;

                                    // Insert image after every 2nd H2 section
                                    if ($section_count % 2 === 0 && count($images_inserted) < 3) {
                                        $image_data = $this->fetch_unsplash_image($keywords[0]);
                                        if ($image_data) {
                                            $images_inserted[] = $image_data;
                                            $text .= "\n\n[IMAGE: " . $image_data['url'] . "]\nPhoto par " . $image_data['author'] . " sur Unsplash\n\n";
                                        }
                                    }
                                }

                                echo "data: " . json_encode([
                                    'type' => 'content',
                                    'content' => $text
                                ]) . "\n\n";

                                if (ob_get_level() > 0) {
                                    ob_flush();
                                }
                                flush();
                            }
                        } elseif ($chunk['type'] === 'message_stop') {
                            // Incrémenter le compteur d'articles générés
                            $usage_service = new \ACS\Services\Usage_Service();
                            $usage_service->increment_article_usage(get_current_user_id());

                            echo "data: " . json_encode(['type' => 'done']) . "\n\n";

                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();
                        }
                    }
                }
            );
        } catch (\Exception $e) {
            echo "data: " . json_encode([
                'type' => 'error',
                'message' => $e->getMessage()
            ]) . "\n\n";

            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }

        exit;
    }

    /**
     * Generate SEO meta (title, description, URL)
     *
     * @param WP_REST_Request $request
     * @return array
     */
    public function generate_seo_meta($request) {
        $title = sanitize_text_field($request->get_param('title'));
        $content = $request->get_param('content');
        $main_keyword = sanitize_text_field($request->get_param('main_keyword'));
        $language = sanitize_text_field($request->get_param('language') ?: 'fr');

        // Truncate content for prompt (first 500 words)
        $content_words = explode(' ', $content);
        $content_excerpt = implode(' ', array_slice($content_words, 0, 500));

        // Language instructions - Complete mapping
        $language_names = [
            'fr' => 'français', 'en' => 'anglais', 'es' => 'espagnol', 'pt' => 'portugais',
            'de' => 'allemand', 'it' => 'italien', 'zh' => 'chinois (mandarin)', 'ja' => 'japonais',
            'ko' => 'coréen', 'ar' => 'arabe', 'ru' => 'russe', 'hi' => 'hindi',
            'bn' => 'bengali', 'id' => 'indonésien', 'tr' => 'turc', 'vi' => 'vietnamien',
            'pl' => 'polonais', 'uk' => 'ukrainien', 'nl' => 'néerlandais', 'th' => 'thaï',
            'sv' => 'suédois', 'el' => 'grec', 'cs' => 'tchèque', 'ro' => 'roumain',
            'hu' => 'hongrois', 'da' => 'danois', 'fi' => 'finnois', 'no' => 'norvégien',
            'he' => 'hébreu', 'ca' => 'catalan'
        ];
        $language_name = $language_names[$language] ?? 'français';

        $prompt = "Tu es un expert SEO.

Titre de l'article : {$title}
Mot-clé principal : {$main_keyword}
Début de l'article : {$content_excerpt}...

LANGUE : Génère les éléments SEO en {$language_name}.

Ta tâche :
1. Génère un titre SEO optimisé (55-60 caractères, avec le mot-clé)
2. Génère une meta description engageante (150-160 caractères, avec le mot-clé)
3. Génère un slug d'URL optimisé (mots-clés séparés par des tirets, en minuscules)

RÈGLES STRICTES :
- Réponds UNIQUEMENT avec un objet JSON
- N'ajoute AUCUN texte avant ou après le JSON
- N'utilise PAS de bloc de code markdown
- Respecte EXACTEMENT ce format :

{\"seo_title\":\"Le titre SEO ici\",\"meta_description\":\"La meta description ici\",\"url_slug\":\"le-slug-url-ici\"}

Réponds maintenant avec le JSON uniquement :";

        // Use Haiku for speed
        $response = $this->claude->generate_completion(
            $prompt,
            'claude-3-5-haiku-20241022',
            1024
        );

        if (is_wp_error($response)) {
            return rest_ensure_response([
                'success' => false,
                'message' => $response->get_error_message(),
            ]);
        }

        // $response is already the text content, not an array
        $response_text = $response;

        // Log the raw response for debugging
        error_log('Claude SEO raw response: ' . $response_text);

        // Try to extract JSON from response (handle markdown code blocks)
        $json_str = $response_text;

        // Remove markdown code blocks if present
        $json_str = preg_replace('/```json\s*/i', '', $json_str);
        $json_str = preg_replace('/```\s*$/i', '', $json_str);

        // Extract JSON object
        if (preg_match('/\{[^}]*"seo_title"[^}]*"meta_description"[^}]*"url_slug"[^}]*\}/s', $json_str, $matches)) {
            $json_str = $matches[0];
        } elseif (preg_match('/\{.*\}/s', $json_str, $matches)) {
            $json_str = $matches[0];
        }

        // Trim whitespace
        $json_str = trim($json_str);

        error_log('Extracted SEO JSON: ' . $json_str);

        $data = json_decode($json_str, true);

        if (!$data || !isset($data['seo_title']) || !isset($data['meta_description']) || !isset($data['url_slug'])) {
            error_log('SEO JSON decode error: ' . json_last_error_msg());
            return rest_ensure_response([
                'success' => false,
                'message' => __('Impossible de parser la réponse SEO. Réponse reçue: ', 'ai-content-studio') . substr($response_text, 0, 200),
            ]);
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Save article to database
     *
     * @param WP_REST_Request $request
     * @return array
     */
    public function save_article($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'acs_blog_articles';
        $user_id = $this->get_current_user_id();
        $project_id = $this->get_active_project_id();

        // Log incoming data for debugging
        error_log('Save article request - User ID: ' . $user_id);
        error_log('Save article request - Project ID: ' . $project_id);
        error_log('Save article request - Subject: ' . $request->get_param('subject'));
        error_log('Save article request - Title: ' . $request->get_param('title'));
        error_log('Save article request - Language: ' . $request->get_param('language'));
        error_log('Save article request - Keywords: ' . print_r($request->get_param('keywords'), true));

        $data = [
            'user_id' => $user_id,
            'project_id' => $project_id,
            'subject' => sanitize_text_field($request->get_param('subject')),
            'title' => sanitize_text_field($request->get_param('title')),
            'content' => wp_kses_post($request->get_param('content')),
            'keywords' => wp_json_encode($request->get_param('keywords')),
            'main_keyword' => sanitize_text_field($request->get_param('main_keyword')),
            'first_person' => (int) $request->get_param('first_person'),
            'word_count' => str_word_count($request->get_param('content')),
            'language' => sanitize_text_field($request->get_param('language') ?: 'fr'),
            'seo_title' => sanitize_text_field($request->get_param('seo_title') ?: ''),
            'meta_description' => sanitize_text_field($request->get_param('meta_description') ?: ''),
            'url_slug' => sanitize_title($request->get_param('url_slug') ?: ''),
            'created_at' => current_time('mysql'),
        ];

        error_log('Attempting to insert article data into: ' . $table);

        $result = $wpdb->insert($table, $data, [
            '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s'
        ]);

        // Log any errors with detailed information
        if ($result === false) {
            error_log('Blog article save FAILED!');
            error_log('SQL Error: ' . $wpdb->last_error);
            error_log('Last query: ' . $wpdb->last_query);

            return rest_ensure_response([
                'success' => false,
                'message' => __('Erreur lors de la sauvegarde: ', 'ai-content-studio') . $wpdb->last_error,
            ]);
        }

        $insert_id = $wpdb->insert_id;
        error_log('Article saved successfully with ID: ' . $insert_id);

        return rest_ensure_response([
            'success' => true,
            'data' => [
                'id' => $insert_id,
            ],
            'message' => __('Article sauvegardé avec succès', 'ai-content-studio'),
        ]);
    }

    /**
     * Get articles list for current user
     *
     * @param WP_REST_Request $request
     * @return array
     */
    public function get_articles($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'acs_blog_articles';
        $user_id = $this->get_current_user_id();
        $project_id = $this->get_active_project_id();

        // Filter by project_id
        if ($project_id) {
            $articles = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE user_id = %d AND project_id = %d
                 ORDER BY created_at DESC
                 LIMIT 50",
                $user_id,
                $project_id
            ), ARRAY_A);
        } else {
            $articles = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE user_id = %d AND project_id IS NULL
                 ORDER BY created_at DESC
                 LIMIT 50",
                $user_id
            ), ARRAY_A);
        }

        // Decode keywords
        foreach ($articles as &$article) {
            $article['keywords'] = json_decode($article['keywords'], true);
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $articles,
        ]);
    }

    /**
     * Delete an article
     *
     * @param WP_REST_Request $request
     * @return array
     */
    public function delete_article($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'acs_blog_articles';
        $article_id = $request->get_param('id');
        $user_id = get_current_user_id();

        // Verify article exists and belongs to user
        $article = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE id = %d AND user_id = %d",
            $article_id,
            $user_id
        ));

        if (!$article) {
            return rest_ensure_response([
                'success' => false,
                'message' => __('Article non trouvé ou vous n\'avez pas les permissions.', 'ai-content-studio'),
            ]);
        }

        // Delete the article
        $result = $wpdb->delete(
            $table,
            ['id' => $article_id, 'user_id' => $user_id],
            ['%d', '%d']
        );

        if ($result !== false) {
            return rest_ensure_response([
                'success' => true,
                'message' => __('Article supprimé avec succès.', 'ai-content-studio'),
            ]);
        } else {
            return rest_ensure_response([
                'success' => false,
                'message' => __('Erreur lors de la suppression de l\'article.', 'ai-content-studio'),
            ]);
        }
    }

    /**
     * Fetch image from Unsplash API
     *
     * @param string $keyword
     * @return array|null
     */
    private function fetch_unsplash_image($keyword) {
        // Unsplash Access Key (you need to add this in WordPress settings)
        $access_key = get_option('acs_unsplash_access_key', '');

        if (empty($access_key)) {
            error_log('Unsplash Access Key not configured');
            return null;
        }

        $url = 'https://api.unsplash.com/photos/random?' . http_build_query([
            'query' => $keyword,
            'orientation' => 'landscape',
            'content_filter' => 'high',
        ]);

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Client-ID ' . $access_key,
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            error_log('Unsplash API error: ' . $response->get_error_message());
            return null;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log('Unsplash API returned status: ' . $status_code);
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['urls']['regular'])) {
            error_log('Invalid Unsplash response');
            return null;
        }

        return [
            'url' => $data['urls']['regular'],
            'thumb' => $data['urls']['small'],
            'author' => $data['user']['name'],
            'author_url' => $data['user']['links']['html'],
            'download_location' => $data['links']['download_location'], // Required for attribution
        ];
    }

    /**
     * Generate 50 blog strategy ideas
     *
     * @param WP_REST_Request $request
     * @return array|WP_Error
     */
    public function generate_blog_strategy_ideas($request) {
        $params = $request->get_json_params();
        $profile = $params['profile'] ?? [];
        $language = sanitize_text_field($params['language'] ?? 'fr');
        $goal = sanitize_text_field($params['goal'] ?? 'engagement');

        // Extraire les informations du profil
        $user_type = $profile['user_type'] ?? 'business';
        $business_name = $profile['business_name'] ?? 'Business';
        $sector = $profile['sector'] ?? 'général';
        $description = $profile['description'] ?? '';
        $target_audience = $profile['target_audience'] ?? 'large public';
        $niche = $profile['niche'] ?? '';
        $keywords = is_array($profile['keywords']) ? implode(', ', $profile['keywords']) : '';
        $blog_topics = is_array($profile['blog_topics']) ? implode(', ', $profile['blog_topics']) : '';
        $goals = is_array($profile['goals']) ? implode(', ', $profile['goals']) : '';

        // Language names mapping
        $language_names = [
            'fr' => 'français', 'en' => 'anglais', 'es' => 'espagnol', 'pt' => 'portugais',
            'de' => 'allemand', 'it' => 'italien', 'zh' => 'chinois', 'ja' => 'japonais',
            'ko' => 'coréen', 'ar' => 'arabe', 'ru' => 'russe', 'hi' => 'hindi',
            'bn' => 'bengali', 'id' => 'indonésien', 'tr' => 'turc', 'vi' => 'vietnamien',
            'pl' => 'polonais', 'uk' => 'ukrainien', 'nl' => 'néerlandais', 'th' => 'thaï',
            'sv' => 'suédois', 'el' => 'grec', 'cs' => 'tchèque', 'ro' => 'roumain',
            'hu' => 'hongrois', 'da' => 'danois', 'fi' => 'finnois', 'no' => 'norvégien',
            'he' => 'hébreu', 'ca' => 'catalan'
        ];
        $language_name = $language_names[$language] ?? $language;

        // Goal descriptions
        $goal_descriptions = [
            'brand_awareness' => 'notoriété de marque et visibilité',
            'lead_generation' => 'génération de leads qualifiés',
            'sales' => 'augmentation des ventes',
            'engagement' => 'engagement et création de communauté',
            'authority' => 'positionnement en expert et leader d\'opinion',
        ];
        $goal_description = $goal_descriptions[$goal] ?? 'engagement';

        // Construire le contexte enrichi
        $context_parts = [];
        if (!empty($description)) {
            $context_parts[] = "Description: {$description}";
        }
        if (!empty($niche)) {
            $context_parts[] = "Niche: {$niche}";
        }
        if (!empty($blog_topics)) {
            $context_parts[] = "Sujets de blog préférés: {$blog_topics}";
        }
        if (!empty($goals)) {
            $context_parts[] = "Objectifs: {$goals}";
        }
        $context = !empty($context_parts) ? "\n" . implode("\n", $context_parts) : '';

        $prompt = <<<PROMPT
Tu es un expert en stratégie de contenu blog et SEO.

MISSION: Générer 50 sujets d'articles de blog percutants et optimisés SEO

PROFIL CLIENT:
- Type: {$user_type}
- Business: {$business_name}
- Secteur: {$sector}
- Audience cible: {$target_audience}
- Mots-clés SEO: {$keywords}
- Objectif principal: {$goal_description}{$context}

CONTRAINTES:
- 50 sujets d'articles de blog
- Sujets variés: guides pratiques, tutoriels, analyses, comparatifs, listes, études de cas
- Optimisés pour le SEO et l'engagement
- Adaptés au secteur, à la niche et à l'audience
- Titres accrocheurs et clairs (8-15 mots)
- Alignés avec l'objectif principal ({$goal_description})

IMPORTANT: Génère tous les sujets en {$language_name}.

GÉNÈRE exactement 50 sujets au format JSON avec cette structure:

{
    "ideas": [
        "Sujet 1",
        "Sujet 2",
        ...
        "Sujet 50"
    ]
}

IMPORTANT: Réponds UNIQUEMENT avec le JSON, sans texte avant ou après.
PROMPT;

        try {
            // Utiliser Haiku pour la rapidité
            $response = $this->claude->generate_completion(
                $prompt,
                'claude-3-5-haiku-20241022',
                2048
            );

            if (is_wp_error($response)) {
                error_log('Blog strategy ideas generation failed: ' . $response->get_error_message());
                return rest_ensure_response([
                    'success' => false,
                    'message' => $response->get_error_message(),
                ]);
            }

            // Parser la réponse
            $response = trim($response);
            $response = preg_replace('/^```json\s*/i', '', $response);
            $response = preg_replace('/\s*```$/', '', $response);

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($data['ideas'])) {
                error_log('Failed to parse blog ideas JSON: ' . json_last_error_msg());
                error_log('Response preview: ' . substr($response, 0, 500));
                return rest_ensure_response([
                    'success' => false,
                    'message' => 'Erreur de parsing JSON',
                ]);
            }

            return rest_ensure_response([
                'success' => true,
                'data' => $data['ideas'],
            ]);
        } catch (\Exception $e) {
            error_log('Blog strategy ideas exception: ' . $e->getMessage());
            return rest_ensure_response([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get current blog strategy for user
     *
     * @param WP_REST_Request $request
     * @return array
     */
    public function get_current_blog_strategy($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'acs_blog_strategies';
        $user_id = $this->get_current_user_id();
        $project_id = $this->get_active_project_id();

        // Filter by project_id
        if ($project_id) {
            $strategy = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d AND project_id = %d",
                $user_id,
                $project_id
            ), ARRAY_A);
        } else {
            $strategy = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d AND project_id IS NULL",
                $user_id
            ), ARRAY_A);
        }

        if (!$strategy) {
            return rest_ensure_response([
                'success' => false,
                'message' => 'Aucune stratégie trouvée',
            ]);
        }

        // Decode ideas JSON
        $strategy['ideas'] = json_decode($strategy['ideas'], true);

        return rest_ensure_response([
            'success' => true,
            'data' => $strategy,
        ]);
    }

    /**
     * Save blog strategy
     *
     * @param WP_REST_Request $request
     * @return array
     */
    public function save_blog_strategy($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'acs_blog_strategies';
        $user_id = $this->get_current_user_id();
        $project_id = $this->get_active_project_id();

        $params = $request->get_json_params();
        $ideas = $params['ideas'] ?? [];
        $language = sanitize_text_field($params['language'] ?? 'fr');
        $goal = sanitize_text_field($params['goal'] ?? 'engagement');

        if (empty($ideas)) {
            return rest_ensure_response([
                'success' => false,
                'message' => 'Les idées sont requises',
            ]);
        }

        $data = [
            'user_id' => $user_id,
            'project_id' => $project_id,
            'ideas' => wp_json_encode($ideas),
            'language' => $language,
            'goal' => $goal,
        ];

        // Check if strategy exists for this project
        if ($project_id) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE user_id = %d AND project_id = %d",
                $user_id,
                $project_id
            ));
        } else {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE user_id = %d AND project_id IS NULL",
                $user_id
            ));
        }

        if ($exists) {
            // Update existing strategy
            $result = $wpdb->update(
                $table,
                $data,
                ['user_id' => $user_id],
                ['%d', '%s', '%s', '%s'],
                ['%d']
            );
        } else {
            // Insert new strategy
            $result = $wpdb->insert(
                $table,
                $data,
                ['%d', '%s', '%s', '%s']
            );
        }

        if ($result === false) {
            error_log('Failed to save blog strategy: ' . $wpdb->last_error);
            return rest_ensure_response([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde',
            ]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Stratégie sauvegardée avec succès',
        ]);
    }

    /**
     * Migrate blog_strategies table (temporary endpoint)
     *
     * @param WP_REST_Request $request
     * @return array
     */
    public function migrate_blog_strategies_table($request) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'acs_blog_strategies';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            ideas LONGTEXT NOT NULL COMMENT 'JSON: Array de 50 sujets d articles',
            language VARCHAR(10) DEFAULT 'fr' COMMENT 'Langue de la stratégie',
            goal VARCHAR(50) COMMENT 'Objectif principal: engagement, brand_awareness, etc',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate ENGINE=InnoDB;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Vérifier que la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

        if ($table_exists === $table_name) {
            return rest_ensure_response([
                'success' => true,
                'message' => "Table {$table_name} créée avec succès",
            ]);
        } else {
            return rest_ensure_response([
                'success' => false,
                'message' => "Erreur lors de la création de la table",
            ]);
        }
    }
}
