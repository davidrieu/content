<?php
/**
 * Translation Service - Automatic AI Translation
 *
 * @package ACS\Services
 */

namespace ACS\Services;

use ACS\Data\Language_Config;
use ACS\Utils\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Translation_Service class
 */
class Translation_Service {

    /**
     * Get all translatable strings from the app
     *
     * @return array
     */
    public static function get_translatable_strings() {
        return [
            // Auth & Onboarding
            'Bienvenue' => 'Welcome',
            'Connexion' => 'Login',
            'Inscription' => 'Register',
            'Email' => 'Email',
            'Mot de passe' => 'Password',
            'Se connecter' => 'Sign in',
            "S'inscrire" => 'Sign up',
            'Déconnexion' => 'Logout',

            // Dashboard
            'Tableau de bord' => 'Dashboard',
            'Actions rapides' => 'Quick actions',
            'Votre plan' => 'Your plan',
            'Posts générés' => 'Generated posts',
            'Articles de blog' => 'Blog articles',
            'Images IA' => 'AI Images',
            'disponibles' => 'available',

            // Navigation
            'Générer des posts' => 'Generate posts',
            'Créer un article' => 'Create article',
            'Stratégie Auto' => 'Auto Strategy',
            'Calendrier' => 'Calendar',
            'Bibliothèque' => 'Library',
            'Mes articles' => 'My articles',
            'Tendances SEO' => 'SEO Trends',
            'Paramètres' => 'Settings',
            'Réseaux Sociaux' => 'Social Media',
            'SEO & Blog' => 'SEO & Blog',

            // Actions
            'Nouveau post' => 'New post',
            'Nouvel article' => 'New article',
            'Générer image' => 'Generate image',
            'Voir tendances' => 'View trends',
            'Upgrader' => 'Upgrade',
            'Gérer' => 'Manage',

            // Pricing
            'Choisissez votre plan' => 'Choose your plan',
            'Choisir ce plan' => 'Choose this plan',
            'POPULAIRE' => 'POPULAR',
            'Pour les entrepreneurs et créateurs' => 'For entrepreneurs and creators',
            'Pour les professionnels du marketing' => 'For marketing professionals',
            'Pour les agences et entreprises' => 'For agencies and businesses',
            'Posts ILLIMITÉS' => 'UNLIMITED Posts',
            'Articles ILLIMITÉS' => 'UNLIMITED Articles',
            'Images ILLIMITÉES' => 'UNLIMITED Images',
            'Toutes les langues' => 'All languages',
            'Toutes les plateformes' => 'All platforms',
            'Planification & calendrier' => 'Scheduling & calendar',
            'Support email' => 'Email support',
            'Analytics avancées' => 'Advanced analytics',
            'Analyse de la concurrence' => 'Competitor analysis',
            'Support prioritaire' => 'Priority support',
            'Support dédié' => 'Dedicated support',
            'Sans engagement' => 'No commitment',
            'Garantie 14 jours' => '14-day guarantee',
            'Paiement 100% sécurisé' => '100% secure payment',
            'créateurs de contenu' => 'content creators',
            'posts générés ce mois' => 'posts generated this month',
            'satisfaction client' => 'customer satisfaction',

            // Messages
            'Chargement...' => 'Loading...',
            'Redirection...' => 'Redirecting...',
            'Erreur' => 'Error',
            'Succès' => 'Success',
            'Je décide plus tard' => "I'll decide later",
            'Annuler mon abonnement' => 'Cancel my subscription',

            // Testimonials
            "RunnWrite AI a transformé ma stratégie de contenu. Je génère en 10 minutes ce qui me prenait 3 heures !" => "RunnWrite AI transformed my content strategy. I generate in 10 minutes what used to take me 3 hours!",
            "La qualité des articles générés est impressionnante. Mes clients adorent le contenu que je produis maintenant." => "The quality of the generated articles is impressive. My clients love the content I produce now.",
            "ROI incroyable. Le temps gagné me permet de gérer 3x plus de clients avec la même équipe." => "Incredible ROI. The time saved allows me to manage 3x more clients with the same team.",
            "Marie L." => "Marie L.",
            "Thomas D." => "Thomas D.",
            "Sophie M." => "Sophie M.",
            "Social Media Manager" => "Social Media Manager",
            "Freelance Content Creator" => "Freelance Content Creator",
            "Agence Marketing" => "Marketing Agency",

            // Special offer
            "🎉 Offre spéciale : Rejoignez-nous maintenant et bénéficiez de votre premier mois avec une assistance personnalisée gratuite !" => "🎉 Special offer: Join us now and get your first month with free personalized assistance!",

            // Limits
            "Vous avez atteint votre limite d'articles" => "You've reached your article limit",
            "Vous avez atteint votre limite de posts" => "You've reached your post limit",
            "Passez à un plan supérieur pour générer plus d'articles de blog" => "Upgrade to generate more blog articles",
            "Passez à un plan supérieur pour générer plus de posts sociaux" => "Upgrade to generate more social posts",
            "Améliorez votre plan pour continuer" => "Upgrade your plan to continue",
        ];
    }

    /**
     * Translate a string to target language using Claude AI
     *
     * @param string $text Original text in French
     * @param string $target_lang Target language code
     * @return string Translated text
     */
    public static function translate_with_ai($text, $target_lang) {
        $lang_config = Language_Config::get($target_lang);

        if (!$lang_config) {
            return $text;
        }

        $api_key = get_option('acs_claude_api_key', '');

        if (empty($api_key)) {
            return $text;
        }

        $prompt = sprintf(
            "Translate the following text from French to %s. Keep the same tone and context. Only return the translation, nothing else.\n\nText to translate: \"%s\"",
            $lang_config['native_name'],
            $text
        );

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode([
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 1024,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            Logger::error('Translation API error', ['error' => $response->get_error_message()]);
            return $text;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['content'][0]['text'])) {
            return trim($body['content'][0]['text']);
        }

        return $text;
    }

    /**
     * Get or generate translation file for a language
     *
     * @param string $lang_code Language code
     * @return array Translations
     */
    public static function get_translations($lang_code) {
        // Check if translation file exists
        $translations_file = ACS_TRANSLATIONS_DIR . "/translations-{$lang_code}.json";

        if (file_exists($translations_file)) {
            $content = file_get_contents($translations_file);
            return json_decode($content, true);
        }

        // Generate translations if they don't exist
        return self::generate_translations($lang_code);
    }

    /**
     * Generate all translations for a language
     *
     * @param string $lang_code Language code
     * @return array Translations
     */
    public static function generate_translations($lang_code) {
        $strings = self::get_translatable_strings();
        $translations = [];

        Logger::info("Generating translations for language: {$lang_code}");

        foreach ($strings as $french => $english) {
            // If target is English, use the English translation directly
            if ($lang_code === 'en') {
                $translations[$french] = $english;
            } else {
                // Use AI translation for other languages
                $translated = self::translate_with_ai($french, $lang_code);
                $translations[$french] = $translated;

                // Small delay to avoid rate limiting
                usleep(100000); // 0.1 second
            }
        }

        // Save translations to file
        self::save_translations($lang_code, $translations);

        return $translations;
    }

    /**
     * Save translations to file
     *
     * @param string $lang_code Language code
     * @param array $translations Translations
     * @return bool Success
     */
    public static function save_translations($lang_code, $translations) {
        $languages_dir = ACS_TRANSLATIONS_DIR;

        if (!file_exists($languages_dir)) {
            mkdir($languages_dir, 0755, true);
        }

        $file = $languages_dir . "/translations-{$lang_code}.json";
        $content = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return file_put_contents($file, $content) !== false;
    }

    /**
     * Translate a string
     *
     * @param string $string String to translate
     * @param string $lang_code Language code (defaults to user's language)
     * @return string Translated string
     */
    public static function __($string, $lang_code = null) {
        if (!$lang_code) {
            $lang_code = get_user_meta(get_current_user_id(), 'acs_user_language', true);
        }

        if (!$lang_code || $lang_code === 'fr') {
            return $string;
        }

        $translations = self::get_translations($lang_code);

        return $translations[$string] ?? $string;
    }
}
