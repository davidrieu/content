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
        $strategy = $params['strategy'] ?? [];

        $context = '';
        if (!empty($profile)) {
            $context .= sprintf(
                "\nContexte business: %s - %s",
                $profile['business_name'] ?? '',
                $profile['description'] ?? ''
            );
        }

        if (!empty($strategy['tone_style'])) {
            $context .= sprintf("\nStyle de communication: %s", $strategy['tone_style']);
        }

        return sprintf(
            "Génère 3 variantes de posts pour %s sur le sujet: %s

Langue: %s
Ton: %s%s

Consignes:
- Adapte le format aux spécificités de %s
- Utilise des emojis de manière appropriée
- Inclus un appel à l'action clair
- Respecte le ton demandé
- Reste dans les limites de caractères de la plateforme

Retourne un JSON:
{
    \"variants\": [
        {
            \"content\": \"texte du post\",
            \"hashtags\": [\"#hashtag1\", \"#hashtag2\"],
            \"hook\": \"phrase d'accroche\",
            \"cta\": \"call to action\"
        },
        {...},
        {...}
    ]
}",
            $platform,
            $topic,
            $language,
            $tone,
            $context,
            $platform
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
