/**
 * Content Templates and Types
 * Bibliothèque de templates intelligents pour la génération de contenu
 */

export const CONTENT_TYPES = {
    EDUCATIONAL: {
        id: 'educational',
        label: 'Éducatif',
        icon: '📚',
        description: 'Tips, tutoriels, conseils pratiques',
        color: '#6366F1',
        emoji: '💡',
    },
    PROMOTIONAL: {
        id: 'promotional',
        label: 'Promotionnel',
        icon: '🎯',
        description: 'Offres, nouveautés, produits',
        color: '#EC4899',
        emoji: '🎁',
    },
    ENGAGEMENT: {
        id: 'engagement',
        label: 'Engagement',
        icon: '💬',
        description: 'Questions, sondages, interactions',
        color: '#8B5CF6',
        emoji: '🤝',
    },
    STORYTELLING: {
        id: 'storytelling',
        label: 'Storytelling',
        icon: '📖',
        description: 'Histoires, behind-the-scenes, témoignages',
        color: '#10B981',
        emoji: '✨',
    },
    INSPIRATION: {
        id: 'inspiration',
        label: 'Inspirationnel',
        icon: '🌟',
        description: 'Citations, motivation, inspiration',
        color: '#F59E0B',
        emoji: '🚀',
    },
    NEWS: {
        id: 'news',
        label: 'Actualités',
        icon: '📰',
        description: 'News de l\'industrie, tendances',
        color: '#06B6D4',
        emoji: '📢',
    },
    UGC: {
        id: 'ugc',
        label: 'Contenu Utilisateur',
        icon: '👥',
        description: 'Avis clients, témoignages, reviews',
        color: '#EF4444',
        emoji: '⭐',
    },
};

export const POST_TEMPLATES = {
    // ========== ÉDUCATIF ==========
    how_to: {
        id: 'how_to',
        name: 'Comment faire...',
        type: 'educational',
        description: 'Guide pratique étape par étape',
        structure: [
            'Hook accrocheur avec problème',
            'Introduction du sujet',
            '3-5 étapes numérotées',
            'Conseil bonus',
            'Call-to-action',
        ],
        prompt: `Crée un post de type "Comment faire" sur le sujet : {{topic}}

Secteur: {{sector}}
Ton: {{tone}}
Public cible: {{target_audience}}

Structure:
1. Hook accrocheur qui présente un problème commun
2. Introduction brève (1-2 phrases)
3. Liste numérotée de 3-5 étapes concrètes
4. Conseil bonus ou astuce
5. Call-to-action engageant

Le post doit être pratique, actionnable et facile à suivre.`,
        platforms: ['instagram', 'linkedin', 'facebook'],
    },

    tips_list: {
        id: 'tips_list',
        name: 'Liste de conseils',
        type: 'educational',
        description: 'X conseils/astuces sur un sujet',
        structure: [
            'Introduction du thème',
            '5-7 conseils numérotés',
            'Encouragement à passer à l\'action',
        ],
        prompt: `Crée une liste de 5 conseils sur : {{topic}}

Secteur: {{sector}}
Ton: {{tone}}
Audience: {{target_audience}}

Format:
- Introduction accrocheuse
- 5 conseils numérotés avec émojis
- Chaque conseil en 1-2 phrases max
- Conclusion avec CTA

Rends ça scannable et facile à digérer!`,
        platforms: ['instagram', 'facebook', 'twitter', 'linkedin'],
    },

    mistake_avoid: {
        id: 'mistake_avoid',
        name: 'Erreurs à éviter',
        type: 'educational',
        description: 'Les erreurs communes et comment les éviter',
        structure: [
            'Hook sur erreurs communes',
            '3-5 erreurs avec solutions',
            'Encouragement positif',
        ],
        prompt: `Crée un post sur les erreurs à éviter concernant : {{topic}}

Secteur: {{sector}}
Ton: {{tone}}

Structure:
1. Hook: "Les X erreurs que font 90% des..."
2. Liste de 3-5 erreurs communes
3. Pour chaque erreur, donne la solution
4. Message positif de conclusion
5. CTA

Ton empathique et non culpabilisant.`,
        platforms: ['instagram', 'linkedin', 'facebook'],
    },

    // ========== PROMOTIONNEL ==========
    product_launch: {
        id: 'product_launch',
        name: 'Lancement produit',
        type: 'promotional',
        description: 'Annonce de nouveau produit/service',
        structure: [
            'Teasing accrocheur',
            'Présentation du produit',
            'Bénéfices clés',
            'CTA fort',
        ],
        prompt: `Crée une annonce de lancement pour : {{topic}}

Secteur: {{sector}}
Ton: {{tone}}
Audience: {{target_audience}}

Structure:
1. Hook excitant qui crée de l'anticipation
2. Présentation du produit/service
3. 3 bénéfices principaux avec émojis
4. Élément d'urgence ou exclusivité
5. CTA clair avec lien/action

Crée de l'enthousiasme sans être trop vendeur!`,
        platforms: ['instagram', 'facebook', 'linkedin'],
    },

    special_offer: {
        id: 'special_offer',
        name: 'Offre spéciale',
        type: 'promotional',
        description: 'Promotion, réduction, offre limitée',
        structure: [
            'Annonce de l\'offre',
            'Détails de la promo',
            'Urgence/rareté',
            'CTA immédiat',
        ],
        prompt: `Crée un post promotionnel pour une offre spéciale : {{topic}}

Secteur: {{sector}}
Ton: {{tone}}

Structure:
1. Hook urgent et accrocheur
2. Détails de l'offre (% réduction, bonus, etc.)
3. Bénéfices pour le client
4. Élément de rareté (places limitées, temps limité)
5. CTA fort et clair

Utilise des émojis pour attirer l'attention!`,
        platforms: ['instagram', 'facebook', 'twitter'],
    },

    // ========== ENGAGEMENT ==========
    question: {
        id: 'question',
        name: 'Question ouverte',
        type: 'engagement',
        description: 'Question pour stimuler les commentaires',
        structure: [
            'Contexte court',
            'Question engageante',
            'Options/exemples',
        ],
        prompt: `Crée un post avec une question engageante sur : {{topic}}

Secteur: {{sector}}
Ton: {{tone}}
Audience: {{target_audience}}

Structure:
1. Contexte court et relatable (2-3 phrases)
2. Question principale claire et ouverte
3. Options ou exemples de réponses
4. Encouragement à commenter
5. Émoji pour chaque option

La question doit inviter à l'interaction!`,
        platforms: ['instagram', 'facebook', 'linkedin'],
    },

    poll: {
        id: 'poll',
        name: 'Sondage',
        type: 'engagement',
        description: 'Sondage avec options A/B ou multiple',
        structure: [
            'Question de sondage',
            'Options claires',
            'Invitation à voter',
        ],
        prompt: `Crée un post de sondage sur : {{topic}}

Secteur: {{sector}}
Ton: {{tone}}

Structure:
1. Hook qui explique le sujet
2. Question de sondage claire
3. 2-4 options avec émojis
4. "Vote dans les commentaires!"
5. Pourquoi c'est intéressant de participer

Rends ça fun et facile à répondre!`,
        platforms: ['instagram', 'facebook', 'twitter', 'linkedin'],
    },

    fill_blank: {
        id: 'fill_blank',
        name: 'Complète la phrase',
        type: 'engagement',
        description: 'Phrase à compléter par l\'audience',
        structure: [
            'Phrase à compléter',
            'Contexte/exemples',
            'Encouragement',
        ],
        prompt: `Crée un post "Complète la phrase" sur : {{topic}}

Secteur: {{sector}}
Ton: {{tone}}

Format:
1. Introduction rapide
2. Phrase à compléter avec "________"
3. 1-2 exemples pour inspirer
4. Invitation chaleureuse à participer
5. Promesse de répondre aux commentaires

Crée une phrase amusante et relatable!`,
        platforms: ['instagram', 'facebook', 'linkedin'],
    },

    // ========== STORYTELLING ==========
    behind_scenes: {
        id: 'behind_scenes',
        name: 'Coulisses',
        type: 'storytelling',
        description: 'Behind-the-scenes, processus, quotidien',
        structure: [
            'Accroche sur l\'envers du décor',
            'Histoire/processus',
            'Leçon ou insight',
            'Humanisation',
        ],
        prompt: `Crée un post "behind-the-scenes" sur : {{topic}}

Secteur: {{sector}}
Ton: {{tone}}
Audience: {{target_audience}}

Structure:
1. Hook qui intrigue ("Ce que vous ne voyez pas...")
2. Raconte un processus ou moment quotidien
3. Détails authentiques et humains
4. Leçon apprise ou insight
5. Remerciement ou question à l'audience

Sois authentique et personnel!`,
        platforms: ['instagram', 'facebook', 'linkedin'],
    },

    customer_story: {
        id: 'customer_story',
        name: 'Histoire client',
        type: 'storytelling',
        description: 'Témoignage, success story, transformation',
        structure: [
            'Présentation du client',
            'Problème initial',
            'Solution/transformation',
            'Résultat',
        ],
        prompt: `Crée une success story client sur : {{topic}}

Secteur: {{sector}}
Ton: {{tone}}

Structure du storytelling:
1. Présentation du client (prénom ou "Un client")
2. Situation de départ (problème/défi)
3. Solution apportée
4. Transformation/résultats concrets
5. Citation fictive du client
6. CTA doux

Rends l'histoire relatable et inspirante!`,
        platforms: ['instagram', 'facebook', 'linkedin'],
    },

    // ========== INSPIRATION ==========
    motivation: {
        id: 'motivation',
        name: 'Message motivant',
        type: 'inspiration',
        description: 'Citation, motivation, encouragement',
        structure: [
            'Citation ou affirmation',
            'Explication/contexte',
            'Application pratique',
        ],
        prompt: `Crée un post inspirant sur : {{topic}}

Secteur: {{sector}}
Ton: {{tone}}
Audience: {{target_audience}}

Structure:
1. Citation puissante ou affirmation
2. Contexte court expliquant pourquoi c'est important
3. Application concrète pour ton audience
4. Encouragement personnel
5. Question ou CTA doux

Inspire sans être cliché!`,
        platforms: ['instagram', 'facebook', 'linkedin'],
    },

    // ========== NEWS ==========
    industry_news: {
        id: 'industry_news',
        name: 'Actualité du secteur',
        type: 'news',
        description: 'News, tendances, changements dans l\'industrie',
        structure: [
            'Annonce de la news',
            'Détails importants',
            'Impact/analyse',
            'Avis/recommandation',
        ],
        prompt: `Crée un post d'actualité sur : {{topic}}

Secteur: {{sector}}
Ton: {{tone}}

Structure:
1. Hook annonçant la news
2. Faits principaux (qui, quoi, quand)
3. Ce que ça change pour ton audience
4. Ton avis ou analyse brève
5. Question pour engager

Reste factuel mais apporte ta perspective!`,
        platforms: ['linkedin', 'facebook', 'twitter'],
    },
};

export const BEST_POSTING_TIMES = {
    instagram: {
        business: {
            best_days: ['Mardi', 'Mercredi', 'Jeudi'],
            best_times: ['11h-13h', '19h-21h'],
            avoid: ['Dimanche matin', 'Nuit'],
        },
        creator: {
            best_days: ['Mercredi', 'Vendredi', 'Dimanche'],
            best_times: ['12h-14h', '17h-19h', '20h-22h'],
            avoid: ['Lundi matin'],
        },
    },
    facebook: {
        business: {
            best_days: ['Mercredi', 'Jeudi', 'Vendredi'],
            best_times: ['9h-10h', '12h-14h'],
            avoid: ['Weekend après-midi'],
        },
    },
    linkedin: {
        business: {
            best_days: ['Mardi', 'Mercredi', 'Jeudi'],
            best_times: ['7h-9h', '12h-13h', '17h-18h'],
            avoid: ['Weekend'],
        },
        freelance: {
            best_days: ['Mardi', 'Mercredi', 'Jeudi'],
            best_times: ['8h-10h', '17h-18h'],
            avoid: ['Weekend', 'Vendredi soir'],
        },
    },
    twitter: {
        all: {
            best_days: ['Tous les jours'],
            best_times: ['8h-10h', '12h-13h', '17h-18h'],
            frequency: 'Multiple fois par jour',
        },
    },
};

export const CONTENT_MIX_RECOMMENDATIONS = {
    brand_awareness: {
        educational: 40,
        storytelling: 30,
        engagement: 20,
        promotional: 10,
    },
    lead_generation: {
        educational: 35,
        promotional: 30,
        engagement: 20,
        storytelling: 15,
    },
    sales: {
        promotional: 40,
        educational: 30,
        engagement: 15,
        storytelling: 15,
    },
    engagement: {
        engagement: 40,
        storytelling: 25,
        educational: 20,
        promotional: 15,
    },
    authority: {
        educational: 45,
        news: 25,
        engagement: 20,
        promotional: 10,
    },
};

export const HASHTAG_STRATEGIES = {
    instagram: {
        optimal_count: '20-30',
        mix: '70% niche + 20% broad + 10% branded',
        tips: [
            'Utilise tous les 30 hashtags disponibles',
            'Mélange hashtags populaires et de niche',
            'Crée ton hashtag de marque',
            'Cache-les en premier commentaire',
        ],
    },
    linkedin: {
        optimal_count: '3-5',
        mix: '100% professionnels et pertinents',
        tips: [
            'Utilise des hashtags professionnels',
            'Moins c\'est plus sur LinkedIn',
            'Suis les hashtags de ton industrie',
        ],
    },
    twitter: {
        optimal_count: '1-2',
        mix: 'Hashtags trending ou de niche',
        tips: [
            'Maximum 2 hashtags par tweet',
            'Utilise les trending topics',
            'Participe aux conversations',
        ],
    },
    facebook: {
        optimal_count: '1-3',
        mix: 'Optionnel, peu utilisés',
        tips: [
            'Les hashtags sont moins importants',
            'Concentre-toi sur le contenu',
        ],
    },
};

export const PLATFORM_SPECS = {
    instagram: {
        max_length: 2200,
        optimal_length: '125-150 caractères',
        image_ratio: ['1:1', '4:5', '9:16'],
        features: ['Carousel', 'Reels', 'Stories'],
    },
    facebook: {
        max_length: 63206,
        optimal_length: '40-80 caractères',
        image_ratio: ['1.91:1', '1:1'],
        features: ['Video', 'Live', 'Stories'],
    },
    linkedin: {
        max_length: 3000,
        optimal_length: '150-300 caractères',
        image_ratio: ['1.91:1', '1:1'],
        features: ['Article', 'Document', 'Poll'],
    },
    twitter: {
        max_length: 280,
        optimal_length: '71-100 caractères',
        image_ratio: ['16:9', '1:1'],
        features: ['Thread', 'Poll', 'Spaces'],
    },
};
