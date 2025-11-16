# Système de Traduction Multilingue - RunnWrite AI

## 📋 Vue d'ensemble

Le plugin supporte **30 langues** avec un système de traduction automatique basé sur l'IA Claude.

## 🌍 Langues Supportées

### Langues Populaires
- 🇫🇷 Français (fr) - Langue par défaut
- 🇬🇧 English (en)
- 🇪🇸 Español (es)
- 🇩🇪 Deutsch (de)
- 🇮🇹 Italiano (it)
- 🇵🇹 Português (pt)
- 🇷🇺 Русский (ru)
- 🇯🇵 日本語 (ja)
- 🇨🇳 中文 (zh)
- 🇸🇦 العربية (ar)

### Autres Langues
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
- 🇬🇷 Ελληνικά (el)
- 🇮🇱 עברית (he) - RTL
- 🇹🇭 ไทย (th)
- 🇻🇳 Tiếng Việt (vi)
- 🇮🇩 Bahasa Indonesia (id)
- 🇲🇾 Bahasa Melayu (ms)
- 🇷🇴 Română (ro)
- 🇺🇦 Українська (uk)
- 🇭🇺 Magyar (hu)
- 🇧🇬 Български (bg)

---

## 🚀 Générer les Traductions

### Méthode 1 : Via l'Interface Admin (Recommandé)

1. Allez dans **WordPress Admin** → **AI Content Studio** → **Réglages** → **Traductions**
2. Vous verrez la liste de toutes les langues avec leur statut
3. Options disponibles :
   - **Générer une langue** : Cliquez sur "Générer" à côté d'une langue spécifique
   - **Générer toutes les langues** : Cliquez sur le bouton "Générer Toutes les Traductions"

### Méthode 2 : Via Code PHP

```php
use ACS\Services\Translation_Service;

// Générer une langue spécifique
Translation_Service::generate_translations('es'); // Espagnol

// Générer toutes les langues
$languages = ['en', 'es', 'de', 'it', 'pt', /* ... */];
foreach ($languages as $lang) {
    Translation_Service::generate_translations($lang);
}
```

---

## ⚙️ Comment ça Fonctionne ?

### 1. Architecture

```
📁 languages/
├── translations-en.json     # Traductions anglaises
├── translations-es.json     # Traductions espagnoles
├── translations-de.json     # Traductions allemandes
└── ...
```

### 2. Processus de Traduction

1. **Source** : Toutes les chaînes sont définies en français dans `Translation_Service::get_translatable_strings()`
2. **IA Claude** : Utilise l'API Claude (modèle Haiku) pour traduire automatiquement
3. **Sauvegarde** : Les traductions sont sauvegardées dans des fichiers JSON
4. **Cache** : Les fichiers JSON sont réutilisés pour éviter les appels API répétés

### 3. Utilisation dans le Code

#### Backend PHP
```php
use ACS\Services\Translation_Service;

// Traduire une chaîne
$translated = Translation_Service::__('Bienvenue', 'en');
// Résultat : "Welcome"
```

#### Frontend React
```javascript
import { __ } from '@wordpress/i18n';

// Utilise la langue de l'utilisateur connecté
const text = __('Bienvenue', 'ai-content-studio');
```

---

## 💾 Format des Fichiers de Traduction

### Exemple : `languages/translations-en.json`

```json
{
  "Bienvenue": "Welcome",
  "Tableau de bord": "Dashboard",
  "Générer des posts": "Generate posts",
  "Choisissez votre plan": "Choose your plan",
  ...
}
```

---

## 🔧 Configuration Utilisateur

### Lors de l'Inscription

1. L'utilisateur choisit sa langue préférée
2. La langue est sauvegardée dans `user_meta` : `acs_user_language`
3. L'interface React charge automatiquement cette langue

### Modifier la Langue Utilisateur

```php
// Changer la langue d'un utilisateur
update_user_meta($user_id, 'acs_user_language', 'es');
```

---

## 💰 Coût Estimé

### API Claude (Haiku)

- **Modèle** : claude-3-haiku-20240307
- **Coût** : ~$0.25 pour 1M tokens input, ~$1.25 pour 1M tokens output
- **Estimation** :
  - ~100 chaînes par langue
  - ~50 tokens par chaîne
  - **Total par langue** : ~5,000 tokens = **$0.01**
  - **Total 30 langues** : **~$0.30**

### Optimisations

1. **Fichiers cachés** : Les traductions ne sont générées qu'une seule fois
2. **Batch processing** : Possibilité de traiter plusieurs chaînes en une seule requête
3. **Modèle Haiku** : Le plus économique de la famille Claude

---

## 🎯 Ajouter une Nouvelle Chaîne

### 1. Ajouter dans le Service

```php
// includes/services/class-translation-service.php
public static function get_translatable_strings() {
    return [
        // Existing strings...
        'Ma nouvelle chaîne' => 'My new string',
    ];
}
```

### 2. Régénérer les Traductions

- Allez dans **Admin** → **Traductions**
- Cliquez sur "Régénérer" pour chaque langue concernée
- Ou régénérez toutes les langues

---

## 🌐 Support RTL (Right-to-Left)

### Langues RTL Supportées

- Arabe (ar)
- Hébreu (he)

### Auto-détection

```php
$lang_config = Language_Config::get('ar');
if ($lang_config['direction'] === 'rtl') {
    // Appliquer les styles RTL
}
```

---

## 📊 Statut des Traductions

### Vérifier l'Avancement

```php
$languages = Language_Config::get_all();

foreach ($languages as $code => $lang) {
    $file = ACS_PLUGIN_DIR . "languages/translations-{$code}.json";
    $status = file_exists($file) ? 'Traduit' : 'Non traduit';
    echo "{$lang['native_name']}: {$status}\n";
}
```

---

## 🐛 Troubleshooting

### Les traductions ne se chargent pas

1. Vérifiez que le fichier existe : `/languages/translations-{code}.json`
2. Vérifiez la clé API Claude dans **Réglages**
3. Consultez les logs : **AI Content Studio** → **Logs Système**

### Mauvaise qualité de traduction

1. Modifiez la chaîne dans `get_translatable_strings()`
2. Régénérez uniquement cette langue
3. Ou éditez manuellement le fichier JSON

### Langue non supportée

1. Ajoutez la langue dans `Language_Config::get_all()`
2. Générez les traductions via l'admin

---

## 📝 TODO / Améliorations Futures

- [ ] Interface de modification manuelle des traductions
- [ ] Export/Import des fichiers de traduction
- [ ] Traduction collaborative (contribution communautaire)
- [ ] Détection automatique de la langue du navigateur
- [ ] Fallback intelligent (si langue non dispo → anglais)
- [ ] Pluralisation et contextes

---

## 📚 Ressources

- **WordPress i18n** : https://developer.wordpress.org/plugins/internationalization/
- **Claude API** : https://docs.anthropic.com/claude/reference/
- **ISO 639-1** : https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes

---

**Date de création** : Novembre 2025
**Version** : 1.0.0
