# 🌍 Système de Traduction Multilingue - AI Content Studio

Ce plugin supporte maintenant **30 langues** avec un système de traduction complet basé sur react-i18next.

## 📋 Table des Matières

- [Langues Supportées](#langues-supportées)
- [Comment Fonctionne le Système](#comment-fonctionne-le-système)
- [Utilisation pour les Utilisateurs](#utilisation-pour-les-utilisateurs)
- [Ajouter des Traductions (Développeurs)](#ajouter-des-traductions-développeurs)
- [Générer les Traductions avec Claude AI](#générer-les-traductions-avec-claude-ai)
- [Structure des Fichiers](#structure-des-fichiers)

## 🌐 Langues Supportées

### Langues Populaires (11)
- 🇫🇷 Français (fr) - *Langue de base*
- 🇬🇧 English (en)
- 🇪🇸 Español (es)
- 🇩🇪 Deutsch (de)
- 🇮🇹 Italiano (it)
- 🇵🇹 Português (pt)
- 🇷🇺 Русский (ru)
- 🇯🇵 日本語 (ja)
- 🇨🇳 中文 (zh)
- 🇸🇦 العربية (ar) - *Support RTL*

### Autres Langues (19)
- 🇳🇱 Nederlands (nl)
- 🇵🇱 Polski (pl)
- 🇰🇷 한국어 (ko)
- 🇹🇷 Türkçe (tr)
- 🇮🇳 हिन्दी (hi)
- 🇸🇪 Svenska (sv)
- 🇩🇰 Dansk (da)
- 🇳🇴 Norsk (no)
- 🇫🇮 Suomi (fi)
- 🇨🇿 Čeština (cs)
- 🇷🇴 Română (ro)
- 🇬🇷 Ελληνικά (el)
- 🇹🇭 ไทย (th)
- 🇻🇳 Tiếng Việt (vi)
- 🇮🇩 Bahasa Indonesia (id)
- 🇲🇾 Bahasa Melayu (ms)
- 🇮🇱 עברית (he) - *Support RTL*
- 🇺🇦 Українська (uk)
- 🇧🇩 বাংলা (bn)
- 🇮🇷 فارسی (fa) - *Support RTL*

## 🔧 Comment Fonctionne le Système

### Détection Automatique de la Langue

Le système détecte automatiquement la langue de l'utilisateur dans cet ordre :

1. **Langue stockée dans WordPress** (`window.acsData.userLanguage`)
2. **Langue sauvegardée dans localStorage** (`acs-language`)
3. **Langue du navigateur** (automatique)
4. **Langue par défaut** : Français (fr)

### Support RTL

Les langues RTL (Right-to-Left) sont automatiquement gérées :
- Arabe (ar)
- Hébreu (he)
- Persan (fa)

L'attribut `dir="rtl"` est ajouté automatiquement à `<html>` quand ces langues sont sélectionnées.

## 👤 Utilisation pour les Utilisateurs

### Changer de Langue

1. Cliquez sur le bouton avec le drapeau dans la barre supérieure (topbar)
2. Utilisez la recherche pour filtrer les langues
3. Cliquez sur la langue souhaitée
4. L'interface se met à jour immédiatement
5. Votre choix est sauvegardé automatiquement

### Lors de l'Inscription

Les utilisateurs peuvent choisir leur langue préférée lors de l'inscription. Cette langue sera utilisée par défaut pour toute l'interface.

## 👨‍💻 Ajouter des Traductions (Développeurs)

### 1. Utiliser les Traductions dans un Composant

```javascript
import { useTranslation } from 'react-i18next';

export default function MyComponent() {
    const { t } = useTranslation();

    return (
        <div>
            <h1>{t('common.loading')}</h1>
            <button>{t('common.save')}</button>
        </div>
    );
}
```

### 2. Ajouter une Nouvelle Clé de Traduction

Éditez `/admin/react-app/src/locales/fr.json` :

```json
{
  "mySection": {
    "myNewKey": "Mon nouveau texte en français"
  }
}
```

Puis utilisez-la :

```javascript
{t('mySection.myNewKey')}
```

### 3. Structure Recommandée

Organisez vos traductions par section logique :

```json
{
  "common": { /* Textes communs */ },
  "auth": { /* Authentification */ },
  "dashboard": { /* Tableau de bord */ },
  "sidebar": { /* Menu latéral */ },
  "settings": { /* Paramètres */ }
}
```

## 🤖 Générer les Traductions avec Claude AI

### Méthode Automatique (Recommandée)

Utilisez le script de traduction automatique avec Claude API :

```bash
# Définir votre clé API
export ANTHROPIC_API_KEY=your_key_here

# Traduire toutes les langues (29 langues)
cd admin/react-app
node translate-with-claude.js

# Traduire seulement certaines langues
node translate-with-claude.js en es de it

# Traduire une seule langue
node translate-with-claude.js en
```

**Avantages** :
- Traductions contextuelles et naturelles
- Respect des expressions natives
- Gestion automatique de la structure JSON
- Cohérence entre toutes les langues

**Note** : Ce processus prend environ 30-60 secondes par langue.

### Méthode Manuelle

Si vous préférez traduire manuellement :

1. Copiez `fr.json` vers la langue cible (ex: `en.json`)
2. Traduisez toutes les valeurs (pas les clés !)
3. Vérifiez que le JSON est valide
4. Testez dans l'interface

## 📁 Structure des Fichiers

```
admin/react-app/
├── src/
│   ├── i18n.js                          # Configuration i18next
│   ├── locales/                         # Fichiers de traduction
│   │   ├── fr.json                      # Français (BASE)
│   │   ├── en.json                      # Anglais
│   │   ├── es.json                      # Espagnol
│   │   └── ... (27 autres langues)
│   └── components/
│       └── common/
│           └── LanguageSwitcher.js      # Composant de sélection
├── generate-translations.js             # Script de génération simple
├── translate-with-claude.js             # Script de traduction AI
└── TRANSLATION_README.md                # Ce fichier

includes/
└── data/
    └── class-language-config.php        # Configuration PHP des langues
```

## 🎯 Bonnes Pratiques

### Pour les Développeurs

1. **Toujours utiliser `t()` pour les textes**
   ```javascript
   ❌ <h1>Dashboard</h1>
   ✅ <h1>{t('dashboard.title')}</h1>
   ```

2. **Utiliser des clés descriptives**
   ```javascript
   ❌ t('text1')
   ✅ t('dashboard.welcome')
   ```

3. **Organiser par contexte**
   ```javascript
   ❌ t('save'), t('save2'), t('saveButton')
   ✅ t('common.save'), t('settings.save'), t('modal.save')
   ```

4. **Ne jamais traduire les clés, seulement les valeurs**
   ```json
   ❌ { "titre": "Title" }
   ✅ { "title": "Titre" }
   ```

### Pour les Traducteurs

1. **Respecter le contexte**
   - "Save" (bouton) → "Enregistrer"
   - "Save" (titre) → "Sauvegarder"

2. **Conserver les formats**
   - `{count} posts` → `{count} posts` (garder `{count}`)
   - `/mois` → `/mês` (garder le `/`)

3. **Préserver les emojis et symboles**
   - `✨ Success!` → `✨ Succès !`

4. **Adapter au public cible**
   - Utiliser le ton approprié (formel/informel)
   - Respecter les conventions locales

## 🔄 Workflow de Développement

### Ajouter un Nouveau Texte

1. Ajoutez la clé dans `fr.json`
2. Utilisez `t('newKey')` dans votre composant
3. Testez en français
4. Exécutez le script de traduction pour les autres langues
5. Committez tous les fichiers JSON modifiés

### Modifier un Texte Existant

1. Modifiez la valeur dans `fr.json`
2. Exécutez le script de traduction pour synchroniser
3. Vérifiez les changements dans les autres langues
4. Committez

## 📊 Statistiques

- **Langues totales** : 30
- **Clés de traduction** : ~600+
- **Fichiers JSON** : 30 (1 par langue)
- **Taille du bundle** : +800 KB (toutes les traductions)

## 🆘 Dépannage

### La langue ne change pas

1. Vérifiez que le fichier JSON existe dans `/locales/`
2. Vérifiez la console pour les erreurs
3. Videz le localStorage : `localStorage.removeItem('acs-language')`
4. Rechargez la page avec Ctrl+F5

### Textes non traduits

1. Vérifiez que la clé existe dans le fichier JSON
2. Vérifiez que vous utilisez `t()` et non `__()`
3. Vérifiez l'import : `import { useTranslation } from 'react-i18next'`

### Erreur de compilation

1. Vérifiez que tous les fichiers JSON sont valides
2. Utilisez un validateur JSON en ligne
3. Vérifiez les virgules et guillemets

## 📝 Notes Importantes

- **Production** : Pour la production, exécutez le script `translate-with-claude.js` pour obtenir de vraies traductions au lieu des placeholders
- **Cache** : Les traductions sont chargées au démarrage de l'app
- **Performance** : Toutes les traductions sont chargées en même temps (pas de lazy loading pour l'instant)
- **Mise à jour** : Pour ajouter une langue, ajoutez-la dans `class-language-config.php` ET créez le fichier JSON

## 🚀 Prochaines Étapes

1. **Traduire avec Claude AI** : Exécutez `translate-with-claude.js` pour générer les vraies traductions
2. **Lazy Loading** : Implémenter le chargement à la demande pour réduire le bundle
3. **Contribution** : Améliorer les traductions existantes
4. **Tests** : Ajouter des tests pour vérifier la complétude des traductions

---

**Créé avec** ❤️ **par Claude AI**
**Version** : 1.0.0
**Date** : Novembre 2025
