<?php
/**
 * Prompts Library for Claude AI
 *
 * @package ACS\Data
 */

namespace ACS\Data;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Prompts_Library class
 */
class Prompts_Library {

    /**
     * Get strategy generation prompt
     *
     * @param array $profile
     * @return string
     */
    public static function get_strategy_prompt($profile) {
        $business_type = $profile['business_type'] ?? 'business';
        $target_audience = $profile['target_audience'] ?? '{}';
        $goals = $profile['goals'] ?? '[]';
        $platforms = $profile['platforms'] ?? '[]';

        return sprintf(
            "Tu es un expert en stratégie de contenu et marketing digital.

Crée une stratégie de contenu complète et personnalisée pour ce business:
- Type d'activité: %s
- Nom: %s
- Description: %s
- Niche: %s
- Audience cible: %s
- Objectifs: %s
- Plateformes: %s

Fournis une stratégie au format JSON avec:
{
    \"content_pillars\": [3-5 piliers de contenu principaux],
    \"posting_schedule\": {
        \"instagram\": {\"frequency\": \"X posts/semaine\", \"best_times\": [\"hours\"]},
        \"facebook\": {...},
        \"linkedin\": {...}
    },
    \"tone_style\": \"Description du ton et style\",
    \"hashtag_strategy\": {
        \"instagram\": [\"hashtags\"],
        \"linkedin\": [...]
    },
    \"content_mix\": {
        \"educational\": 40,
        \"promotional\": 20,
        \"engagement\": 30,
        \"entertainment\": 10
    },
    \"key_messages\": [\"messages clés\"],
    \"differentiation\": \"Ce qui rend ce business unique\"
}",
            $business_type,
            $profile['business_name'] ?? '',
            $profile['description'] ?? '',
            $profile['niche'] ?? '',
            $target_audience,
            $goals,
            $platforms
        );
    }

    /**
     * Get social post generation prompt
     *
     * @param array $params
     * @return string
     */
    public static function get_social_post_prompt($params) {
        $platform = $params['platform'] ?? 'instagram';
        $topic = $params['topic'] ?? '';
        $tone = $params['tone'] ?? 'professional';
        $language = $params['language'] ?? 'fr';
        $profile = $params['profile'] ?? [];
        $profile_data = $params['profile_data'] ?? [];
        $content_type = $params['content_type'] ?? 'educational';
        $template_id = $params['template_id'] ?? '';

        // Contexte business enrichi
        $context = '';
        if (!empty($profile['business_name']) || !empty($profile_data['business_name'])) {
            $business_name = $profile['business_name'] ?? $profile_data['business_name'] ?? '';
            $description = $profile['description'] ?? '';
            $context .= sprintf("\nBusiness: %s", $business_name);
            if ($description) {
                $context .= sprintf(" - %s", $description);
            }
        }

        // Ajout secteur et audience
        $sector = $profile_data['sector'] ?? $profile['sector'] ?? '';
        $target_audience = $profile_data['target_audience'] ?? $profile['target_audience'] ?? '';

        if ($sector) {
            $context .= sprintf("\nSecteur: %s", $sector);
        }
        if ($target_audience) {
            $context .= sprintf("\nAudience cible: %s", $target_audience);
        }

        // Type de contenu et structure
        $content_types = [
            'educational' => 'Éducatif (tips, conseils, how-to)',
            'promotional' => 'Promotionnel (offres, nouveautés, produits)',
            'engagement' => 'Engagement (questions, sondages, interaction)',
            'storytelling' => 'Storytelling (histoires, behind-the-scenes, témoignages)',
            'inspiration' => 'Inspirationnel (citations, motivation)',
            'news' => 'Actualités (news de l\'industrie, tendances)',
        ];

        $content_type_desc = $content_types[$content_type] ?? 'Général';

        // Templates specs
        $template_structure = '';
        if ($template_id === 'how_to') {
            $template_structure = "\n\nStructure HOW-TO:\n- Hook accrocheur avec problème\n- Introduction brève\n- 3-5 étapes numérotées\n- Conseil bonus\n- Call-to-action";
        } elseif ($template_id === 'tips_list') {
            $template_structure = "\n\nStructure LISTE DE CONSEILS:\n- Introduction accrocheuse\n- 5 conseils numérotés avec émojis\n- Chaque conseil en 1-2 phrases\n- Conclusion avec CTA";
        } elseif ($template_id === 'product_launch') {
            $template_structure = "\n\nStructure LANCEMENT:\n- Teasing accrocheur\n- Présentation du produit\n- 3 bénéfices principaux\n- Élément d'urgence\n- CTA fort";
        } elseif ($template_id === 'question') {
            $template_structure = "\n\nStructure QUESTION:\n- Contexte court et relatable\n- Question principale claire\n- Options ou exemples\n- Encouragement à commenter";
        } elseif ($template_id === 'behind_scenes') {
            $template_structure = "\n\nStructure COULISSES:\n- Hook intriguant\n- Raconter un processus/moment\n- Détails authentiques\n- Leçon ou insight\n- Remerciement/question";
        }

        // Specs plateforme avec limite passée depuis le frontend
        $max_length = $params['max_length'] ?? 2200;

        $platform_specs = [
            'instagram' => sprintf('MAX %d caractères, optimal 125-150. Émojis OK. Style visuel et engageant.', $max_length),
            'facebook' => sprintf('MAX %d caractères, optimal 40-80. Style conversationnel et personnel.', $max_length),
            'linkedin' => sprintf('MAX %d caractères, optimal 150-300. Professionnel, insights et valeur ajoutée.', $max_length),
            'twitter' => sprintf('MAX %d caractères, optimal 71-100. Concis, percutant et direct.', $max_length),
            'tiktok' => sprintf('MAX %d caractères, optimal 80-120. Jeune, dynamique, avec émojis.', $max_length),
            'youtube' => sprintf('MAX %d caractères, optimal 200-300. Descriptif et accrocheur.', $max_length),
            'pinterest' => sprintf('MAX %d caractères, optimal 100-200. Inspirant et descriptif.', $max_length),
            'snapchat' => sprintf('MAX %d caractères, optimal 80-150. Court, fun et authentique.', $max_length),
        ];

        $specs = $platform_specs[$platform] ?? sprintf('MAX %d caractères. Format standard.', $max_length);

        return sprintf(
            "Génère 3 variantes de posts pour %s sur le sujet: %s

CONTEXTE:%s

Type de contenu: %s
Langue: %s (IMPORTANT: Réponds dans cette langue!)
Ton: %s
Specs plateforme: %s%s

CONSIGNES IMPORTANTES:
- Adapte le format aux spécificités de %s
- Utilise des emojis de manière appropriée et moderne
- Inclus un appel à l'action clair et engageant
- Respecte scrupuleusement le ton demandé
- CRITIQUE: Respecte ABSOLUMENT la limite de %d caractères (contenu + hashtags)
- Compte les caractères attentivement, ne dépasse JAMAIS la limite
- Rends le contenu actionnable et utile
- Pour les hashtags: mélange de popularité (70%% niche + 20%% broad + 10%% branded)

Retourne UNIQUEMENT un JSON valide (pas de texte avant ou après):
{
    \"variants\": [
        {
            \"content\": \"Texte complet du post avec émojis et structure\",
            \"hashtags\": [\"#hashtag1\", \"#hashtag2\", \"#hashtag3\"],
            \"hook\": \"Première phrase accrocheuse\",
            \"cta\": \"Call to action final\"
        },
        {
            \"content\": \"Variante 2...\",
            \"hashtags\": [\"#tag1\", \"#tag2\"],
            \"hook\": \"Hook variante 2\",
            \"cta\": \"CTA variante 2\"
        },
        {
            \"content\": \"Variante 3...\",
            \"hashtags\": [\"#tag1\", \"#tag2\"],
            \"hook\": \"Hook variante 3\",
            \"cta\": \"CTA variante 3\"
        }
    ]
}",
            $platform,
            $topic,
            $context,
            $content_type_desc,
            $language,
            $tone,
            $specs,
            $template_structure,
            $platform,
            $max_length
        );
    }

    /**
     * Get blog article ideas prompt
     *
     * @param array $profile
     * @param int $count
     * @return string
     */
    public static function get_article_ideas_prompt($profile, $count = 10) {
        return sprintf(
            "Génère %d idées d'articles de blog pour ce business:

Business: %s - %s
Niche: %s
Audience: %s

Pour chaque idée, fournis:
{
    \"ideas\": [
        {
            \"title\": \"Titre accrocheur\",
            \"description\": \"Courte description de l'article\",
            \"keyword\": \"mot-clé SEO principal\",
            \"estimated_word_count\": 1500,
            \"seo_potential\": 8,
            \"engagement_potential\": 7,
            \"difficulty\": \"facile/moyen/difficile\"
        },
        {...}
    ]
}

Les idées doivent être:
- SEO-friendly
- Adaptées à l'audience cible
- Variées dans les formats (how-to, listicle, guide, comparaison, etc.)
- Actionnables et utiles",
            $count,
            $profile['business_name'] ?? '',
            $profile['description'] ?? '',
            $profile['niche'] ?? '',
            json_encode($profile['target_audience'] ?? [])
        );
    }

    /**
     * Get full article generation prompt
     *
     * @param array $params
     * @return string
     */
    public static function get_article_generation_prompt($params) {
        $topic = $params['topic'] ?? '';
        $keyword = $params['keyword'] ?? '';
        $word_count = $params['word_count'] ?? 1500;
        $tone = $params['tone'] ?? 'professional';
        $language = $params['language'] ?? 'fr';

        return sprintf(
            "Rédige un article de blog complet sur: %s

Mot-clé principal: %s
Nombre de mots souhaité: %d
Ton: %s
Langue: %s

L'article doit contenir:
1. Un titre SEO-optimisé (<60 caractères)
2. Une meta description (<160 caractères)
3. Une introduction engageante (100-150 mots)
4. 3-5 sections principales avec sous-titres H2
5. Des sous-sections H3 si pertinent
6. Une conclusion avec appel à l'action
7. Une liste de 5-8 mots-clés secondaires

Format de réponse JSON:
{
    \"title\": \"Titre optimisé\",
    \"meta_description\": \"Description meta\",
    \"content\": \"<h2>Introduction</h2><p>...</p><h2>Section 1</h2>...\",
    \"excerpt\": \"Résumé court\",
    \"keywords\": [\"keyword1\", \"keyword2\"],
    \"internal_link_suggestions\": [\"Idée de lien interne 1\"],
    \"image_suggestions\": [\"Description d'image 1\", \"Description d'image 2\"]
}

Conseils SEO:
- Utilise le mot-clé principal naturellement 5-7 fois
- Inclus des variantes du mot-clé
- Structure logique avec hiérarchie de titres
- Paragraphes courts et lisibles
- Inclus des listes à puces/numérotées
- Ajoute des statistiques ou faits vérifiables si pertinent",
            $topic,
            $keyword,
            $word_count,
            $tone,
            $language
        );
    }

    /**
     * Get image prompt optimization
     *
     * @param string $user_prompt
     * @param array $context
     * @return string
     */
    public static function get_image_prompt_optimizer($user_prompt, $context = []) {
        $style = $context['style'] ?? 'realistic';
        $format = $context['format'] ?? 'square';

        return sprintf(
            "Transforme ce prompt utilisateur en un prompt optimisé pour DALL-E 3:

Prompt utilisateur: %s
Style souhaité: %s
Format: %s

Améliore le prompt en ajoutant:
- Détails visuels précis
- Style artistique approprié
- Éléments de composition
- Palette de couleurs si pertinent
- Ambiance/mood

Retourne uniquement le prompt optimisé en anglais (DALL-E performe mieux en anglais), sans explication additionnelle.
Le prompt doit être clair, détaillé mais concis (max 400 caractères).",
            $user_prompt,
            $style,
            $format
        );
    }

    /**
     * Get hashtag generation prompt
     *
     * @param string $content
     * @param string $platform
     * @param int $count
     * @return string
     */
    public static function get_hashtag_prompt($content, $platform, $count = 10) {
        return sprintf(
            "Génère %d hashtags pertinents pour ce contenu sur %s:

Contenu: %s

Les hashtags doivent être:
- Pertinents au contenu
- Mélange de popularité (quelques populaires, quelques niches)
- Adaptés à %s
- En français si le contenu est en français

Format JSON:
{
    \"hashtags\": [
        {\"tag\": \"#hashtag1\", \"popularity\": \"high\"},
        {\"tag\": \"#hashtag2\", \"popularity\": \"medium\"},
        {...}
    ]
}",
            $count,
            $platform,
            substr($content, 0, 500),
            $platform
        );
    }

    /**
     * Get post ideas prompt based on previous posts
     *
     * @param array $params
     * @return string
     */
    public static function get_post_ideas_prompt($params) {
        $recent_posts = $params['recent_posts'] ?? [];
        $profile = $params['profile'] ?? [];
        $platform = $params['platform'] ?? 'instagram';

        // Extract content from recent posts
        $posts_summary = '';
        foreach (array_slice($recent_posts, 0, 5) as $index => $post) {
            $content = isset($post['content']) ? substr($post['content'], 0, 150) : '';
            $platform_name = isset($post['platform']) ? $post['platform'] : 'inconnu';
            $posts_summary .= sprintf("\n%d. [%s] %s...", $index + 1, $platform_name, $content);
        }

        return sprintf(
            "Tu es un expert en stratégie de contenu pour les réseaux sociaux.

Basé sur les posts précédents de l'utilisateur et son profil, génère 5 IDÉES DE SUJETS pour de nouveaux posts.

PROFIL BUSINESS:
- Nom: %s
- Secteur: %s
- Audience: %s
- Niche: %s

POSTS RÉCENTS:%s

PLATEFORME CIBLE: %s

CONSIGNES:
- Analyse les thèmes et styles qui marchent dans les posts précédents
- Propose des idées qui sont une ÉVOLUTION naturelle du contenu existant
- Reste cohérent avec l'identité de marque
- Chaque idée doit être concrète, actionnable et engageante
- Varie les types de contenu (éducatif, storytelling, engagement, etc.)
- Adapte les idées à %s

Format JSON attendu:
{
    \"ideas\": [
        {
            \"title\": \"Titre court et accrocheur de l'idée\",
            \"description\": \"Description détaillée en 2-3 phrases\",
            \"type\": \"educational|promotional|engagement|storytelling|inspiration\",
            \"platform\": \"%s\",
            \"why\": \"Pourquoi cette idée est pertinente basée sur l'historique\"
        },
        {
            \"title\": \"...\",
            \"description\": \"...\",
            \"type\": \"...\",
            \"platform\": \"%s\",
            \"why\": \"...\"
        }
    ]
}",
            $profile['business_name'] ?? 'Business',
            $profile['sector'] ?? 'général',
            $profile['target_audience'] ?? 'audience générale',
            $profile['niche'] ?? 'niche non définie',
            $posts_summary ?: "\n(Aucun post récent trouvé, propose des idées génériques adaptées au profil)",
            $platform,
            $platform,
            $platform,
            $platform
        );
    }

    /**
     * Get trend analysis prompt
     *
     * @param string $trend
     * @param array $profile
     * @return string
     */
    public static function get_trend_analysis_prompt($trend, $profile) {
        return sprintf(
            "Analyse cette tendance pour ce business:

Tendance: %s
Business: %s - %s
Niche: %s

Fournis une analyse au format JSON:
{
    \"relevance_score\": 0.85,
    \"relevance_explanation\": \"Pourquoi pertinent\",
    \"content_angles\": [
        \"Angle 1 pour exploiter la tendance\",
        \"Angle 2\",
        \"Angle 3\"
    ],
    \"post_ideas\": [
        {\"platform\": \"instagram\", \"idea\": \"Idée de post\"},
        {...}
    ],
    \"timing\": \"maintenant/cette semaine/ce mois\",
    \"risks\": [\"Risque potentiel si applicable\"]
}",
            $trend,
            $profile['business_name'] ?? '',
            $profile['description'] ?? '',
            $profile['niche'] ?? ''
        );
    }

    /**
     * Get SEO optimization prompt
     *
     * @param string $content
     * @param string $keyword
     * @return string
     */
    public static function get_seo_analysis_prompt($content, $keyword) {
        return sprintf(
            "Analyse ce contenu pour le SEO avec le mot-clé principal: %s

Contenu:
%s

Fournis une analyse JSON:
{
    \"keyword_usage\": {
        \"count\": 5,
        \"density\": 1.2,
        \"in_title\": true,
        \"in_meta\": true,
        \"in_headings\": 2
    },
    \"readability\": {
        \"score\": 65,
        \"avg_sentence_length\": 18,
        \"passive_voice\": \"low\"
    },
    \"structure\": {
        \"headings_count\": {\"h2\": 4, \"h3\": 6},
        \"paragraphs_count\": 12,
        \"has_lists\": true
    },
    \"suggestions\": [
        \"Ajoute le mot-clé dans un H2\",
        \"Raccourcis le paragraphe 3\"
    ],
    \"score\": 75
}",
            $keyword,
            substr($content, 0, 2000)
        );
    }
}
