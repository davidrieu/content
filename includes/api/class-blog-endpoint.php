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
class Blog_Endpoint {

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

        if (empty($subject)) {
            return rest_ensure_response([
                'success' => false,
                'message' => __('Le sujet est requis', 'ai-content-studio'),
            ]);
        }

        $prompt = "Tu es un expert en rédaction de contenu et SEO.

Sujet de l'article : {$subject}

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

        // Parse JSON response
        $content = isset($response['content'][0]['text']) ? $response['content'][0]['text'] : '';

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
        $include_images = (bool) $request->get_param('include_images');

        // Parse length range
        list($min_words, $max_words) = explode('-', $length);
        $target_words = (int) $max_words;

        // Calculate keyword density (aim for 1-2%)
        $keyword_occurrences = max(2, floor($target_words * 0.015)); // 1.5%

        // Build prompt
        $keywords_list = implode(', ', $keywords);
        $point_of_view = $first_person ? 'première personne (je, nous)' : 'troisième personne';

        $prompt = "Tu es un rédacteur web expert en SEO.

CONSIGNES :
- Titre de l'article : {$title}
- Mots-clés à intégrer naturellement : {$keywords_list}
- Mot-clé principal (à utiliser environ {$keyword_occurrences} fois) : {$main_keyword}
- Point de vue : {$point_of_view}
- Longueur cible : {$target_words} mots (entre {$min_words} et {$max_words} mots)

STRUCTURE OBLIGATOIRE :
1. Introduction engageante (10% du texte)
2. 3-5 sections principales avec sous-titres H2
3. Chaque section doit contenir 2-3 paragraphes
4. Conclusion avec appel à l'action

RÈGLES SEO :
- Densité du mot-clé principal : 1-2% (environ {$keyword_occurrences} occurrences)
- Intégrer les mots-clés secondaires naturellement
- Utiliser des variations sémantiques
- Phrases courtes et claires
- Paragraphes de 3-5 lignes maximum

IMPORTANT :
- N'utilise PAS de markdown
- N'utilise PAS de balises HTML (sauf si c'est pour les images)
- Écris du texte brut avec des sauts de ligne entre les paragraphes
- Ne mets PAS de titre au début (il est déjà affiché)

Commence la rédaction maintenant :";

        // Stream the response
        try {
            $this->claude->stream_completion(
                $prompt,
                'claude-sonnet-4-20250514', // Sonnet 4
                $target_words * 5, // Tokens approximation
                function($chunk) {
                    if (isset($chunk['type'])) {
                        if ($chunk['type'] === 'content_block_delta') {
                            $text = $chunk['delta']['text'] ?? '';
                            if (!empty($text)) {
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

        // Truncate content for prompt (first 500 words)
        $content_words = explode(' ', $content);
        $content_excerpt = implode(' ', array_slice($content_words, 0, 500));

        $prompt = "Tu es un expert SEO.

Titre de l'article : {$title}
Mot-clé principal : {$main_keyword}
Début de l'article : {$content_excerpt}...

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

        // Parse JSON response
        $response_text = isset($response['content'][0]['text']) ? $response['content'][0]['text'] : '';

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
        $user_id = get_current_user_id();

        $data = [
            'user_id' => $user_id,
            'subject' => sanitize_text_field($request->get_param('subject')),
            'title' => sanitize_text_field($request->get_param('title')),
            'content' => wp_kses_post($request->get_param('content')),
            'keywords' => wp_json_encode($request->get_param('keywords')),
            'main_keyword' => sanitize_text_field($request->get_param('main_keyword')),
            'first_person' => (int) $request->get_param('first_person'),
            'word_count' => str_word_count($request->get_param('content')),
            'seo_title' => sanitize_text_field($request->get_param('seo_title')),
            'meta_description' => sanitize_text_field($request->get_param('meta_description')),
            'url_slug' => sanitize_title($request->get_param('url_slug')),
            'created_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert($table, $data, [
            '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s'
        ]);

        if ($result === false) {
            return rest_ensure_response([
                'success' => false,
                'message' => __('Erreur lors de la sauvegarde', 'ai-content-studio'),
            ]);
        }

        return rest_ensure_response([
            'success' => true,
            'data' => [
                'id' => $wpdb->insert_id,
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
        $user_id = get_current_user_id();

        $articles = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE user_id = %d
             ORDER BY created_at DESC
             LIMIT 50",
            $user_id
        ), ARRAY_A);

        // Decode keywords
        foreach ($articles as &$article) {
            $article['keywords'] = json_decode($article['keywords'], true);
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $articles,
        ]);
    }
}
