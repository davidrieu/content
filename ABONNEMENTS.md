# Système d'Abonnements - AI Content Studio

## Vue d'ensemble

Le système d'abonnements d'AI Content Studio est intégré avec WooCommerce Subscriptions et permet de proposer 3 plans payants avec des limites d'utilisation différentes.

---

## Plans disponibles

### 🆓 Free Trial (Plan par défaut)
**Prix** : Gratuit
**Limites** :
- 1 post social
- 1 article de blog
- 0 image AI

**Utilisation** :
- Plan attribué automatiquement à l'inscription
- Permet de tester la qualité de génération
- Popup de pricing affichée après génération

---

### 💼 Starter - $29/mois
**Limites** :
- 50 posts sociaux/mois
- 5 articles de blog/mois
- 25 images AI/mois

**Fonctionnalités** :
- Toutes les langues (15+)
- Toutes les plateformes (6)
- Planification de contenu
- Publication automatique
- Support email

**Public cible** : Entrepreneurs, créateurs de contenu, freelances

---

### 🚀 Professional - $59/mois ⭐ POPULAIRE
**Limites** :
- 300 posts sociaux/mois
- 30 articles de blog/mois
- 150 images AI/mois

**Fonctionnalités** :
- Tout du plan Starter
- Analytics avancées
- Analyse de la concurrence (3 concurrents)
- A/B testing
- Support prioritaire (24h)

**Public cible** : Professionnels du marketing, agences (1-3 personnes)

---

### 💎 Business - $149/mois
**Limites** :
- ∞ Posts sociaux ILLIMITÉS
- ∞ Articles de blog ILLIMITÉS
- ∞ Images AI ILLIMITÉES

**Fonctionnalités** :
- Tout du plan Professional
- 5 membres d'équipe
- API access
- Analyse concurrence illimitée
- Support dédié

**Public cible** : Agences marketing, équipes entreprise

---

## Installation et Configuration

### 1. Prérequis

- **WooCommerce** : Installé et activé
- **WooCommerce Subscriptions** : Installé et activé
- **API Keys configurées** : Claude API + DALL-E API (dans Réglages)

### 2. Générer les produits WooCommerce

1. Aller dans **AI Content Studio > Abonnements** dans le backoffice WordPress
2. Cliquer sur **"Créer les produits d'abonnement"**
3. Les 3 produits seront créés automatiquement :
   - `Starter - AI Content Studio` ($29/mois)
   - `Professional - AI Content Studio` ($59/mois)
   - `Business - AI Content Studio` ($149/mois)

4. Les produits sont créés avec :
   - Type : Abonnement mensuel récurrent
   - Période : 1 mois
   - Statut : Publié (mais caché du catalogue)
   - Meta : `_acs_plan_slug` pour identification

### 3. Vérifier les produits

Dans la page **Abonnements**, vous verrez :
- Tableau récapitulatif des plans
- Liste des produits WooCommerce créés
- Liens directs pour modifier chaque produit

---

## Workflow Utilisateur

### 1. Inscription
```
Utilisateur s'inscrit
  ↓
Plan "free_trial" attribué automatiquement
  ↓
Redirection vers onboarding wizard
  ↓
Profil business créé
  ↓
Stratégie de contenu générée
  ↓
Accès au dashboard
```

### 2. Utilisation du Free Trial
```
Utilisateur clique "Générer un post"
  ↓
Génération des 3 variantes de post
  ↓
Popup de pricing s'affiche immédiatement
  ↓
3 plans affichés avec limites claires
  ↓
Options :
  - Choisir un plan → Redirection checkout WooCommerce
  - "Je décide plus tard" → Fermer la popup
```

### 3. Tentative de 2ème génération
```
Utilisateur tente de générer un 2ème post
  ↓
Vérification des limites (can_generate_post())
  ↓
Limite atteinte (1/1 utilisé)
  ↓
Popup de pricing affichée
  ↓
Message : "Vous avez atteint votre limite de posts"
```

### 4. Abonnement à un plan

```
Utilisateur clique "Choisir ce plan"
  ↓
API call: /acs/v1/subscription/checkout-url
  ↓
Panier WooCommerce vidé
  ↓
Produit ajouté au panier
  ↓
Redirection vers /checkout/
  ↓
Utilisateur paie (Stripe, PayPal, etc.)
  ↓
Webhook WooCommerce déclenché
  ↓
Plan utilisateur mis à jour dans DB
  ↓
Usage reset automatiquement
  ↓
Utilisateur peut utiliser ses nouvelles limites
```

---

## Système de Popup de Pricing

### Déclencheurs

La popup `PricingModal` s'affiche dans ces cas :

1. **Après génération du 1er post (Free Trial)**
   - `triggerType: 'post_limit'`
   - Message : "Vous avez atteint votre limite de posts"

2. **Après génération du 1er article (Free Trial)**
   - `triggerType: 'article_limit'`
   - Message : "Vous avez atteint votre limite d'articles"

3. **Tentative de génération au-delà des limites**
   - Popup affichée AVANT la génération
   - Génération bloquée

4. **Clic manuel sur "Upgrade"**
   - `triggerType: 'upgrade'`
   - Depuis le dashboard ou settings

### Utilisation dans React

```javascript
import PricingModal from './components/common/PricingModal';

function MyComponent() {
    const [showPricing, setShowPricing] = useState(false);
    const [triggerType, setTriggerType] = useState('limit_reached');

    const handleGeneratePost = async () => {
        // Vérifier si limites atteintes
        const response = await apiFetch({ path: '/acs/v1/subscription' });

        if (response.data.remaining.posts === 0) {
            setTriggerType('post_limit');
            setShowPricing(true);
            return;
        }

        // Continuer la génération...
    };

    return (
        <>
            <button onClick={handleGeneratePost}>Générer un post</button>

            <PricingModal
                isOpen={showPricing}
                onClose={() => setShowPricing(false)}
                currentPlan={currentUserPlan}
                triggerType={triggerType}
            />
        </>
    );
}
```

---

## Gestion des Webhooks WooCommerce

Les webhooks WooCommerce sont automatiquement gérés par `Subscription_Handler` :

### Événements gérés

| Événement | Action |
|-----------|--------|
| `woocommerce_subscription_status_active` | Plan utilisateur mis à jour + usage reset |
| `woocommerce_subscription_status_cancelled` | Downgrade vers free_trial |
| `woocommerce_subscription_status_expired` | Downgrade vers free_trial |
| `woocommerce_subscription_renewal_payment_complete` | Usage reset mensuel |
| `woocommerce_subscription_renewal_payment_failed` | Notification utilisateur |

### Logs

Tous les événements sont loggés dans :
- **Table** : `wp_acs_logs`
- **Type** : `subscription`
- **Accès** : AI Content Studio > Logs (backoffice)

---

## Vérification des Limites

### Dans le code PHP

```php
use ACS\Services\Subscription_Service;

$subscription_service = new Subscription_Service();

// Vérifier si l'utilisateur peut générer un post
if (!$subscription_service->can_generate_post()) {
    return new WP_Error('limit_reached', 'Limite de posts atteinte');
}

// Vérifier si l'utilisateur peut générer un article
if (!$subscription_service->can_generate_article()) {
    return new WP_Error('limit_reached', 'Limite d\'articles atteinte');
}

// Obtenir les limites restantes
$remaining = $subscription_service->get_remaining_quota();
/*
Array(
    'posts' => 45,     // -1 si illimité
    'articles' => 3,
    'images' => 20
)
*/
```

### Dans React (API)

```javascript
// Obtenir les quotas restants
const response = await apiFetch({
    path: '/acs/v1/subscription/usage'
});

console.log(response.data);
/*
{
    posts: { used: 5, limit: 50, remaining: 45 },
    articles: { used: 2, limit: 5, remaining: 3 },
    images: { used: 5, limit: 25, remaining: 20 }
}
*/
```

---

## Reset Mensuel des Quotas

Le reset automatique se fait via **WP-Cron** :

### Configuration

- **Hook** : `acs_reset_monthly_usage`
- **Fréquence** : Mensuel (1er de chaque mois à 00:00)
- **Classe** : `Usage_Service::reset_all_monthly_usage()`

### Déclencheurs de reset

1. **Automatique** : Cron job mensuel
2. **Renouvellement abonnement** : Webhook WooCommerce
3. **Manuel** : Depuis page Diagnostic (admin)

### Vérification

```php
// Voir la prochaine exécution du cron
$timestamp = wp_next_scheduled('acs_reset_monthly_usage');
echo date('Y-m-d H:i:s', $timestamp);
```

---

## FAQ

### Comment modifier les prix ?

1. Éditer `/includes/config/class-plans-config.php`
2. Modifier les valeurs `price` dans `get_plans()`
3. Aller dans **Abonnements** et cliquer **"Mettre à jour les produits"**
4. Les produits WooCommerce seront mis à jour automatiquement

### Comment modifier les limites ?

1. Éditer `/includes/config/class-plans-config.php`
2. Modifier les valeurs dans `limits` (ex: `posts_per_month`)
3. Les changements sont effectifs immédiatement
4. Les utilisateurs existants gardent leur quota actuel jusqu'au reset

### Comment ajouter un 4ème plan ?

1. Ajouter le plan dans `class-plans-config.php`
2. Ajouter dans `$plan_hierarchy` dans `class-subscription-service.php`
3. Ajouter dans `get_plan_from_subscription()` dans `class-subscription-handler.php`
4. Ajouter dans le composant `PricingModal.js`
5. Recréer les produits WooCommerce depuis **Abonnements**

### Les utilisateurs peuvent-ils upgrader/downgrader ?

- **Upgrade** : Oui, via WooCommerce Subscriptions (switch subscription)
- **Downgrade** : Oui, même système
- **Prorata** : Géré automatiquement par WooCommerce Subscriptions

### Comment tester sans payer ?

1. Créer un compte utilisateur de test
2. Aller dans WooCommerce > Abonnements
3. Créer manuellement un abonnement pour l'utilisateur test
4. Sélectionner le produit et statut "Actif"
5. Le webhook se déclenchera automatiquement

---

## Architecture Technique

### Base de données

```
wp_usermeta:
├── acs_subscription_plan      → 'starter' | 'professional' | 'business' | 'free_trial'
├── acs_subscription_status    → 'active' | 'canceled' | 'expired'
└── acs_wc_subscription_id     → ID de l'abonnement WooCommerce

wp_acs_usage_stats:
├── user_id
├── posts_this_month           → Nombre de posts générés ce mois
├── articles_this_month        → Nombre d'articles générés ce mois
├── images_this_month          → Nombre d'images générées ce mois
└── last_reset_date            → Date du dernier reset

wp_options:
├── acs_product_starter_id     → ID produit WooCommerce Starter
├── acs_product_professional_id → ID produit WooCommerce Professional
└── acs_product_business_id    → ID produit WooCommerce Business
```

### Flux de données

```
Frontend (React)
    ↓ apiFetch
API Endpoints (/acs/v1/subscription/*)
    ↓
Subscription_Service & Usage_Service
    ↓
Plans_Config (configuration)
    ↓
Base de données (usermeta + usage_stats)
```

---

## Support et Logs

### Consulter les logs

1. Aller dans **AI Content Studio > Logs**
2. Filtrer par type : `subscription`
3. Voir tous les événements :
   - Activation abonnement
   - Renouvellement
   - Annulation
   - Expiration
   - Changement de plan

### Debug mode

Activer dans **Réglages** :
- Cocher "Mode Debug"
- Les logs seront plus verbeux
- Visible dans la console navigateur + logs backend

---

## Prochaines Étapes

✅ Plans configurés
✅ Produits WooCommerce créés
✅ Popup de pricing implémentée
✅ Webhooks configurés
✅ Reset automatique activé

**À faire par l'utilisateur** :
1. Configurer les clés API (Claude + DALL-E)
2. Générer les produits WooCommerce depuis la page Abonnements
3. Configurer le mode de paiement dans WooCommerce (Stripe/PayPal)
4. Tester le workflow complet avec un compte de test
5. Personnaliser les emails WooCommerce (templates)

---

**Documentation créée le 11 novembre 2025**
**Version du plugin : AI Content Studio 1.0.0**
