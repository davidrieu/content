<?php
/**
 * Templates Library
 *
 * @package ACS\Data
 */

namespace ACS\Data;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Templates_Library class
 */
class Templates_Library {

    /**
     * Get all default templates
     *
     * @return array
     */
    public static function get_all() {
        return [
            // Social Post Templates
            'social_announcement' => [
                'name' => __('Annonce importante', 'ai-content-studio'),
                'category' => 'social_post',
                'subcategory' => 'announcement',
                'platform' => 'all',
                'template_content' => __('🎉 Grande nouvelle ! {{announcement}}\n\n{{details}}\n\n{{cta}}', 'ai-content-studio'),
                'prompt_template' => __('Rédige une annonce enthousiaste pour {{platform}} concernant : {{topic}}. Ton : {{tone}}', 'ai-content-studio'),
            ],
            'social_promotion' => [
                'name' => __('Promotion / Offre spéciale', 'ai-content-studio'),
                'category' => 'social_post',
                'subcategory' => 'promotion',
                'platform' => 'all',
                'template_content' => __('⚡ OFFRE SPÉCIALE ⚡\n\n{{offer_description}}\n\n💰 {{discount}}\n⏰ {{deadline}}\n\n{{cta}}', 'ai-content-studio'),
                'prompt_template' => __('Crée un post promotionnel pour {{offer}} avec urgence et appel à l\'action clair', 'ai-content-studio'),
            ],
            'social_education' => [
                'name' => __('Contenu éducatif', 'ai-content-studio'),
                'category' => 'social_post',
                'subcategory' => 'education',
                'platform' => 'all',
                'template_content' => __('💡 Le saviez-vous ?\n\n{{fact}}\n\n{{explanation}}\n\n{{takeaway}}', 'ai-content-studio'),
                'prompt_template' => __('Partage un conseil éducatif sur {{topic}} de manière accessible et engageante', 'ai-content-studio'),
            ],
            'social_testimonial' => [
                'name' => __('Témoignage client', 'ai-content-studio'),
                'category' => 'social_post',
                'subcategory' => 'testimonial',
                'platform' => 'all',
                'template_content' => __('⭐⭐⭐⭐⭐\n\n"{{testimonial}}"\n\n- {{customer_name}}\n\n{{cta}}', 'ai-content-studio'),
                'prompt_template' => __('Présente un témoignage client authentique et convaincant', 'ai-content-studio'),
            ],
            'social_question' => [
                'name' => __('Question engagement', 'ai-content-studio'),
                'category' => 'social_post',
                'subcategory' => 'engagement',
                'platform' => 'all',
                'template_content' => __('{{question}} 🤔\n\nDites-nous en commentaire ! 👇', 'ai-content-studio'),
                'prompt_template' => __('Pose une question engageante liée à {{topic}} pour susciter l\'interaction', 'ai-content-studio'),
            ],
            'social_behind_scenes' => [
                'name' => __('Coulisses', 'ai-content-studio'),
                'category' => 'social_post',
                'subcategory' => 'behind_scenes',
                'platform' => 'instagram',
                'template_content' => __('En coulisses 🎬\n\n{{description}}\n\n{{fun_fact}}', 'ai-content-studio'),
                'prompt_template' => __('Montre les coulisses de {{business}} de manière authentique', 'ai-content-studio'),
            ],
            'social_motivational' => [
                'name' => __('Citation motivante', 'ai-content-studio'),
                'category' => 'social_post',
                'subcategory' => 'motivational',
                'platform' => 'all',
                'template_content' => __('✨ {{quote}}\n\n{{reflection}}', 'ai-content-studio'),
                'prompt_template' => __('Crée une citation inspirante en lien avec {{theme}}', 'ai-content-studio'),
            ],

            // LinkedIn specific
            'linkedin_thought_leadership' => [
                'name' => __('Leadership d\'opinion (LinkedIn)', 'ai-content-studio'),
                'category' => 'social_post',
                'subcategory' => 'thought_leadership',
                'platform' => 'linkedin',
                'template_content' => __('{{hook}}\n\n{{main_point_1}}\n\n{{main_point_2}}\n\n{{main_point_3}}\n\n{{conclusion}}', 'ai-content-studio'),
                'prompt_template' => __('Rédige un post LinkedIn professionnel sur {{topic}} démontrant expertise', 'ai-content-studio'),
            ],
            'linkedin_company_update' => [
                'name' => __('Actualité entreprise (LinkedIn)', 'ai-content-studio'),
                'category' => 'social_post',
                'subcategory' => 'company_news',
                'platform' => 'linkedin',
                'template_content' => __('Nous sommes ravis d\'annoncer {{news}} 🎉\n\n{{details}}\n\n{{impact}}\n\n{{next_steps}}', 'ai-content-studio'),
                'prompt_template' => __('Annonce professionnelle pour LinkedIn: {{announcement}}', 'ai-content-studio'),
            ],

            // Twitter/X specific
            'twitter_thread_starter' => [
                'name' => __('Début de thread (Twitter)', 'ai-content-studio'),
                'category' => 'social_post',
                'subcategory' => 'thread',
                'platform' => 'twitter',
                'template_content' => __('🧵 {{hook}}\n\nVoici ce que vous devez savoir: (1/{{count}})', 'ai-content-studio'),
                'prompt_template' => __('Crée un tweet d\'accroche pour un thread sur {{topic}}', 'ai-content-studio'),
            ],

            // Blog Article Templates
            'blog_how_to' => [
                'name' => __('Article "Comment faire"', 'ai-content-studio'),
                'category' => 'blog_article',
                'subcategory' => 'tutorial',
                'platform' => null,
                'template_content' => null,
                'prompt_template' => __('Rédige un guide complet "Comment {{action}}" avec introduction, étapes détaillées, conseils pratiques et conclusion. Inclure exemples concrets.', 'ai-content-studio'),
            ],
            'blog_listicle' => [
                'name' => __('Liste numérotée', 'ai-content-studio'),
                'category' => 'blog_article',
                'subcategory' => 'listicle',
                'platform' => null,
                'template_content' => null,
                'prompt_template' => __('Crée un article "{{number}} {{topic}}" avec introduction engageante, chaque point bien développé, et conclusion percutante.', 'ai-content-studio'),
            ],
            'blog_ultimate_guide' => [
                'name' => __('Guide ultime', 'ai-content-studio'),
                'category' => 'blog_article',
                'subcategory' => 'guide',
                'platform' => null,
                'template_content' => null,
                'prompt_template' => __('Rédige le guide ultime sur {{topic}} avec table des matières, sections approfondies, exemples, FAQ et ressources additionnelles.', 'ai-content-studio'),
            ],
            'blog_case_study' => [
                'name' => __('Étude de cas', 'ai-content-studio'),
                'category' => 'blog_article',
                'subcategory' => 'case_study',
                'platform' => null,
                'template_content' => null,
                'prompt_template' => __('Présente une étude de cas sur {{subject}} avec contexte, défi, solution, résultats mesurables et leçons apprises.', 'ai-content-studio'),
            ],
            'blog_comparison' => [
                'name' => __('Article comparatif', 'ai-content-studio'),
                'category' => 'blog_article',
                'subcategory' => 'comparison',
                'platform' => null,
                'template_content' => null,
                'prompt_template' => __('Compare {{option_a}} vs {{option_b}} de manière objective avec avantages, inconvénients, cas d\'usage et recommandations.', 'ai-content-studio'),
            ],
            'blog_news_analysis' => [
                'name' => __('Analyse d\'actualité', 'ai-content-studio'),
                'category' => 'blog_article',
                'subcategory' => 'news',
                'platform' => null,
                'template_content' => null,
                'prompt_template' => __('Analyse {{news_topic}} avec contexte, implications, perspectives d\'experts et impact sur {{industry}}.', 'ai-content-studio'),
            ],
        ];
    }

    /**
     * Get templates by category
     *
     * @param string $category
     * @return array
     */
    public static function get_by_category($category) {
        return array_filter(self::get_all(), function($template) use ($category) {
            return $template['category'] === $category;
        });
    }

    /**
     * Get templates by platform
     *
     * @param string $platform
     * @return array
     */
    public static function get_by_platform($platform) {
        return array_filter(self::get_all(), function($template) use ($platform) {
            return $template['platform'] === $platform || $template['platform'] === 'all';
        });
    }

    /**
     * Install default templates to database
     *
     * @return int Number of templates installed
     */
    public static function install_defaults() {
        global $wpdb;
        $table = $wpdb->prefix . ACS_TABLE_PREFIX . 'templates';

        $templates = self::get_all();
        $installed = 0;

        foreach ($templates as $key => $template) {
            $wpdb->insert(
                $table,
                [
                    'name' => $template['name'],
                    'description' => '',
                    'category' => $template['category'],
                    'subcategory' => $template['subcategory'],
                    'template_content' => $template['template_content'],
                    'prompt_template' => $template['prompt_template'],
                    'platform' => $template['platform'],
                    'language' => 'fr',
                    'is_premium' => 0,
                    'is_default' => 1,
                    'usage_count' => 0,
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d']
            );

            if ($wpdb->insert_id) {
                $installed++;
            }
        }

        return $installed;
    }
}
