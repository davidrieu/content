<?php
/**
 * Script temporaire pour créer la table blog_strategies
 * À exécuter via WP-CLI : wp eval-file create-blog-strategies-table.php
 */

global $wpdb;

$table_name = $wpdb->prefix . 'acs_blog_strategies';
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    ideas LONGTEXT NOT NULL COMMENT 'JSON: Array de 50 sujets d articles',
    language VARCHAR(10) DEFAULT 'fr' COMMENT 'Langue de la stratégie',
    goal VARCHAR(50) COMMENT 'Objectif principal: engagement, brand_awareness, etc',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_id (user_id)
) $charset_collate ENGINE=InnoDB;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

echo "Table {$table_name} créée avec succès!\n";

// Vérifier que la table existe
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
if ($table_exists === $table_name) {
    echo "✓ Vérification : La table existe bien dans la base de données.\n";
} else {
    echo "✗ Erreur : La table n'a pas été créée.\n";
}
