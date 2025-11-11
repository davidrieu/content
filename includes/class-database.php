<?php
/**
 * Database schema and management
 *
 * @package ACS
 */

namespace ACS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database class
 */
class Database {

    /**
     * Create all database tables
     *
     * @return void
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_prefix = $wpdb->prefix . ACS_TABLE_PREFIX;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // 1. Business Profiles
        $sql_business_profiles = "CREATE TABLE {$table_prefix}business_profiles (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            business_type VARCHAR(100) NOT NULL COMMENT 'Type d activité (legacy)',
            business_name VARCHAR(255) NOT NULL,
            description TEXT COMMENT 'Description courte de l activité',
            niche VARCHAR(255) COMMENT 'Niche spécifique',
            location VARCHAR(255) COMMENT 'Localisation',
            target_audience TEXT COMMENT 'Public cible (texte libre)',
            goals LONGTEXT COMMENT 'JSON: [awareness, sales, engagement]',
            platforms LONGTEXT COMMENT 'JSON: [instagram, facebook, linkedin, tiktok, twitter, youtube]',
            languages LONGTEXT COMMENT 'JSON: [fr, en, es, de, it]',
            has_blog BOOLEAN DEFAULT 0 COMMENT 'Indique si l utilisateur a un blog',
            user_type VARCHAR(50) COMMENT 'Type utilisateur: business, creator, freelance, agency',
            sector VARCHAR(100) COMMENT 'Secteur activité: ecommerce, services, tech, food, health, music, etc',
            website VARCHAR(255) COMMENT 'URL du site web',
            social_platforms LONGTEXT COMMENT 'JSON: Plateformes sociales sélectionnées',
            posting_frequency VARCHAR(50) COMMENT 'Fréquence publication: daily, frequent, weekly, occasional',
            seo_goals LONGTEXT COMMENT 'JSON: Objectifs SEO [organic_traffic, ranking, long_tail, authority]',
            blog_topics LONGTEXT COMMENT 'JSON: Sujets blog préférés',
            primary_keywords LONGTEXT COMMENT 'JSON: Mots-clés principaux (max 5)',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY idx_business_type (business_type),
            KEY idx_user_type (user_type),
            KEY idx_sector (sector)
        ) $charset_collate ENGINE=InnoDB;";

        // 2. Strategies
        $sql_strategies = "CREATE TABLE {$table_prefix}strategies (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            content_strategy LONGTEXT COMMENT 'JSON: Strategy complète générée par Claude',
            posting_schedule LONGTEXT COMMENT 'JSON: {platform: {frequency, bestTimes}}',
            content_pillars LONGTEXT COMMENT 'JSON: [pilier1, pilier2, pilier3]',
            tone_style TEXT COMMENT 'Ton et style de communication',
            hashtags LONGTEXT COMMENT 'JSON: Hashtags recommandés par plateforme',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate ENGINE=InnoDB;";

        // 3. Usage Stats
        $sql_usage_stats = "CREATE TABLE {$table_prefix}usage_stats (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            posts_generated INT DEFAULT 0 COMMENT 'Total posts générés',
            posts_this_month INT DEFAULT 0 COMMENT 'Posts ce mois',
            articles_generated INT DEFAULT 0 COMMENT 'Total articles générés',
            articles_this_month INT DEFAULT 0 COMMENT 'Articles ce mois',
            images_generated INT DEFAULT 0 COMMENT 'Total images générées',
            images_this_month INT DEFAULT 0 COMMENT 'Images ce mois',
            videos_generated INT DEFAULT 0,
            videos_this_month INT DEFAULT 0,
            last_reset_date DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Dernière réinitialisation mensuelle',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate ENGINE=InnoDB;";

        // 4. Social Posts
        $sql_social_posts = "CREATE TABLE {$table_prefix}social_posts (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            platform VARCHAR(50) NOT NULL COMMENT 'instagram, facebook, linkedin, tiktok, twitter, youtube',
            content LONGTEXT NOT NULL,
            hashtags LONGTEXT COMMENT 'JSON: [#tag1, #tag2]',
            language VARCHAR(10) DEFAULT 'fr',
            status VARCHAR(20) DEFAULT 'draft' COMMENT 'draft, scheduled, published, failed',
            scheduled_for DATETIME COMMENT 'Date de publication programmée',
            published_at DATETIME COMMENT 'Date de publication effective',
            published_url VARCHAR(500) COMMENT 'URL du post publié',
            trend_id BIGINT(20) UNSIGNED COMMENT 'Tendance utilisée',
            template_id BIGINT(20) UNSIGNED COMMENT 'Template utilisé',
            image_id BIGINT(20) UNSIGNED COMMENT 'Image associée',
            metadata LONGTEXT COMMENT 'JSON: {variants, tone, original_prompt, etc}',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_status (user_id, status),
            KEY idx_scheduled (scheduled_for),
            KEY idx_platform (platform)
        ) $charset_collate ENGINE=InnoDB;";

        // 5. Blog Articles
        $sql_blog_articles = "CREATE TABLE {$table_prefix}blog_articles (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            subject VARCHAR(255) NOT NULL COMMENT 'Sujet original de l article',
            title VARCHAR(255) NOT NULL COMMENT 'Titre de l article',
            content LONGTEXT NOT NULL COMMENT 'Contenu de l article',
            keywords LONGTEXT COMMENT 'JSON: [keyword1, keyword2, keyword3, keyword4, keyword5]',
            main_keyword VARCHAR(100) COMMENT 'Mot-clé principal',
            first_person TINYINT(1) DEFAULT 0 COMMENT 'Écrit à la première personne',
            word_count INT COMMENT 'Nombre de mots',
            seo_title VARCHAR(255) COMMENT 'Titre SEO optimisé',
            meta_description VARCHAR(160) COMMENT 'Meta description SEO',
            url_slug VARCHAR(255) COMMENT 'Slug URL optimisé',
            language VARCHAR(10) DEFAULT 'fr',
            status VARCHAR(20) DEFAULT 'draft' COMMENT 'draft, scheduled, published',
            scheduled_for DATETIME,
            published_post_id BIGINT(20) UNSIGNED COMMENT 'ID du post WordPress créé',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_status (user_id, status),
            KEY idx_main_keyword (main_keyword),
            FULLTEXT idx_content (title, content)
        ) $charset_collate ENGINE=InnoDB;";

        // 6. Generated Images
        $sql_generated_images = "CREATE TABLE {$table_prefix}generated_images (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            prompt TEXT NOT NULL COMMENT 'Prompt utilisé pour générer l image',
            dalle_prompt TEXT COMMENT 'Prompt optimisé envoyé à DALL-E',
            image_url VARCHAR(500) NOT NULL COMMENT 'URL de l image générée',
            thumbnail_url VARCHAR(500),
            width INT DEFAULT 1024,
            height INT DEFAULT 1024,
            format VARCHAR(20) DEFAULT 'square' COMMENT 'square, portrait, landscape',
            style VARCHAR(50) COMMENT 'realistic, artistic, cartoon, etc',
            post_id BIGINT(20) UNSIGNED COMMENT 'Post associé si applicable',
            article_id BIGINT(20) UNSIGNED COMMENT 'Article associé si applicable',
            status VARCHAR(20) DEFAULT 'generated' COMMENT 'generated, edited, deleted',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user (user_id),
            KEY idx_status (status)
        ) $charset_collate ENGINE=InnoDB;";

        // 7. Trends
        $sql_trends = "CREATE TABLE {$table_prefix}trends (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            keyword VARCHAR(255) NOT NULL,
            platform VARCHAR(50) NOT NULL COMMENT 'google, instagram, tiktok, twitter, linkedin',
            category VARCHAR(100) COMMENT 'Catégorie business associée',
            language VARCHAR(10) DEFAULT 'fr',
            volume INT COMMENT 'Volume de recherche/mentions',
            growth_rate DECIMAL(5,2) COMMENT 'Taux de croissance en %',
            relevance_score DECIMAL(3,2) COMMENT 'Score 0-1 de pertinence',
            detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL COMMENT 'Date d expiration de la tendance',
            PRIMARY KEY (id),
            KEY idx_platform_category (platform, category),
            KEY idx_relevance (relevance_score),
            KEY idx_expires (expires_at),
            KEY idx_keyword (keyword)
        ) $charset_collate ENGINE=InnoDB;";

        // 8. Templates
        $sql_templates = "CREATE TABLE {$table_prefix}templates (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(100) NOT NULL COMMENT 'social_post, blog_article, email',
            subcategory VARCHAR(100) COMMENT 'announcement, promotion, education, etc',
            template_content LONGTEXT COMMENT 'Contenu du template avec variables {{name}}',
            prompt_template TEXT COMMENT 'Template de prompt pour Claude',
            platform VARCHAR(50) COMMENT 'Plateforme cible si social post',
            language VARCHAR(10) DEFAULT 'fr',
            is_premium BOOLEAN DEFAULT 0 COMMENT 'Réservé aux plans payants',
            is_default BOOLEAN DEFAULT 1 COMMENT 'Template fourni par défaut',
            usage_count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_category (category, subcategory),
            KEY idx_platform (platform),
            KEY idx_popularity (usage_count DESC)
        ) $charset_collate ENGINE=InnoDB;";

        // 9. Brand Kits
        $sql_brand_kits = "CREATE TABLE {$table_prefix}brand_kits (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            brand_name VARCHAR(255),
            logo_url VARCHAR(500),
            logo_attachment_id BIGINT(20) UNSIGNED COMMENT 'ID media WordPress',
            primary_color VARCHAR(7) COMMENT 'HEX color',
            secondary_color VARCHAR(7),
            accent_color VARCHAR(7),
            font_primary VARCHAR(100),
            font_secondary VARCHAR(100),
            tone_of_voice TEXT COMMENT 'Description du ton de communication',
            brand_values LONGTEXT COMMENT 'JSON: [valeur1, valeur2]',
            banned_words LONGTEXT COMMENT 'JSON: Mots à éviter',
            preferred_hashtags LONGTEXT COMMENT 'JSON: Hashtags de marque',
            signature VARCHAR(255) COMMENT 'Signature à ajouter aux posts',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate ENGINE=InnoDB;";

        // 10. Social Connections
        $sql_social_connections = "CREATE TABLE {$table_prefix}social_connections (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            platform VARCHAR(50) NOT NULL COMMENT 'facebook, instagram, linkedin, twitter',
            account_id VARCHAR(255) NOT NULL COMMENT 'ID du compte sur la plateforme',
            account_name VARCHAR(255) COMMENT 'Nom du compte',
            account_username VARCHAR(255) COMMENT 'Username/handle',
            account_type VARCHAR(50) COMMENT 'page, profile, business, etc',
            access_token TEXT NOT NULL,
            refresh_token TEXT,
            token_type VARCHAR(50) DEFAULT 'Bearer',
            token_expires_at DATETIME,
            scopes TEXT COMMENT 'Permissions accordées',
            is_active BOOLEAN DEFAULT 1,
            last_used_at DATETIME,
            connected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_platform_account (user_id, platform, account_id),
            KEY idx_user_platform (user_id, platform),
            KEY idx_expires (token_expires_at)
        ) $charset_collate ENGINE=InnoDB;";

        // 11. Calendar Events
        $sql_calendar_events = "CREATE TABLE {$table_prefix}calendar_events (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_type VARCHAR(50) NOT NULL COMMENT 'post, article, holiday, custom',
            event_date DATE NOT NULL,
            post_id BIGINT(20) UNSIGNED COMMENT 'Si lié à un post',
            article_id BIGINT(20) UNSIGNED COMMENT 'Si lié à un article',
            is_recurring BOOLEAN DEFAULT 0,
            recurrence_rule VARCHAR(255) COMMENT 'RRULE format',
            color VARCHAR(7) COMMENT 'Couleur dans le calendrier',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_date (user_id, event_date),
            KEY idx_type (event_type)
        ) $charset_collate ENGINE=InnoDB;";

        // 12. Analytics
        $sql_analytics = "CREATE TABLE {$table_prefix}analytics (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            metric_type VARCHAR(50) NOT NULL COMMENT 'post_generated, article_published, image_created, etc',
            metric_value INT DEFAULT 1,
            platform VARCHAR(50),
            language VARCHAR(10),
            content_type VARCHAR(50),
            metadata LONGTEXT COMMENT 'JSON: Données additionnelles',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_type (user_id, metric_type),
            KEY idx_created (created_at)
        ) $charset_collate ENGINE=InnoDB;";

        // 13. Notifications
        $sql_notifications = "CREATE TABLE {$table_prefix}notifications (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL COMMENT 'limit_reached, trend_detected, post_published, etc',
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            action_url VARCHAR(500) COMMENT 'URL de l action',
            action_text VARCHAR(100) COMMENT 'Texte du bouton',
            is_read BOOLEAN DEFAULT 0,
            read_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_unread (user_id, is_read),
            KEY idx_created (created_at DESC)
        ) $charset_collate ENGINE=InnoDB;";

        // 14. Favorites
        $sql_favorites = "CREATE TABLE {$table_prefix}favorites (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            item_type VARCHAR(50) NOT NULL COMMENT 'template, post, article, trend',
            item_id BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_item (user_id, item_type, item_id),
            KEY idx_user_type (user_id, item_type)
        ) $charset_collate ENGINE=InnoDB;";

        // 15. Content Plans (Stratégies de contenu automatiques)
        $sql_content_plans = "CREATE TABLE {$table_prefix}content_plans (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL COMMENT 'Nom du plan (ex: Plan Mars 2025)',
            month INT NOT NULL COMMENT 'Mois (1-12)',
            year INT NOT NULL COMMENT 'Année',
            strategy LONGTEXT COMMENT 'JSON: Stratégie complète avec thèmes, mix contenu, etc',
            weekly_themes LONGTEXT COMMENT 'JSON: [{week: 1, theme: \"Lancement\", description: \"...\"}]',
            posting_frequency VARCHAR(50) COMMENT 'daily, frequent, weekly, occasional',
            content_mix LONGTEXT COMMENT 'JSON: {educational: 40, promotional: 20, engagement: 30, storytelling: 10}',
            platforms LONGTEXT COMMENT 'JSON: [instagram, facebook, linkedin]',
            status VARCHAR(20) DEFAULT 'active' COMMENT 'active, archived, draft',
            posts_generated INT DEFAULT 0 COMMENT 'Nombre de posts générés depuis ce plan',
            is_generated BOOLEAN DEFAULT 1 COMMENT 'Plan généré automatiquement',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_period (user_id, year, month),
            KEY idx_status (status)
        ) $charset_collate ENGINE=InnoDB;";

        // 16. Saved Templates (Templates personnalisés utilisateur)
        $sql_saved_templates = "CREATE TABLE {$table_prefix}saved_templates (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NOT NULL COMMENT 'post, caption, cta, hook',
            content LONGTEXT NOT NULL,
            platform VARCHAR(50) COMMENT 'Plateforme cible',
            hashtags LONGTEXT COMMENT 'JSON: Hashtags sauvegardés',
            usage_count INT DEFAULT 0,
            is_favorite BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_category (user_id, category),
            KEY idx_favorite (user_id, is_favorite)
        ) $charset_collate ENGINE=InnoDB;";

        // 17. System Logs (Table dédiée pour les logs système)
        $sql_system_logs = "CREATE TABLE {$table_prefix}system_logs (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED DEFAULT 0 COMMENT 'ID utilisateur (0 pour logs système)',
            level VARCHAR(20) NOT NULL COMMENT 'debug, info, warning, error, critical',
            category VARCHAR(50) NOT NULL COMMENT 'api, generation, auth, database, etc',
            message TEXT NOT NULL,
            context LONGTEXT COMMENT 'JSON: Contexte additionnel (params, stack trace, etc)',
            file VARCHAR(255) COMMENT 'Fichier source du log',
            line INT COMMENT 'Ligne dans le fichier source',
            ip_address VARCHAR(45) COMMENT 'Adresse IP de l utilisateur',
            user_agent TEXT COMMENT 'User agent du navigateur',
            request_uri VARCHAR(500) COMMENT 'URI de la requête',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_level (level),
            KEY idx_category (category),
            KEY idx_user (user_id),
            KEY idx_created (created_at DESC),
            KEY idx_level_created (level, created_at DESC)
        ) $charset_collate ENGINE=InnoDB;";

        // Execute all table creations
        dbDelta($sql_business_profiles);
        dbDelta($sql_strategies);
        dbDelta($sql_usage_stats);
        dbDelta($sql_social_posts);
        dbDelta($sql_blog_articles);
        dbDelta($sql_generated_images);
        dbDelta($sql_trends);
        dbDelta($sql_templates);
        dbDelta($sql_brand_kits);
        dbDelta($sql_social_connections);
        dbDelta($sql_calendar_events);
        dbDelta($sql_analytics);
        dbDelta($sql_notifications);
        dbDelta($sql_favorites);
        dbDelta($sql_content_plans);
        dbDelta($sql_saved_templates);
        dbDelta($sql_system_logs);

        // Store database version
        update_option('acs_db_version', ACS_VERSION);
    }

    /**
     * Upgrade existing tables with new columns
     *
     * @return void
     */
    public static function upgrade_tables() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . ACS_TABLE_PREFIX;
        $table = $table_prefix . 'business_profiles';

        // Check if new columns exist, if not add them
        $columns = $wpdb->get_col("DESCRIBE {$table}", 0);

        // Add user_type column
        if (!in_array('user_type', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN user_type VARCHAR(50) COMMENT 'Type utilisateur: business, creator, freelance, agency' AFTER has_blog");
        }

        // Add sector column
        if (!in_array('sector', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN sector VARCHAR(100) COMMENT 'Secteur activité: ecommerce, services, tech, food, health, music, etc' AFTER user_type");
        }

        // Add website column
        if (!in_array('website', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN website VARCHAR(255) COMMENT 'URL du site web' AFTER sector");
        }

        // Add social_platforms column
        if (!in_array('social_platforms', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN social_platforms LONGTEXT COMMENT 'JSON: Plateformes sociales sélectionnées' AFTER website");
        }

        // Add posting_frequency column
        if (!in_array('posting_frequency', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN posting_frequency VARCHAR(50) COMMENT 'Fréquence publication: daily, frequent, weekly, occasional' AFTER social_platforms");
        }

        // Add seo_goals column
        if (!in_array('seo_goals', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN seo_goals LONGTEXT COMMENT 'JSON: Objectifs SEO [organic_traffic, ranking, long_tail, authority]' AFTER posting_frequency");
        }

        // Add blog_topics column
        if (!in_array('blog_topics', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN blog_topics LONGTEXT COMMENT 'JSON: Sujets blog préférés' AFTER seo_goals");
        }

        // Add primary_keywords column
        if (!in_array('primary_keywords', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN primary_keywords LONGTEXT COMMENT 'JSON: Mots-clés principaux (max 5)' AFTER blog_topics");
        }

        // Modify target_audience to TEXT instead of LONGTEXT if needed
        $column_info = $wpdb->get_row("SHOW COLUMNS FROM {$table} LIKE 'target_audience'");
        if ($column_info && strpos(strtolower($column_info->Type), 'longtext') !== false) {
            $wpdb->query("ALTER TABLE {$table} MODIFY COLUMN target_audience TEXT COMMENT 'Public cible (texte libre)'");
        }

        // Add indexes for new columns
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$table}", ARRAY_A);
        $index_names = array_column($indexes, 'Key_name');

        if (!in_array('idx_user_type', $index_names)) {
            $wpdb->query("ALTER TABLE {$table} ADD KEY idx_user_type (user_type)");
        }

        if (!in_array('idx_sector', $index_names)) {
            $wpdb->query("ALTER TABLE {$table} ADD KEY idx_sector (sector)");
        }

        // Upgrade blog_articles table - Add subject column if missing
        $blog_articles_table = $table_prefix . 'blog_articles';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$blog_articles_table}'") === $blog_articles_table) {
            $blog_columns = $wpdb->get_col("DESCRIBE {$blog_articles_table}", 0);

            if (!in_array('subject', $blog_columns)) {
                error_log('ACS: Adding missing subject column to blog_articles table');
                $wpdb->query("ALTER TABLE {$blog_articles_table} ADD COLUMN subject VARCHAR(255) NOT NULL COMMENT 'Sujet original de l article' AFTER user_id");
                error_log('ACS: Subject column added successfully');
            }
        }

        // Create system_logs table if it doesn't exist (added in v1.2.0)
        self::create_system_logs_table();
    }

    /**
     * Create system logs table if it doesn't exist
     *
     * @return void
     */
    public static function create_system_logs_table() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . ACS_TABLE_PREFIX;
        $table = $table_prefix . 'system_logs';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
            return; // Table already exists
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED DEFAULT 0 COMMENT 'ID utilisateur (0 pour logs système)',
            level VARCHAR(20) NOT NULL COMMENT 'debug, info, warning, error, critical',
            category VARCHAR(50) NOT NULL COMMENT 'api, generation, auth, database, etc',
            message TEXT NOT NULL,
            context LONGTEXT COMMENT 'JSON: Contexte additionnel (params, stack trace, etc)',
            file VARCHAR(255) COMMENT 'Fichier source du log',
            line INT COMMENT 'Ligne dans le fichier source',
            ip_address VARCHAR(45) COMMENT 'Adresse IP de l utilisateur',
            user_agent TEXT COMMENT 'User agent du navigateur',
            request_uri VARCHAR(500) COMMENT 'URI de la requête',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_level (level),
            KEY idx_category (category),
            KEY idx_user (user_id),
            KEY idx_created (created_at DESC),
            KEY idx_level_created (level, created_at DESC)
        ) $charset_collate ENGINE=InnoDB;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Check if tables exist
     *
     * @return bool
     */
    public static function tables_exist() {
        global $wpdb;
        $table_prefix = $wpdb->prefix . ACS_TABLE_PREFIX;
        $table = $table_prefix . 'business_profiles';

        return $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
    }

    /**
     * Get table name with prefix
     *
     * @param string $table
     * @return string
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . ACS_TABLE_PREFIX . $table;
    }
}
