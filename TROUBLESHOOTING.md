# Guide de Dépannage - AI Content Studio

## 🐛 Problèmes Résolus (Version 1.0.0)

### 1. ✅ Avertissement d'Incompatibilité WooCommerce

**Symptôme**: "WooCommerce a détecté que certaines de vos extensions actives sont incompatibles"

**Solution Appliquée**:
- Ajout des déclarations de compatibilité HPOS dans `ai-content-studio.php`
- Déclaration de compatibilité avec `custom_order_tables` (High-Performance Order Storage)
- Déclaration de compatibilité avec `cart_checkout_blocks`

**Vérification**:
```bash
# Le plugin déclare maintenant explicitement sa compatibilité WooCommerce
# Redémarrez WordPress et vérifiez que l'avertissement a disparu
```

---

### 2. ✅ Page de Réglages Blanche

**Symptôme**: Page blanche lors de l'accès à "AI Content Studio → Réglages"

**Cause**: La page de réglages faisait référence à un composant React non-créé

**Solution Appliquée**:
- Création de `includes/admin/class-settings.php` avec interface HTML native WordPress
- Utilisation de l'API Settings WordPress standard
- Affichage des statuts WooCommerce, API Keys, et informations du plugin

**Vérification**:
1. Allez dans "AI Content Studio → Réglages"
2. Vous devriez voir:
   - Section "Clés API" (Claude, DALL-E)
   - Section "Paramètres Généraux"
   - Tableau d'informations du plugin
   - Exemples d'utilisation du shortcode

---

### 3. ✅ Shortcode N'Affiche Rien

**Symptôme**: Le shortcode `[ai_content_studio]` ne montre rien sur la page

**Cause**: Les fichiers React n'étaient pas compilés (build manquant)

**Solution Appliquée**:
1. Installation des dépendances npm
2. Compilation de l'application React avec `@wordpress/scripts`
3. Copie des fichiers buildés vers les bons emplacements:
   - `admin/js/admin-script.js` (27KB)
   - `admin/js/admin-script.asset.php`
   - `admin/css/admin-style.css`
4. Mise à jour de `.gitignore` pour inclure les builds en production

**Vérification**:
```bash
# Vérifiez que ces fichiers existent:
ls -la admin/js/admin-script.js
ls -la admin/css/admin-style.css

# Ils doivent exister et avoir une taille > 0
```

**Si le problème persiste après déploiement**:
```bash
cd /path/to/plugin/admin/react-app
npm install
npm run build

# Puis copiez les fichiers:
cp build/index.js ../js/admin-script.js
cp build/index.asset.php ../js/admin-script.asset.php
cp build/index.css ../css/admin-style.css
```

---

## 🔍 Diagnostics Rapides

### Vérifier l'Installation Complète

```bash
# Depuis la racine du plugin
ls -la includes/class-plugin.php           # Doit exister
ls -la includes/admin/class-settings.php   # Doit exister
ls -la admin/js/admin-script.js            # Doit exister (27KB)
ls -la admin/css/admin-style.css           # Doit exister (1.2KB)
```

### Vérifier les Tables SQL

```sql
-- Dans phpMyAdmin ou MySQL CLI
SHOW TABLES LIKE 'wp_acs_%';

-- Devrait montrer 14 tables:
-- wp_acs_business_profiles
-- wp_acs_strategies
-- wp_acs_usage_stats
-- wp_acs_social_posts
-- wp_acs_blog_articles
-- wp_acs_generated_images
-- wp_acs_trends
-- wp_acs_templates
-- wp_acs_brand_kits
-- wp_acs_social_connections
-- wp_acs_calendar_events
-- wp_acs_analytics
-- wp_acs_notifications
-- wp_acs_favorites
```

### Vérifier les API Keys

1. Allez dans "AI Content Studio → Réglages"
2. Vérifiez que les clés API sont configurées:
   - ✅ Claude API configurée
   - ✅ DALL-E API configurée

### Tester le Shortcode

1. Créez une nouvelle page WordPress
2. Ajoutez le shortcode:
   ```
   [ai_content_studio]
   ```
3. **IMPORTANT**: Connectez-vous d'abord!
4. Visitez la page
5. Vous devriez voir:
   - Si NON connecté: Message de connexion avec boutons
   - Si connecté: Interface React complète

---

## 🚨 Problèmes Courants et Solutions

### Le Shortcode Affiche Seulement un Message de Connexion

**C'est normal si vous n'êtes pas connecté!**

Le shortcode affiche automatiquement:
- Pour utilisateurs NON connectés: Message avec bouton "Se connecter"
- Pour utilisateurs connectés: Interface complète React

**Solution**: Connectez-vous à WordPress avant de visiter la page.

---

### Erreur JavaScript dans la Console

**Vérification**:
1. Ouvrez la console navigateur (F12)
2. Cherchez des erreurs rouges
3. Vérifiez que ces fichiers se chargent sans erreur 404:
   - `/wp-content/plugins/ai-content-studio/admin/js/admin-script.js`
   - `/wp-content/plugins/ai-content-studio/admin/css/admin-style.css`

**Si erreur 404**:
```bash
# Re-build l'application React
cd admin/react-app
npm run build
cp build/index.js ../js/admin-script.js
cp build/index.asset.php ../js/admin-script.asset.php
cp build/index.css ../css/admin-style.css
```

---

### WooCommerce Non Installé

**Symptôme**: Avertissement dans l'admin WordPress

**Solution**:
1. Installez WooCommerce (8.0+)
2. Installez WooCommerce Subscriptions (5.0+) [optionnel mais recommandé]
3. Configurez Stripe pour les paiements
4. Les produits d'abonnement seront créés automatiquement

**Note**: Le plugin peut fonctionner sans WooCommerce en mode dégradé (pas de système d'abonnement automatique).

---

### Cron Jobs Ne S'Exécutent Pas

**Vérification**:
```bash
wp cron event list

# Devrait montrer:
# - acs_reset_monthly_usage
# - acs_fetch_trends
# - acs_cleanup_trends
# - acs_refresh_tokens
# - acs_publish_scheduled_posts
```

**Solution**:
```bash
# Activez WP-Cron si désactivé
# Dans wp-config.php, vérifiez:
define('DISABLE_WP_CRON', false);

# Ou configurez un vrai cron serveur:
*/15 * * * * wget -q -O - https://votresite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

---

### Limites d'Utilisation Non Respectées

**Symptôme**: Les utilisateurs peuvent générer plus de contenu que leur limite

**Vérification**:
```php
// Test dans MySQL
SELECT * FROM wp_acs_usage_stats WHERE user_id = 1;

// Vérifiez que posts_this_month est incrémenté après génération
```

**Solution**:
```bash
# Forcer un reset manuel si nécessaire
wp eval "ACS\Services\Usage_Service::reset_all_monthly_usage();"
```

---

### Erreurs API Claude ou DALL-E

**Symptôme**: Génération échoue avec erreur API

**Vérifications**:
1. Clés API valides dans Réglages
2. Crédit disponible sur les comptes Anthropic/OpenAI
3. Limites de taux respectées

**Logs**:
```bash
# Activez WP_DEBUG
# Dans wp-config.php:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

# Puis consultez:
tail -f wp-content/debug.log

# Filtrez les logs ACS:
grep '\[ACS\]' wp-content/debug.log
```

---

## 📊 Checklist de Déploiement Production

- [ ] Plugin activé dans WordPress
- [ ] WooCommerce installé et actif
- [ ] WooCommerce Subscriptions installé (recommandé)
- [ ] API Keys configurées (Claude, DALL-E)
- [ ] Build React compilé et fichiers présents
- [ ] Tables SQL créées (14 tables)
- [ ] Cron jobs actifs
- [ ] Page de réglages accessible (pas de blanc)
- [ ] Shortcode fonctionne sur une page de test
- [ ] Test utilisateur connecté vs non-connecté
- [ ] Test génération de post (avec limites)
- [ ] Test onboarding pour nouveau compte
- [ ] Stripe configuré en mode production
- [ ] Produits d'abonnement créés
- [ ] SSL actif (HTTPS)

---

## 🎯 Tests de Fonctionnement

### Test 1: Nouveau Utilisateur

1. Créez un nouveau compte WordPress
2. Vérifiez dans la BDD:
   ```sql
   SELECT * FROM wp_usermeta WHERE user_id = X AND meta_key = 'acs_subscription_plan';
   -- Devrait être 'free'
   ```
3. Visitez la page avec le shortcode
4. Vous devriez voir l'onboarding wizard
5. Complétez l'onboarding
6. Vérifiez qu'un profil est créé:
   ```sql
   SELECT * FROM wp_acs_business_profiles WHERE user_id = X;
   ```

### Test 2: Génération de Post

1. Connectez-vous
2. Accédez au générateur (via shortcode ou admin)
3. Générez un post
4. Vérifiez:
   - 3 variantes générées
   - Usage incrémenté:
     ```sql
     SELECT posts_this_month FROM wp_acs_usage_stats WHERE user_id = X;
     ```
   - Post sauvegardé:
     ```sql
     SELECT * FROM wp_acs_social_posts WHERE user_id = X;
     ```

### Test 3: Limites

1. Avec un compte Free (5 posts/mois)
2. Générez 5 posts
3. Tentez d'en générer un 6ème
4. Devrait afficher: "Limite atteinte pour votre plan"

### Test 4: Upgrade

1. Achetez un plan Starter (en mode test Stripe)
2. Vérifiez que le plan est mis à jour:
   ```sql
   SELECT meta_value FROM wp_usermeta
   WHERE user_id = X AND meta_key = 'acs_subscription_plan';
   -- Devrait être 'starter'
   ```
3. Vérifiez que les limites augmentent (50 posts/mois)
4. Générez plus de 5 posts (devrait fonctionner)

---

## 🔧 Commandes Utiles

### Réactiver le Plugin
```bash
wp plugin deactivate ai-content-studio
wp plugin activate ai-content-studio
```

### Forcer Création des Tables
```bash
wp eval "ACS\Database::create_tables();"
```

### Reset Usage Stats
```bash
wp eval "ACS\Services\Usage_Service::reset_all_monthly_usage();"
```

### Tester API Claude
```bash
wp eval "
\$service = new ACS\Services\Claude_Service();
var_dump(\$service);
"
```

### Lister les Cron Jobs
```bash
wp cron event list
```

### Forcer Exécution Cron
```bash
wp cron event run acs_reset_monthly_usage
```

---

## 📞 Support

### Fichiers de Log
- **WordPress**: `wp-content/debug.log`
- **PHP**: Vérifiez `php_error.log` ou via `error_log()` de PHP
- **Serveur**: `/var/log/apache2/error.log` ou `/var/log/nginx/error.log`

### Informations à Fournir pour Support
1. Version WordPress
2. Version PHP
3. Version WooCommerce
4. Logs d'erreur
5. Console navigateur (F12)
6. Étapes pour reproduire le problème

---

## 🎉 Tout Fonctionne?

Si tous les tests passent, votre installation est complète!

**Prochaines étapes**:
1. Créez une page "Mon Studio" avec le shortcode
2. Configurez votre menu de navigation
3. Testez l'expérience utilisateur complète
4. Configurez vos emails WooCommerce
5. Lancez en production! 🚀

---

**Dernière mise à jour**: 2024-11-09
**Version du plugin**: 1.0.0
