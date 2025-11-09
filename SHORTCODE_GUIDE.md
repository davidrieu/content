# Guide d'Utilisation du Shortcode AI Content Studio

## 🎯 Utilisation Basique

Le plugin AI Content Studio inclut un shortcode `[ai_content_studio]` qui permet d'afficher l'interface client sur n'importe quelle page WordPress.

### Installation Rapide

**Méthode 1: Manuelle (Shortcode)**
1. Créez une nouvelle page dans WordPress (ex: "Mon Studio de Contenu")
2. Ajoutez le shortcode dans le contenu:
   ```
   [ai_content_studio]
   ```
3. Publiez la page
4. Les utilisateurs connectés verront l'interface complète!

**Méthode 2: Automatique (Configuration Globale)**
1. Créez une nouvelle page WordPress (ex: "Mon Studio")
2. Allez dans "AI Content Studio → Réglages"
3. Dans "Page Frontend par défaut", sélectionnez votre page
4. Sauvegardez
5. L'interface s'affichera automatiquement sur cette page, sans avoir besoin d'ajouter le shortcode!

💡 **Astuce**: La méthode automatique est idéale pour avoir une page dédiée qui affiche toujours l'interface, même si le contenu de la page change.

## 🌍 Configuration Globale (Shortcode Automatique)

### Qu'est-ce que la Configuration Globale?

Au lieu d'ajouter manuellement le shortcode `[ai_content_studio]` sur une page, vous pouvez configurer une page qui affichera **automatiquement** l'interface.

### Comment Configurer

1. **Accédez aux réglages**
   - Allez dans votre admin WordPress
   - Cliquez sur "AI Content Studio → Réglages"

2. **Sélectionnez la page par défaut**
   - Trouvez le paramètre "Page Frontend par défaut"
   - Sélectionnez une page existante dans la liste déroulante
   - Cliquez "Enregistrer les modifications"

3. **C'est tout!**
   - Visitez la page sélectionnée
   - L'interface AI Content Studio s'affichera automatiquement
   - Pas besoin d'ajouter le shortcode manuellement!

### Avantages de la Configuration Globale

✅ **Pas de shortcode à retenir** - La page affiche toujours l'interface
✅ **Mise à jour automatique** - Si vous changez le contenu de la page, l'interface reste
✅ **Centralisé** - Un seul endroit pour gérer l'affichage
✅ **Idéal pour les pages membres** - Parfait pour une zone membre dédiée

### Différence entre Shortcode Manuel et Global

| Aspect | Shortcode Manuel | Configuration Globale |
|--------|------------------|----------------------|
| Installation | Ajouter `[ai_content_studio]` dans le contenu | Sélectionner la page dans Réglages |
| Flexibilité | Peut être placé n'importe où dans le contenu | Remplace tout le contenu de la page |
| Paramètres | Peut utiliser `view=` et `height=` | Utilise les paramètres par défaut |
| Usage | Intégration partielle dans une page | Page dédiée 100% à l'interface |

### Exemple d'Utilisation

**Scénario**: Vous voulez une page "Mon Studio de Contenu" qui affiche toujours l'interface.

**Sans configuration globale**:
```
1. Créer page "Mon Studio"
2. Éditer la page
3. Ajouter [ai_content_studio]
4. Publier
```

**Avec configuration globale**:
```
1. Créer page "Mon Studio"
2. Réglages → Sélectionner "Mon Studio"
3. Sauvegarder
✅ L'interface s'affiche automatiquement!
```

## 📋 Paramètres du Shortcode

### Vue par défaut
```
[ai_content_studio view="dashboard"]
```

Options pour `view`:
- `dashboard` - Tableau de bord principal (par défaut)
- `generator` - Générateur de posts
- `blog` - Générateur d'articles
- `library` - Bibliothèque de contenu
- `calendar` - Calendrier éditorial
- `analytics` - Analytics

### Hauteur personnalisée
```
[ai_content_studio height="1000px"]
```

Définit la hauteur minimale du conteneur (défaut: 800px)

### Combinaison de paramètres
```
[ai_content_studio view="generator" height="900px"]
```

## 💡 Exemples d'Utilisation

### 1. Page "Générer du Contenu"
```
[ai_content_studio view="generator"]
```
Parfait pour une page dédiée à la création de posts.

### 2. Page "Mon Tableau de Bord"
```
[ai_content_studio view="dashboard" height="1200px"]
```
Vue complète du dashboard avec plus d'espace.

### 3. Page "Ma Bibliothèque"
```
[ai_content_studio view="library"]
```
Accès direct à tous les contenus générés.

## 🔒 Sécurité

### Utilisateurs Non-Connectés
Les utilisateurs non-connectés verront un message de connexion avec:
- Bouton "Se connecter"
- Bouton "Créer un compte" (si l'inscription est activée)

### Utilisateurs Connectés
Tous les utilisateurs connectés peuvent accéder à l'interface selon leur plan d'abonnement.

## 🎨 Personnalisation CSS

Vous pouvez personnaliser l'apparence via CSS personnalisé dans votre thème:

```css
/* Conteneur principal */
.acs-frontend-wrapper {
    background: #f5f5f5;
    padding: 20px;
    border-radius: 8px;
}

/* Message de connexion */
.acs-login-required {
    /* Vos styles */
}

.acs-notice {
    /* Vos styles */
}
```

## 📱 Responsive

Le shortcode est entièrement responsive et s'adapte automatiquement:
- Desktop: Vue complète
- Tablette: Vue optimisée
- Mobile: Vue mobile-friendly

## 🚀 Cas d'Usage Avancés

### 1. Page Membre avec Sidebar

```html
<div class="member-area">
    <aside class="sidebar">
        <!-- Votre sidebar -->
    </aside>
    <main class="content">
        [ai_content_studio]
    </main>
</div>
```

### 2. Onglets avec Différentes Vues

Utilisez un plugin d'onglets et créez:
- Onglet 1: `[ai_content_studio view="dashboard"]`
- Onglet 2: `[ai_content_studio view="generator"]`
- Onglet 3: `[ai_content_studio view="library"]`

### 3. Page de Profil Utilisateur

Intégrez le shortcode dans une page de profil membre:

```html
<h2>Mon Espace de Création</h2>
<p>Bienvenue dans votre studio de création de contenu IA!</p>

[ai_content_studio view="dashboard" height="1000px"]
```

## 🔗 Intégration avec Page Builders

### Elementor
1. Ajoutez un widget "Shortcode"
2. Collez `[ai_content_studio]`
3. Configurez les paramètres

### Gutenberg (Bloc Editor)
1. Ajoutez un bloc "Shortcode"
2. Collez le shortcode
3. Publiez

### Divi Builder
1. Ajoutez un module "Code"
2. Collez le shortcode
3. Configurez la hauteur si besoin

### Beaver Builder
1. Ajoutez un module "HTML"
2. Collez le shortcode

## ⚙️ Configuration Avancée

### Redirection après Connexion

Pour rediriger vers la page avec le shortcode après connexion:

```php
// Dans functions.php de votre thème
add_filter('login_redirect', function($redirect_to, $request, $user) {
    if (!is_wp_error($user)) {
        return home_url('/mon-studio-de-contenu/'); // URL de votre page
    }
    return $redirect_to;
}, 10, 3);
```

### Restriction par Rôle

Si vous voulez réserver le shortcode à certains rôles:

```php
// Dans functions.php
add_shortcode('ai_content_studio_vip', function($atts) {
    if (!current_user_can('subscriber')) { // ou autre capability
        return '<p>Accès réservé aux membres VIP</p>';
    }
    return do_shortcode('[ai_content_studio]');
});
```

Puis utilisez `[ai_content_studio_vip]` au lieu de `[ai_content_studio]`.

## 📊 Performance

### Chargement Optimisé
- Les scripts React ne se chargent que sur les pages avec le shortcode
- Pas d'impact sur les autres pages du site
- Chargement conditionnel automatique

### Cache
Compatible avec les plugins de cache:
- WP Rocket
- W3 Total Cache
- WP Super Cache

Vérifiez juste que les utilisateurs connectés ne sont pas cachés.

## 🐛 Dépannage

### Le shortcode ne s'affiche pas
1. Vérifiez que le plugin est activé
2. Vérifiez que le build React est fait (`npm run build`)
3. Vérifiez les permissions de fichiers

### Interface ne charge pas
1. Ouvrez la console navigateur (F12)
2. Vérifiez les erreurs JavaScript
3. Vérifiez que les fichiers JS/CSS sont bien chargés

### Utilisateur connecté voit le message de connexion
1. Videz le cache
2. Vérifiez la session WordPress
3. Testez en navigation privée

## 💡 Bonnes Pratiques

### DO ✅
- Utilisez le shortcode sur des pages dédiées
- Configurez une hauteur adaptée au contenu
- Testez sur mobile
- Combinez avec votre design de thème

### DON'T ❌
- N'utilisez pas plusieurs fois le même shortcode sur une page
- N'utilisez pas dans les widgets sidebar (trop étroit)
- N'utilisez pas dans les footers
- N'oubliez pas le build React avant déploiement

## 🎓 Exemples Complets

### Structure de Site Recommandée

```
Homepage
├── Page "Générer du Contenu" → [ai_content_studio view="generator"]
├── Page "Mes Publications" → [ai_content_studio view="library"]
├── Page "Mon Calendrier" → [ai_content_studio view="calendar"]
├── Page "Statistiques" → [ai_content_studio view="analytics"]
└── Page "Tableau de Bord" → [ai_content_studio view="dashboard"]
```

### Menu de Navigation

Créez un menu avec ces pages pour une navigation facile entre les différentes vues.

## 📞 Support

Pour des questions spécifiques sur le shortcode:
1. Consultez la documentation principale (README.md)
2. Vérifiez les logs WordPress (`wp-content/debug.log`)
3. Contactez le support

---

**Astuce Pro**: Combinez le shortcode avec des hooks WordPress pour créer une expérience membre complètement personnalisée!
