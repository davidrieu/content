# 🚀 Guide d'utilisation - Assistant de Migration des Traductions

## ✨ Nouveau : Mise à jour automatique en 1 clic !

Plus besoin de FTP ! L'assistant de migration intégré permet de mettre à jour les traductions directement depuis WordPress.

---

## 📍 Accéder à l'assistant

### Méthode 1 : Notification automatique

Lorsque vos traductions sont obsolètes, une notification apparaît automatiquement dans toutes les pages AI Content Studio :

```
⚠️ AI Content Studio - Mise à jour disponible
Une nouvelle version des traductions est disponible (1.2.0). 
Vos traductions actuelles sont obsolètes.

[Mettre à jour les traductions]
```

Cliquez sur le bouton **"Mettre à jour les traductions"**.

### Méthode 2 : Via le menu Traductions

1. Allez dans **AI Content Studio → Traductions**
2. Cliquez sur **"Assistant de Migration"** en haut à droite du titre
3. Vous serez redirigé vers la page de migration

### Méthode 3 : URL directe

```
https://votre-site.com/wp-admin/admin.php?page=acs-translation-migration
```

---

## 📊 Page de migration - Comprendre l'interface

### Section 1 : État des traductions

Un tableau comparatif affiche :

| Fichier | Statut | Traductions | Taille | Dernière modification |
|---------|--------|-------------|--------|----------------------|
| **Source (plugin)** | ✓ Disponible | **838** clés | 41 KB | 2025-11-17 |
| **Actuel (utilisé)** | ⚠ Obsolète | **459** clés | 23 KB | 2025-10-15 |

**Indicateurs de statut :**
- 🟢 **À jour** : Vos traductions sont à la dernière version
- 🟠 **Obsolète** : Une mise à jour est disponible
- 🔴 **Manquant** : Le fichier n'existe pas

### Section 2 : Mise à jour nécessaire

Si vos traductions sont obsolètes, vous verrez :

```
⚠️ Mise à jour nécessaire

Votre fichier de traductions contient 459 traductions mais 
la version actuelle en contient 838.

Il manque 379 traductions nécessaires pour le bon fonctionnement 
de l'interface (formulaires de login, authentification, etc.).
```

---

## 🔧 Effectuer la mise à jour

### Étape 1 : Cliquer sur le bouton

Cliquez sur le gros bouton bleu :

```
🔄 Mettre à jour les traductions maintenant
```

### Étape 2 : Confirmation automatique

Le système :
1. ✅ Crée une sauvegarde de votre fichier actuel
2. ✅ Copie le nouveau fichier source (838 traductions)
3. ✅ Enregistre la nouvelle version (1.2.0)
4. ✅ Vide le cache WordPress

### Étape 3 : Vérification

Après la mise à jour, vous verrez :

```
✓ Traductions à jour

Vos traductions sont à la dernière version. Aucune action nécessaire.

838 traductions installées.
```

---

## 🎯 Après la mise à jour

### Étape suivante : Régénérer toutes les langues

Maintenant que le fichier anglais (source) est à jour avec 838 traductions, vous devez régénérer les traductions pour les autres langues :

1. **Retournez sur la page Traductions** :
   ```
   AI Content Studio → Traductions
   ```

2. **Régénérez chaque langue** :
   - Cliquez sur "Régénérer" pour chaque langue que vous utilisez
   - Le système traduira automatiquement les 838 clés en utilisant Claude AI
   - Temps estimé : ~5-6 minutes par langue (17 chunks × 50 traductions)

3. **Langues prioritaires à régénérer** :
   - Anglais (en) - Déjà fait par la migration
   - Espagnol (es)
   - Portugais (pt)
   - Allemand (de)
   - Italien (it)
   - Toutes les langues que vos utilisateurs utilisent

### Vider les caches (important!)

Après la migration ET la régénération :

1. **Cache WordPress** :
   - WP Rocket : Dashboard → Clear cache
   - W3 Total Cache : Performance → Purge All Caches
   - WP Super Cache : Settings → Delete Cache

2. **Cache navigateur** :
   - Windows/Linux : **Ctrl + Shift + R**
   - Mac : **Cmd + Shift + R**
   - Ou ouvrir en navigation privée

3. **Cache CDN** (si Cloudflare) :
   - Dashboard Cloudflare → Caching → Purge Everything

---

## 🔍 Vérifier que ça fonctionne

### Test 1 : Vérifier l'endpoint API

Ouvrez cette URL dans votre navigateur :
```
https://votre-site.com/wp-json/acs/v1/translations/en
```

**Attendu** :
- Un JSON avec 838 entrées
- Présence de ces clés :
  ```json
  {
    "Nom d'utilisateur": "Username",
    "Langue de l'interface": "Interface Language",
    "Connectez-vous pour accéder à Runnwrite AI": "Log in to access Runnwrite AI"
  }
  ```

### Test 2 : Console du navigateur

Déconnectez-vous de WordPress et ouvrez la console (F12) sur la page de login.

**Attendu** :
```
Loading translations for language: en
Loaded 838 translation keys for en  ← Pas 459 !
```

### Test 3 : Interface visuelle

La page de login doit afficher :
- ✅ "Log in to access Runnwrite AI"
- ✅ "Username or Email"
- ✅ "Create an account"

La page de création de compte doit afficher :
- ✅ "Start creating content with AI"
- ✅ "Username"
- ✅ "Interface Language"
- ✅ "I already have an account"

---

## 🛡️ Sécurité et sauvegarde

### Sauvegarde automatique

Avant chaque mise à jour, le système crée automatiquement une sauvegarde :

```
Fichier original : translations-en.json
Sauvegarde créée : translations-en.json.backup-20251117034512
```

Les sauvegardes sont conservées dans :
```
wp-content/uploads/acs-translations/
```

### En cas de problème

Si quelque chose ne va pas après la migration :

1. **Restaurer la sauvegarde manuellement** :
   - Via FTP/SFTP, renommez le fichier `.backup-XXXXXXXX` en `.json`

2. **Contacter le support** avec :
   - Version de WordPress
   - Message d'erreur (si applicable)
   - Nombre de traductions avant/après la migration

---

## ❓ FAQ

### Pourquoi mes traductions étaient obsolètes ?

Vous aviez une ancienne version du fichier (459 traductions) qui date d'avant les ajouts récents :
- Traductions d'authentification (login/register)
- Nouvelles fonctionnalités (Image Generator, Calendar, etc.)
- Branding Runnwrite AI

### La mise à jour va-t-elle effacer mes traductions personnalisées ?

Oui, si vous avez modifié manuellement le fichier `translations-en.json`. 
C'est pourquoi une sauvegarde automatique est créée.

### Dois-je refaire la migration à chaque mise à jour du plugin ?

Non ! Le système détecte automatiquement quand une nouvelle version est disponible et affiche une notification.

### Combien de temps prend la migration ?

< 1 seconde. La migration copie simplement le fichier.
Le temps de régénération des 30 langues prend environ 2.5-3 heures.

### Puis-je migrer seulement certaines langues ?

La migration met à jour le fichier source anglais (838 clés).
Vous choisissez ensuite quelles langues régénérer (pas obligé de tout faire).

---

## 📈 Historique des versions

### Version 1.2.0 (Actuelle)
- **838 traductions** (+379 par rapport à 1.1.0)
- Authentification multilingue complète
- Branding Runnwrite AI
- Couverture 100% des composants React

### Version 1.1.0 (Ancienne)
- **459 traductions**
- Traductions de base
- Fonctionnalités limitées

---

## ✅ Checklist complète

Après avoir installé le plugin avec la migration tool :

- [ ] Aller sur la page de migration
- [ ] Vérifier le tableau de comparaison
- [ ] Cliquer sur "Mettre à jour maintenant"
- [ ] Attendre la confirmation de succès
- [ ] Aller sur Traductions
- [ ] Régénérer les langues nécessaires
- [ ] Vider tous les caches
- [ ] Tester la page de login (doit être en anglais)
- [ ] Tester la création de compte (sélection de langue)
- [ ] Vérifier l'endpoint API (838 traductions)

---

## 🎉 Résultat final

**Avant migration** :
- ❌ Login/Register en français
- ❌ 459 traductions (incomplet)
- ❌ Nécessitait FTP pour mise à jour

**Après migration** :
- ✅ Login/Register en anglais
- ✅ 838 traductions (complet)
- ✅ Mise à jour en 1 clic depuis WordPress
- ✅ 30 langues disponibles
- ✅ Wizard multilingue
- ✅ Branding Runnwrite AI

---

**Support** : Pour toute question, consultez la documentation ou contactez le support.

**Version du guide** : 1.0.0 - 17 novembre 2025
