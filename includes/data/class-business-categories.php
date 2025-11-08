<?php
/**
 * Business Categories Data
 *
 * @package ACS\Data
 */

namespace ACS\Data;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Business_Categories class
 */
class Business_Categories {

    /**
     * Get all business categories
     *
     * @return array
     */
    public static function get_all() {
        return [
            'food_beverage' => [
                'name' => __('Alimentation & Boissons', 'ai-content-studio'),
                'subcategories' => [
                    'restaurant' => __('Restaurant', 'ai-content-studio'),
                    'cafe' => __('Café', 'ai-content-studio'),
                    'bakery' => __('Boulangerie', 'ai-content-studio'),
                    'food_truck' => __('Food Truck', 'ai-content-studio'),
                    'catering' => __('Traiteur', 'ai-content-studio'),
                    'bar' => __('Bar', 'ai-content-studio'),
                    'brewery' => __('Brasserie', 'ai-content-studio'),
                    'winery' => __('Domaine viticole', 'ai-content-studio'),
                    'organic_food' => __('Alimentation bio', 'ai-content-studio'),
                    'vegan_food' => __('Alimentation végane', 'ai-content-studio'),
                ],
            ],
            'retail' => [
                'name' => __('Commerce de détail', 'ai-content-studio'),
                'subcategories' => [
                    'clothing' => __('Vêtements', 'ai-content-studio'),
                    'shoes' => __('Chaussures', 'ai-content-studio'),
                    'accessories' => __('Accessoires', 'ai-content-studio'),
                    'jewelry' => __('Bijoux', 'ai-content-studio'),
                    'cosmetics' => __('Cosmétiques', 'ai-content-studio'),
                    'electronics' => __('Électronique', 'ai-content-studio'),
                    'home_decor' => __('Décoration intérieure', 'ai-content-studio'),
                    'furniture' => __('Meubles', 'ai-content-studio'),
                    'books' => __('Librairie', 'ai-content-studio'),
                    'toys' => __('Jouets', 'ai-content-studio'),
                    'sports_equipment' => __('Équipement sportif', 'ai-content-studio'),
                    'gift_shop' => __('Boutique de cadeaux', 'ai-content-studio'),
                ],
            ],
            'health_wellness' => [
                'name' => __('Santé & Bien-être', 'ai-content-studio'),
                'subcategories' => [
                    'gym' => __('Salle de sport', 'ai-content-studio'),
                    'yoga_studio' => __('Studio de yoga', 'ai-content-studio'),
                    'spa' => __('Spa', 'ai-content-studio'),
                    'massage' => __('Massage', 'ai-content-studio'),
                    'nutritionist' => __('Nutritionniste', 'ai-content-studio'),
                    'personal_trainer' => __('Coach sportif', 'ai-content-studio'),
                    'physiotherapy' => __('Physiothérapie', 'ai-content-studio'),
                    'wellness_coach' => __('Coach bien-être', 'ai-content-studio'),
                    'meditation' => __('Méditation', 'ai-content-studio'),
                ],
            ],
            'beauty' => [
                'name' => __('Beauté & Esthétique', 'ai-content-studio'),
                'subcategories' => [
                    'hair_salon' => __('Salon de coiffure', 'ai-content-studio'),
                    'barber' => __('Barbier', 'ai-content-studio'),
                    'nail_salon' => __('Salon de manucure', 'ai-content-studio'),
                    'beauty_salon' => __('Institut de beauté', 'ai-content-studio'),
                    'makeup_artist' => __('Maquilleur/Maquilleuse', 'ai-content-studio'),
                    'tattoo' => __('Tatoueur', 'ai-content-studio'),
                    'esthetics' => __('Esthéticienne', 'ai-content-studio'),
                ],
            ],
            'professional_services' => [
                'name' => __('Services professionnels', 'ai-content-studio'),
                'subcategories' => [
                    'lawyer' => __('Avocat', 'ai-content-studio'),
                    'accountant' => __('Comptable', 'ai-content-studio'),
                    'consultant' => __('Consultant', 'ai-content-studio'),
                    'architect' => __('Architecte', 'ai-content-studio'),
                    'real_estate' => __('Immobilier', 'ai-content-studio'),
                    'insurance' => __('Assurance', 'ai-content-studio'),
                    'financial_advisor' => __('Conseiller financier', 'ai-content-studio'),
                    'marketing_agency' => __('Agence marketing', 'ai-content-studio'),
                    'web_design' => __('Design web', 'ai-content-studio'),
                    'photography' => __('Photographie', 'ai-content-studio'),
                    'videography' => __('Vidéographie', 'ai-content-studio'),
                ],
            ],
            'education' => [
                'name' => __('Éducation & Formation', 'ai-content-studio'),
                'subcategories' => [
                    'online_courses' => __('Cours en ligne', 'ai-content-studio'),
                    'tutoring' => __('Tutorat', 'ai-content-studio'),
                    'language_school' => __('École de langues', 'ai-content-studio'),
                    'music_school' => __('École de musique', 'ai-content-studio'),
                    'dance_school' => __('École de danse', 'ai-content-studio'),
                    'coaching' => __('Coaching', 'ai-content-studio'),
                    'training' => __('Formation professionnelle', 'ai-content-studio'),
                ],
            ],
            'technology' => [
                'name' => __('Technologie & IT', 'ai-content-studio'),
                'subcategories' => [
                    'software_dev' => __('Développement logiciel', 'ai-content-studio'),
                    'app_dev' => __('Développement d\'applications', 'ai-content-studio'),
                    'it_support' => __('Support IT', 'ai-content-studio'),
                    'cybersecurity' => __('Cybersécurité', 'ai-content-studio'),
                    'ai_ml' => __('IA & Machine Learning', 'ai-content-studio'),
                    'saas' => __('SaaS', 'ai-content-studio'),
                    'tech_startup' => __('Startup tech', 'ai-content-studio'),
                ],
            ],
            'creative' => [
                'name' => __('Créatif & Artistique', 'ai-content-studio'),
                'subcategories' => [
                    'graphic_design' => __('Design graphique', 'ai-content-studio'),
                    'illustration' => __('Illustration', 'ai-content-studio'),
                    'artist' => __('Artiste', 'ai-content-studio'),
                    'musician' => __('Musicien', 'ai-content-studio'),
                    'writer' => __('Écrivain', 'ai-content-studio'),
                    'content_creator' => __('Créateur de contenu', 'ai-content-studio'),
                    'influencer' => __('Influenceur', 'ai-content-studio'),
                ],
            ],
            'hospitality' => [
                'name' => __('Hôtellerie & Tourisme', 'ai-content-studio'),
                'subcategories' => [
                    'hotel' => __('Hôtel', 'ai-content-studio'),
                    'bed_breakfast' => __('Chambre d\'hôtes', 'ai-content-studio'),
                    'vacation_rental' => __('Location de vacances', 'ai-content-studio'),
                    'travel_agency' => __('Agence de voyage', 'ai-content-studio'),
                    'tour_guide' => __('Guide touristique', 'ai-content-studio'),
                ],
            ],
            'automotive' => [
                'name' => __('Automobile', 'ai-content-studio'),
                'subcategories' => [
                    'car_dealer' => __('Concessionnaire auto', 'ai-content-studio'),
                    'auto_repair' => __('Garage automobile', 'ai-content-studio'),
                    'car_wash' => __('Lavage auto', 'ai-content-studio'),
                    'auto_parts' => __('Pièces automobiles', 'ai-content-studio'),
                ],
            ],
            'home_services' => [
                'name' => __('Services à domicile', 'ai-content-studio'),
                'subcategories' => [
                    'cleaning' => __('Nettoyage', 'ai-content-studio'),
                    'plumbing' => __('Plomberie', 'ai-content-studio'),
                    'electrician' => __('Électricien', 'ai-content-studio'),
                    'landscaping' => __('Paysagisme', 'ai-content-studio'),
                    'pest_control' => __('Lutte antiparasitaire', 'ai-content-studio'),
                    'moving' => __('Déménagement', 'ai-content-studio'),
                ],
            ],
            'ecommerce' => [
                'name' => __('E-commerce', 'ai-content-studio'),
                'subcategories' => [
                    'online_store' => __('Boutique en ligne', 'ai-content-studio'),
                    'dropshipping' => __('Dropshipping', 'ai-content-studio'),
                    'marketplace' => __('Marketplace', 'ai-content-studio'),
                    'subscription_box' => __('Box par abonnement', 'ai-content-studio'),
                ],
            ],
            'nonprofit' => [
                'name' => __('Association & Non-profit', 'ai-content-studio'),
                'subcategories' => [
                    'charity' => __('Charité', 'ai-content-studio'),
                    'ngo' => __('ONG', 'ai-content-studio'),
                    'community' => __('Organisation communautaire', 'ai-content-studio'),
                ],
            ],
            'entertainment' => [
                'name' => __('Divertissement', 'ai-content-studio'),
                'subcategories' => [
                    'event_planning' => __('Organisation d\'événements', 'ai-content-studio'),
                    'dj' => __('DJ', 'ai-content-studio'),
                    'entertainment' => __('Divertissement', 'ai-content-studio'),
                    'cinema' => __('Cinéma', 'ai-content-studio'),
                    'theater' => __('Théâtre', 'ai-content-studio'),
                ],
            ],
            'pets' => [
                'name' => __('Animaux', 'ai-content-studio'),
                'subcategories' => [
                    'pet_store' => __('Animalerie', 'ai-content-studio'),
                    'grooming' => __('Toilettage', 'ai-content-studio'),
                    'veterinary' => __('Vétérinaire', 'ai-content-studio'),
                    'pet_sitting' => __('Garde d\'animaux', 'ai-content-studio'),
                ],
            ],
            'other' => [
                'name' => __('Autre', 'ai-content-studio'),
                'subcategories' => [
                    'other' => __('Autre', 'ai-content-studio'),
                ],
            ],
        ];
    }

    /**
     * Get category names only
     *
     * @return array
     */
    public static function get_category_names() {
        $categories = self::get_all();
        $names = [];

        foreach ($categories as $key => $category) {
            $names[$key] = $category['name'];
        }

        return $names;
    }

    /**
     * Get subcategories for a category
     *
     * @param string $category
     * @return array
     */
    public static function get_subcategories($category) {
        $categories = self::get_all();
        return $categories[$category]['subcategories'] ?? [];
    }

    /**
     * Get all as flat list
     *
     * @return array
     */
    public static function get_flat_list() {
        $categories = self::get_all();
        $flat = [];

        foreach ($categories as $cat_key => $category) {
            foreach ($category['subcategories'] as $sub_key => $sub_name) {
                $flat[$cat_key . '_' . $sub_key] = $category['name'] . ' - ' . $sub_name;
            }
        }

        return $flat;
    }
}
