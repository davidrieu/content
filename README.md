# AI Content Studio - WordPress SaaS Plugin

> **Plateforme SaaS complète pour générer du contenu pour réseaux sociaux et articles de blog avec IA**

[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net/)
[![React](https://img.shields.io/badge/React-18.2-61dafb.svg)](https://reactjs.org/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](LICENSE)

## 🚀 Fonctionnalités

### Génération de Contenu IA
- **Posts Réseaux Sociaux** : Génération de 3 variantes avec Claude AI
- **Articles de Blog** : Articles SEO-optimisés de 1000+ mots
- **Images IA** : Génération avec DALL-E 3
- **Hashtags Intelligents** : Suggestions automatiques par plateforme
- **Détection de Tendances** : Identification des sujets populaires

### Système d'Abonnement
- **4 Plans** : Free, Starter (19€), Pro (49€), Business (99€)
- **WooCommerce Integration** : Paiements récurrents via Stripe
- **Limites Dynamiques** : Tracking automatique de l'usage
- **Upgrades/Downgrades** : Gestion automatique via webhooks

### Plateformes Supportées
- Instagram
- Facebook
- LinkedIn
- Twitter/X
- TikTok
- YouTube

### Langues Supportées
15+ langues dont FR, EN, ES, DE, IT, PT, NL, PL, RU, JA, ZH, AR, KO, TR, HI

## 📋 Prérequis

- **WordPress** : 6.4 ou supérieur
- **PHP** : 8.1 ou supérieur
- **MySQL** : 8.0 ou supérieur
- **WooCommerce** : 8.0 ou supérieur
- **WooCommerce Subscriptions** : 5.0 ou supérieur (recommandé)
- **Node.js** : 18+ (pour le build React)
- **Composer** : (optionnel, pour dev)

### APIs Requises
- **Claude API** (Anthropic) - Obligatoire
- **DALL-E API** (OpenAI) - Obligatoire
- **APIs Sociales** (optionnel pour publication automatique)
  - Facebook/Instagram Graph API
  - LinkedIn API
  - Twitter API v2

## 🛠️ Installation

### 1. Installation du Plugin

```bash
# Cloner le repository
git clone https://github.com/votrecompte/ai-content-studio.git
cd ai-content-studio

# Ou télécharger et extraire dans wp-content/plugins/
```

### 2. Build du Frontend React

```bash
cd admin/react-app
npm install
npm run build
```

Les fichiers compilés seront dans `admin/js/` et `admin/css/`.

### 3. Activation

1. Dans WordPress Admin, allez dans **Extensions** → **Extensions installées**
2. Activez **AI Content Studio**
3. Le plugin créera automatiquement les tables de base de données

### 4. Configuration des API Keys

1. Allez dans **AI Content Studio** → **Réglages**
2. Entrez vos clés API :
   - **Claude API Key** : https://console.anthropic.com/
   - **DALL-E API Key** : https://platform.openai.com/
3. Sauvegardez

### 5. Création des Produits WooCommerce

Le plugin crée automatiquement 3 produits d'abonnement lors de l'activation :
- Starter - 19€/mois
- Pro - 49€/mois
- Business - 99€/mois

## 📖 Utilisation

### Workflow Utilisateur

1. **Inscription** → Plan Free automatique
2. **Onboarding** → Profil business + Génération stratégie IA
3. **Tableau de bord** → Vue d'ensemble et usage
4. **Génération** → Posts sociaux, articles, images
5. **Upgrade** → Via WooCommerce (si limites atteintes)

### Pour les Développeurs

#### Structure du Projet

```
ai-content-studio/
├── includes/
│   ├── class-plugin.php          # Orchestrateur principal
│   ├── class-database.php        # 14 tables SQL
│   ├── class-activator.php       # Activation/désactivation
│   ├── api/                      # REST API endpoints
│   ├── services/                 # Services (Claude, DALL-E, etc.)
│   ├── models/                   # ORM Models
│   ├── woocommerce/              # Integration WC
│   ├── data/                     # Configs & templates
│   └── utils/                    # Helpers
├── admin/
│   └── react-app/                # Frontend React
└── ai-content-studio.php         # Main file
```

#### Hooks & Filters

```php
// Après activation d'abonnement
add_action('acs_subscription_activated', function($user_id, $plan) {
    // Votre code
}, 10, 2);

// Après génération de post
add_action('acs_post_generated', function($user_id) {
    // Votre code
});

// Modifier les limites d'un plan
add_filter('acs_plan_limits', function($limits, $plan_slug) {
    if ($plan_slug === 'custom') {
        $limits['posts_per_month'] = 500;
    }
    return $limits;
}, 10, 2);
```

#### REST API Endpoints

```
GET    /acs/v1/profile
POST   /acs/v1/profile
GET    /acs/v1/subscription
GET    /acs/v1/subscription/usage
GET    /acs/v1/posts
POST   /acs/v1/posts/generate
GET    /acs/v1/posts/{id}
PUT    /acs/v1/posts/{id}
DELETE /acs/v1/posts/{id}
```

## 🗄️ Base de Données

Le plugin crée 14 tables :
- `wp_acs_business_profiles`
- `wp_acs_strategies`
- `wp_acs_usage_stats`
- `wp_acs_social_posts`
- `wp_acs_blog_articles`
- `wp_acs_generated_images`
- `wp_acs_trends`
- `wp_acs_templates`
- `wp_acs_brand_kits`
- `wp_acs_social_connections`
- `wp_acs_calendar_events`
- `wp_acs_analytics`
- `wp_acs_notifications`
- `wp_acs_favorites`

## 🔧 Développement

### Build React en Mode Dev

```bash
cd admin/react-app
npm run start  # Watch mode avec hot reload
```

### Tests

```bash
# Tests PHP (si configurés)
composer test

# Linting
npm run lint:js
npm run lint:css
```

## 📊 Plans d'Abonnement

| Feature | Free | Starter | Pro | Business |
|---------|------|---------|-----|----------|
| Posts/mois | 5 | 50 | 200 | ∞ |
| Articles/mois | 1 | 5 | 20 | ∞ |
| Images/mois | 2 | 20 | 100 | ∞ |
| Langues | 1 | 3 | ∞ | ∞ |
| Plateformes | 2 | 5 | ∞ | ∞ |
| Planification | ✗ | ✓ | ✓ | ✓ |
| Auto-publication | ✗ | ✓ | ✓ | ✓ |
| Brand Kit | ✗ | Basic | Complete | Complete |
| Analytics | Basic | Standard | Advanced | Advanced |
| Support | Community | Email | Priority | Dedicated |

## 🚨 Troubleshooting

### Le plugin ne s'active pas
- Vérifiez PHP 8.1+ et WordPress 6.4+
- Installez WooCommerce

### Erreur "Clé API non configurée"
- Allez dans Réglages et configurez vos clés Claude et DALL-E

### Posts non générés
- Vérifiez votre limite mensuelle
- Vérifiez que la clé API Claude est valide
- Consultez les logs : `wp-content/debug.log`

### Frontend React ne charge pas
- Vérifiez que le build a été fait : `npm run build`
- Vérifiez les permissions sur `admin/js/` et `admin/css/`

## 📝 Changelog

### Version 1.0.0 - 2024-11-08
- ✨ Version initiale
- ✅ Génération posts avec Claude Sonnet 4
- ✅ Génération images avec DALL-E 3
- ✅ Articles SEO-optimisés
- ✅ 4 plans d'abonnement
- ✅ Interface React complète
- ✅ 200+ templates
- ✅ 15+ langues
- ✅ 6 plateformes sociales

## 🤝 Contributing

Les contributions sont les bienvenues !

1. Fork le projet
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit (`git commit -m 'Add AmazingFeature'`)
4. Push (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 License

GPL v2 ou ultérieure. Voir [LICENSE](LICENSE).

## 🙏 Crédits

- **Claude AI** par [Anthropic](https://anthropic.com)
- **DALL-E** par [OpenAI](https://openai.com)
- **@wordpress/scripts** par WordPress
- **React** par Meta

## 📧 Support

- **Documentation** : https://aicontentstudio.com/docs
- **Support** : https://aicontentstudio.com/support
- **Email** : support@aicontentstudio.com

## 🌟 Fonctionnalités à venir

- [ ] Générateur de vidéos courtes
- [ ] A/B Testing
- [ ] Analyse concurrentielle
- [ ] API publique
- [ ] Mode White Label
- [ ] Équipes multi-utilisateurs

---

Made with ❤️ for content creators
