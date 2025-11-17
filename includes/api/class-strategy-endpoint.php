<?php
/**
 * Strategy API Endpoint
 * Gestion de la génération automatique de stratégies de contenu
 *
 * @package ACS
 */

namespace ACS\API;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use ACS\Services\Claude_Service;
use ACS\Utils\Sanitizer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Strategy Endpoint class
 */
class Strategy_Endpoint extends REST_Controller {

    /**
     * Rest base
     *
     * @var string
     */
    protected $rest_base = 'strategy';

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/generate', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'generate_strategy'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/current', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_current_strategy'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/content-ideas', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'generate_content_ideas'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/apply', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'apply_strategy'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_strategy'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);
    }

    /**
     * Générer une stratégie de contenu automatique
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function generate_strategy($request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();

        $month = isset($params['month']) ? intval($params['month']) : date('n');
        $year = isset($params['year']) ? intval($params['year']) : date('Y');
        $goal = sanitize_text_field($params['goal'] ?? 'brand_awareness');
        $posts_per_week = isset($params['posts_per_week']) ? intval($params['posts_per_week']) : 3;
        $language = sanitize_text_field($params['language'] ?? 'fr');
        $profile = $params['profile'] ?? [];

        // Construire le prompt pour Claude
        $prompt = $this->build_strategy_prompt($month, $year, $goal, $posts_per_week, $profile, $language);

        try {
            $claude = new Claude_Service();
            $response = $claude->generate_completion($prompt);

            // Parser la réponse JSON de Claude
            $strategy_data = $this->parse_strategy_response($response);

            // Sauvegarder la stratégie dans la base de données
            $strategy_id = $this->save_strategy($user_id, $month, $year, $strategy_data, $posts_per_week, $profile);

            $strategy_data['id'] = $strategy_id;
            $strategy_data['month'] = $month;
            $strategy_data['year'] = $year;

            // Ensure platforms is always an array
            $platforms = $profile['platforms'] ?? ['instagram', 'facebook'];
            if (is_string($platforms)) {
                $platforms = json_decode($platforms, true) ?? ['instagram', 'facebook'];
            }
            if (!is_array($platforms)) {
                $platforms = ['instagram', 'facebook'];
            }
            $strategy_data['platforms'] = $platforms;

            return rest_ensure_response([
                'success' => true,
                'data' => $strategy_data,
            ]);
        } catch (\Exception $e) {
            return new WP_Error(
                'strategy_generation_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Construire le prompt pour Claude
     *
     * @param int $month Mois (1-12)
     * @param int $year Année
     * @param string $goal Objectif
     * @param int $posts_per_week Posts par semaine
     * @param array $profile Profil utilisateur
     * @param string $language Langue de génération
     * @return string
     */
    private function build_strategy_prompt($month, $year, $goal, $posts_per_week, $profile, $language = 'fr') {
        $month_name = date('F', mktime(0, 0, 0, $month, 1));
        $total_posts = $posts_per_week * 4;

        $platforms = is_array($profile['platforms']) ? implode(', ', $profile['platforms']) : 'Instagram, Facebook';
        $user_type = $profile['user_type'] ?? 'business';
        $business_name = $profile['business_name'] ?? 'Business';
        $sector = $profile['sector'] ?? '';
        $description = $profile['description'] ?? '';
        $target_audience = $profile['target_audience'] ?? '';

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

        $goal_descriptions = [
            'brand_awareness' => 'notoriété de marque et visibilité',
            'lead_generation' => 'génération de leads qualifiés',
            'sales' => 'augmentation des ventes',
            'engagement' => 'engagement et création de communauté',
            'authority' => 'positionnement en expert et leader d\'opinion',
        ];

        $goal_description = $goal_descriptions[$goal] ?? 'engagement';

        // Build profile context
        $profile_context = "Type: {$user_type}\nEntreprise: {$business_name}\nSecteur: {$sector}";
        if (!empty($description)) {
            $profile_context .= "\nDescription: {$description}";
        }
        $profile_context .= "\nAudience cible: {$target_audience}\nPlateformes: {$platforms}\nObjectif principal: {$goal_description}\nFréquence: {$posts_per_week} posts par semaine";

        return <<<PROMPT
Tu es un expert en stratégie de contenu pour les réseaux sociaux.

MISSION: Créer une stratégie de contenu complète et SPÉCIFIQUEMENT ADAPTÉE au profil client pour {$month_name} {$year}.

PROFIL CLIENT:
{$profile_context}

IMPORTANT: La stratégie doit être PERTINENTE pour cette activité spécifique, PAS générique.

CONTRAINTES:
- {$total_posts} posts au total sur le mois
- Mix de contenu adapté à l'objectif
- Thèmes hebdomadaires cohérents
- Conseils pratiques et actionnables

IMPORTANT: Génère tout le contenu en {$language_name}.

GÉNÈRE une stratégie complète au format JSON avec cette structure EXACTE:

{
    "strategy_summary": "Résumé de la stratégie en 2-3 phrases, SPÉCIFIQUEMENT basé sur l'activité et la description du client",
    "weekly_themes": [
        {
            "week": 1,
            "theme": "Titre du thème",
            "description": "Description courte",
            "focus": "Point principal de la semaine"
        },
        {
            "week": 2,
            "theme": "Titre du thème",
            "description": "Description courte",
            "focus": "Point principal de la semaine"
        },
        {
            "week": 3,
            "theme": "Titre du thème",
            "description": "Description courte",
            "focus": "Point principal de la semaine"
        },
        {
            "week": 4,
            "theme": "Titre du thème",
            "description": "Description courte",
            "focus": "Point principal de la semaine"
        }
    ],
    "content_mix": {
        "educational": 40,
        "promotional": 20,
        "engagement": 25,
        "storytelling": 15
    },
    "key_topics": [
        "Sujet 1",
        "Sujet 2",
        "Sujet 3",
        "Sujet 4",
        "Sujet 5"
    ],
    "platforms": ["{platforms}"],
    "best_practices": [
        "Conseil 1",
        "Conseil 2",
        "Conseil 3"
    ],
    "total_posts": {$total_posts}
}

IMPORTANT: Réponds UNIQUEMENT avec le JSON, sans texte avant ou après.
PROMPT;
    }

    /**
     * Parser la réponse de Claude
     *
     * @param string $response Réponse brute
     * @return array
     */
    private function parse_strategy_response($response) {
        // Nettoyer la réponse pour extraire le JSON
        $response = trim($response);

        // Enlever les balises markdown si présentes
        $response = preg_replace('/^```json\s*/i', '', $response);
        $response = preg_replace('/\s*```$/', '', $response);

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Erreur lors du parsing de la stratégie: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Sauvegarder la stratégie dans la base de données
     *
     * @param int $user_id ID utilisateur
     * @param int $month Mois
     * @param int $year Année
     * @param array $strategy_data Données de stratégie
     * @param int $posts_per_week Posts par semaine
     * @param array $profile Profil
     * @return int Strategy ID
     */
    private function save_strategy($user_id, $month, $year, $strategy_data, $posts_per_week, $profile) {
        global $wpdb;
        $table = $wpdb->prefix . 'acs_content_plans';

        // Récupérer le project_id actif
        $project_id = $this->get_active_project_id();

        $month_names = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        $data = [
            'user_id' => $user_id,
            'project_id' => $project_id,
            'name' => 'Plan ' . $month_names[$month] . ' ' . $year,
            'month' => $month,
            'year' => $year,
            'strategy' => wp_json_encode($strategy_data),
            'weekly_themes' => wp_json_encode($strategy_data['weekly_themes'] ?? []),
            'posting_frequency' => $profile['posting_frequency'] ?? 'weekly',
            'content_mix' => wp_json_encode($strategy_data['content_mix'] ?? []),
            'platforms' => wp_json_encode($profile['platforms'] ?? []),
            'status' => 'active',
            'is_generated' => 1,
        ];

        $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }

    /**
     * Appliquer une stratégie (générer les posts)
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function apply_strategy($request) {
        // Cette méthode serait appelée pour générer automatiquement tous les posts
        // basés sur la stratégie. Pour l'instant, on retourne un succès simulé.

        return rest_ensure_response([
            'success' => true,
            'message' => __('Stratégie appliquée avec succès!', 'ai-content-studio'),
        ]);
    }

    /**
     * Get current strategy for the user
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_current_strategy($request) {
        $user_id = $this->get_current_user_id();
        $project_id = $this->get_active_project_id();

        global $wpdb;
        $table = $wpdb->prefix . 'acs_content_plans';

        // Récupérer la stratégie la plus récente de l'utilisateur pour le projet actif
        if ($project_id) {
            $strategy = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE user_id = %d AND project_id = %d
                 ORDER BY created_at DESC
                 LIMIT 1",
                $user_id,
                $project_id
            ), ARRAY_A);
        } else {
            // Fallback pour les anciennes données sans project_id
            $strategy = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE user_id = %d AND project_id IS NULL
                 ORDER BY created_at DESC
                 LIMIT 1",
                $user_id
            ), ARRAY_A);
        }

        if (!$strategy) {
            return rest_ensure_response([
                'success' => false,
                'data' => null,
                'message' => __('Aucune stratégie trouvée', 'ai-content-studio'),
            ]);
        }

        // Parse JSON fields
        $strategy_data = json_decode($strategy['strategy'], true);
        $strategy_data['weekly_themes'] = json_decode($strategy['weekly_themes'], true);
        $strategy_data['content_mix'] = json_decode($strategy['content_mix'], true);

        // Ensure platforms is always an array
        $platforms = json_decode($strategy['platforms'], true);
        if (!is_array($platforms)) {
            $platforms = ['instagram', 'facebook'];
        }
        $strategy_data['platforms'] = $platforms;

        // Récupérer les content_ideas si elles existent (stockées dans key_topics ou un autre champ)
        if (isset($strategy_data['content_ideas'])) {
            $strategy_data['content_ideas'] = $strategy_data['content_ideas'];
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $strategy_data,
        ]);
    }

    /**
     * Générer 50 idées de titres de posts
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function generate_content_ideas($request) {
        $params = $request->get_json_params();
        $profile = $params['profile'] ?? [];

        $business_name = $profile['business_name'] ?? 'Business';
        $sector = $profile['sector'] ?? 'général';
        $description = $profile['description'] ?? '';
        $target_audience = $profile['target_audience'] ?? 'large public';
        $goal = sanitize_text_field($params['goal'] ?? 'engagement');
        $language = sanitize_text_field($params['language'] ?? 'fr');
        $platforms = is_array($profile['platforms']) ? implode(', ', $profile['platforms']) : 'Instagram, Facebook';

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

        $goal_descriptions = [
            'brand_awareness' => 'notoriété de marque et visibilité',
            'lead_generation' => 'génération de leads qualifiés',
            'sales' => 'augmentation des ventes',
            'engagement' => 'engagement et création de communauté',
            'authority' => 'positionnement en expert et leader d\'opinion',
        ];

        $goal_description = $goal_descriptions[$goal] ?? 'engagement';

        // Build profile context
        $profile_context = "Entreprise: {$business_name}\nSecteur: {$sector}";
        if (!empty($description)) {
            $profile_context .= "\nDescription: {$description}";
        }
        $profile_context .= "\nAudience cible: {$target_audience}\nPlateformes: {$platforms}";

        $prompt = <<<PROMPT
Tu es un expert en création de contenu pour les réseaux sociaux.

MISSION: Générer 50 titres/sujets de posts percutants et variés SPÉCIFIQUEMENT ADAPTÉS au profil ci-dessous.

PROFIL CLIENT:
{$profile_context}

OBJECTIF: {$goal_description}

CONTRAINTES:
- 50 titres courts et accrocheurs (5-10 mots max)
- IMPORTANT: Les titres doivent être PERTINENTS pour ce profil spécifique (secteur, activité, description)
- Variété de types: éducatif, inspirant, questions, conseils, storytelling, cas pratiques
- Adaptés aux plateformes et à l'audience décrite
- Actionnables et engageants
- Éviter les généralités, privilégier les sujets concrets liés à l'activité

IMPORTANT: Génère tous les titres en {$language_name}.

GÉNÈRE exactement 50 titres au format JSON avec cette structure:

{
    "ideas": [
        "Titre 1",
        "Titre 2",
        ...
        "Titre 50"
    ]
}

IMPORTANT: Réponds UNIQUEMENT avec le JSON, sans texte avant ou après.
PROMPT;

        try {
            $claude = new Claude_Service();
            // Utiliser Haiku pour la rapidité
            $response = $claude->generate_completion($prompt, 'claude-3-5-haiku-20241022');

            // Parser la réponse
            $response = trim($response);
            $response = preg_replace('/^```json\s*/i', '', $response);
            $response = preg_replace('/\s*```$/', '', $response);

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Erreur lors du parsing des idées: ' . json_last_error_msg());
            }

            if (!isset($data['ideas']) || !is_array($data['ideas'])) {
                throw new \Exception('Format de réponse invalide');
            }

            // Sauvegarder les idées dans la stratégie existante
            $this->save_content_ideas_to_strategy($data['ideas']);

            return rest_ensure_response([
                'success' => true,
                'data' => $data['ideas'],
            ]);
        } catch (\Exception $e) {
            return new WP_Error(
                'content_ideas_generation_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Sauvegarder les content ideas dans la stratégie existante
     *
     * @param array $ideas Les 50 idées générées
     * @return void
     */
    private function save_content_ideas_to_strategy($ideas) {
        $user_id = $this->get_current_user_id();
        $project_id = $this->get_active_project_id();

        global $wpdb;
        $table = $wpdb->prefix . 'acs_content_plans';

        // Récupérer la stratégie existante pour le projet actif
        if ($project_id) {
            $strategy = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE user_id = %d AND project_id = %d
                 ORDER BY created_at DESC
                 LIMIT 1",
                $user_id,
                $project_id
            ), ARRAY_A);
        } else {
            $strategy = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE user_id = %d AND project_id IS NULL
                 ORDER BY created_at DESC
                 LIMIT 1",
                $user_id
            ), ARRAY_A);
        }

        if (!$strategy) {
            return;
        }

        // Parser la stratégie existante
        $strategy_data = json_decode($strategy['strategy'], true);

        // Ajouter les content_ideas
        $strategy_data['content_ideas'] = $ideas;

        // Mettre à jour la stratégie
        $wpdb->update(
            $table,
            ['strategy' => wp_json_encode($strategy_data)],
            ['id' => $strategy['id']],
            ['%s'],
            ['%d']
        );
    }

    /**
     * Get strategy by ID
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_strategy($request) {
        $strategy_id = $request['id'];
        $user_id = $this->get_current_user_id();
        $project_id = $this->get_active_project_id();

        global $wpdb;
        $table = $wpdb->prefix . 'acs_content_plans';

        // Filtrer par project_id si disponible
        if ($project_id) {
            $strategy = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d AND user_id = %d AND project_id = %d",
                $strategy_id,
                $user_id,
                $project_id
            ), ARRAY_A);
        } else {
            $strategy = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d AND user_id = %d AND project_id IS NULL",
                $strategy_id,
                $user_id
            ), ARRAY_A);
        }

        if (!$strategy) {
            return new WP_Error('not_found', 'Strategy not found', ['status' => 404]);
        }

        // Parse JSON fields
        $strategy['strategy'] = json_decode($strategy['strategy'], true);
        $strategy['weekly_themes'] = json_decode($strategy['weekly_themes'], true);
        $strategy['content_mix'] = json_decode($strategy['content_mix'], true);
        $strategy['platforms'] = json_decode($strategy['platforms'], true);

        return rest_ensure_response([
            'success' => true,
            'data' => $strategy,
        ]);
    }
}
