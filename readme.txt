=== AI Content Studio ===
Contributors: aicontentstudio
Tags: ai, content, social media, blog, claude, dall-e, content generation
Requires at least: 6.4
Tested up to: 6.4
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plateforme SaaS complète pour générer du contenu réseaux sociaux et articles de blog avec IA.

== Description ==

**AI Content Studio** est une plateforme SaaS complète qui permet de générer du contenu de qualité professionnelle pour les réseaux sociaux et les blogs en utilisant l'intelligence artificielle.

= Fonctionnalités principales =

* **Génération de posts réseaux sociaux** - Créez du contenu optimisé pour Instagram, Facebook, LinkedIn, Twitter, TikTok et YouTube
* **Générateur d'articles de blog** - Produisez des articles SEO-optimisés avec analyse de score
* **Générateur d'images IA** - Créez des visuels uniques avec DALL-E 3
* **Détection de tendances** - Identifiez les sujets populaires dans votre niche
* **Calendrier éditorial** - Planifiez et organisez votre contenu
* **Templates prêts à l'emploi** - Plus de 200 modèles pour tous types de contenu
* **Brand Kit** - Définissez votre identité de marque (couleurs, ton, logo)
* **Analytics** - Suivez vos performances et votre usage
* **Publication automatique** - Publiez directement sur vos réseaux sociaux
* **Système d'abonnement** - Monétisez votre service avec WooCommerce

= Technologies utilisées =

* **Claude API** (Anthropic) - Génération de texte de haute qualité
* **DALL-E 3** (OpenAI) - Génération d'images
* **WooCommerce Subscriptions** - Gestion des abonnements
* **React 18.2+** - Interface moderne et réactive
* **@wordpress/scripts** - Build système optimisé

= Plans d'abonnement =

**Gratuit**
* 5 posts/mois
* 1 article/mois
* 2 images/mois
* 50 templates

**Starter - 19€/mois**
* 50 posts/mois
* 5 articles/mois
* 20 images/mois
* Publication automatique
* Brand Kit basique

**Pro - 49€/mois**
* 200 posts/mois
* 20 articles/mois
* 100 images/mois
* Analyse concurrentielle
* Analytics avancés

**Business - 99€/mois**
* Posts illimités
* Articles illimités
* Images illimitées
* Équipe (3 membres)
* Support dédié

= Configuration requise =

* WordPress 6.4+
* PHP 8.1+
* WooCommerce 8.0+
* WooCommerce Subscriptions 5.0+ (recommandé)
* MySQL 8.0+

= APIs requises =

Pour utiliser ce plugin, vous devez configurer les clés API suivantes :

* **Claude API** (Anthropic) - Obligatoire pour la génération de texte
* **DALL-E API** (OpenAI) - Obligatoire pour la génération d'images
* **Facebook/Instagram API** - Optionnel pour la publication automatique
* **LinkedIn API** - Optionnel pour la publication automatique
* **Twitter API** - Optionnel pour la publication automatique

== Installation ==

1. Téléchargez et installez le plugin
2. Activez le plugin dans WordPress
3. Assurez-vous que WooCommerce et WooCommerce Subscriptions sont installés et activés
4. Allez dans **AI Content Studio > Réglages** pour configurer vos clés API
5. Les produits d'abonnement seront créés automatiquement lors de l'activation
6. Commencez à utiliser le plugin !

= Configuration des clés API =

1. Allez dans **AI Content Studio > Réglages**
2. Entrez votre **Claude API Key** (obligatoire)
3. Entrez votre **DALL-E API Key** (obligatoire)
4. Configurez les API OAuth pour les réseaux sociaux (optionnel)
5. Sauvegardez les paramètres

= Obtenir les clés API =

* **Claude API** : https://console.anthropic.com/
* **OpenAI DALL-E** : https://platform.openai.com/
* **Facebook/Instagram** : https://developers.facebook.com/
* **LinkedIn** : https://www.linkedin.com/developers/
* **Twitter** : https://developer.twitter.com/

== Frequently Asked Questions ==

= Ai-je besoin d'un compte Claude et OpenAI ? =

Oui, vous devez avoir des clés API pour Claude (Anthropic) et DALL-E (OpenAI). Ces services sont payants selon votre utilisation.

= Le plugin fonctionne-t-il sans WooCommerce ? =

Non, WooCommerce est requis pour le système d'abonnement. WooCommerce Subscriptions est fortement recommandé pour une gestion complète des abonnements récurrents.

= Puis-je publier automatiquement sur les réseaux sociaux ? =

Oui, avec les plans payants (Starter, Pro, Business), vous pouvez connecter vos comptes sociaux et publier automatiquement.

= Les données sont-elles supprimées lors de la désinstallation ? =

Par défaut, oui. Vous pouvez activer l'option "Préserver les données" dans les réglages pour conserver vos données même après désinstallation.

= Puis-je utiliser le plugin en plusieurs langues ? =

Oui ! Le plugin supporte plus de 15 langues pour la génération de contenu (français, anglais, espagnol, allemand, italien, etc.).

= Quel est le coût des API ? =

Les coûts dépendent de votre utilisation :
* Claude API : ~$3 pour 1M tokens d'entrée, ~$15 pour 1M tokens de sortie
* DALL-E 3 : $0.040-$0.120 par image selon la qualité

= Y a-t-il un mode débutant ? =

Oui ! Le plugin propose deux modes :
* **Mode Simple** : Interface simplifiée pour débutants
* **Mode Expert** : Toutes les options avancées disponibles

== Screenshots ==

1. Dashboard principal avec vue d'ensemble
2. Générateur de posts réseaux sociaux
3. Générateur d'articles de blog avec SEO
4. Calendrier éditorial
5. Bibliothèque de templates
6. Brand Kit personnalisé
7. Analytics et statistiques

== Changelog ==

= 1.0.0 - 2024-11-08 =
* Version initiale
* Génération de posts réseaux sociaux avec Claude
* Génération d'articles de blog SEO-optimisés
* Générateur d'images avec DALL-E 3
* Détection de tendances
* Calendrier éditorial
* 200+ templates
* Brand Kit
* Analytics
* Publication automatique (Facebook, Instagram, LinkedIn, Twitter)
* Intégration WooCommerce Subscriptions
* 4 plans d'abonnement (Free, Starter, Pro, Business)
* Interface React moderne
* Mode Simple/Expert

== Upgrade Notice ==

= 1.0.0 =
Version initiale du plugin.

== Support ==

Pour obtenir de l'aide :

* Documentation : https://aicontentstudio.com/docs
* Support : https://aicontentstudio.com/support
* Email : support@aicontentstudio.com

== Privacy & GDPR ==

Ce plugin :
* Stocke les données utilisateur dans la base de données WordPress
* Communique avec des API tierces (Claude, OpenAI, réseaux sociaux)
* Ne vend aucune donnée à des tiers
* Permet l'export et la suppression des données utilisateur

== Credits ==

* Claude API par Anthropic
* DALL-E par OpenAI
* React par Meta
* @wordpress/scripts par WordPress
