# 🔨 Instructions de Build - React App

## ⚠️ IMPORTANT - À FAIRE APRÈS AVOIR PULL LES CHANGEMENTS

Les composants React ont été corrigés mais doivent être **recompilés** pour fonctionner.

---

## 📦 Étapes de Build

### 1. Naviguer vers le dossier React
```bash
cd admin/react-app/
```

### 2. Installer les dépendances (première fois seulement)
```bash
npm install
```

**Durée** : ~2-3 minutes
**Taille** : ~250 MB (node_modules)

### 3. Compiler l'application React
```bash
npm run build
```

**Durée** : ~30-60 secondes
**Sortie** : `admin/js/admin-script.js` (bundle compilé)

---

## ✅ Vérification du Build

Après le build, vérifiez :

```bash
ls -lh ../js/admin-script.js
# Devrait afficher un fichier de ~500KB - 1MB
```

---

## 🔄 Quand Recompiler ?

Vous devez recompiler (`npm run build`) après **chaque modification** de :

- ✅ Fichiers dans `src/components/`
- ✅ Fichiers dans `src/contexts/`
- ✅ `src/App.js`
- ❌ Fichiers PHP backend (pas de build nécessaire)
- ❌ Fichiers CSS (chargés directement)

---

## 🚨 Dépannage

### Erreur : `wp-scripts: not found`
```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Erreur : `Module not found`
```bash
npm install
npm run build
```

### Build très lent (>5 minutes)
- Vérifiez votre connexion Internet
- Fermez les applications gourmandes en RAM
- Essayez `npm run build -- --no-cache`

---

## 📝 Changements qui Nécessitent ce Build

Les corrections suivantes ont été apportées aux composants React :

1. **LanguageSwitcher.js** : Passage de `__()` à `t()`
2. **BlogArticleGenerator.js** : Passage de `__()` à `t()`
3. **BlogLibrary.js** : Passage de `__()` à `t()`

Sans le build, ces composants utiliseront l'**ancienne version** (avant corrections).

---

## 🎯 Après le Build

1. ✅ Upload du fichier `admin/js/admin-script.js` sur votre serveur
2. ✅ Videz le cache WordPress (si activé)
3. ✅ Videz le cache navigateur (Ctrl+Shift+R)
4. ✅ Testez le changement de langue dans l'interface

---

**Temps total estimé** : 3-5 minutes (première fois), 1 minute (builds suivants)
