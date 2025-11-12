<?php
/**
 * Subscriptions Admin Page
 *
 * @package ACS\Admin
 */

namespace ACS\Admin;

use ACS\WooCommerce\Product_Generator;
use ACS\Config\Plans_Config;
use ACS\Utils\Plan_Migration;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Subscriptions_Admin class
 */
class Subscriptions_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_subscriptions_page']);
        add_action('admin_post_acs_generate_products', [$this, 'handle_generate_products']);
        add_action('admin_post_acs_delete_products', [$this, 'handle_delete_products']);
        add_action('admin_post_acs_migrate_plans', [$this, 'handle_migrate_plans']);
    }

    /**
     * Add subscriptions admin page
     */
    public function add_subscriptions_page() {
        add_submenu_page(
            'ai-content-studio',
            __('Abonnements', 'ai-content-studio'),
            __('Abonnements', 'ai-content-studio'),
            'manage_options',
            'ai-content-studio-subscriptions',
            [$this, 'render_subscriptions_page']
        );
    }

    /**
     * Render subscriptions admin page
     */
    public function render_subscriptions_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'ai-content-studio'));
        }

        // Check if WooCommerce is active
        $wc_active = class_exists('WooCommerce');
        $wc_subs_active = class_exists('WC_Subscriptions');

        // Get current product IDs
        $product_ids = Product_Generator::get_all_product_ids();
        $products_exist = Product_Generator::products_exist();

        // Get all plans
        $plans = Plans_Config::get_plans();
        $paid_plans = Plans_Config::get_paid_plans();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php if (isset($_GET['message'])): ?>
                <?php if ($_GET['message'] === 'products_generated'): ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php _e('✅ Les produits d\'abonnement ont été générés avec succès !', 'ai-content-studio'); ?></p>
                    </div>
                <?php elseif ($_GET['message'] === 'products_deleted'): ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php _e('✅ Les produits d\'abonnement ont été supprimés.', 'ai-content-studio'); ?></p>
                    </div>
                <?php elseif (strpos($_GET['message'], 'plans_migrated') === 0): ?>
                    <div class="notice notice-success is-dismissible">
                        <p>
                            <?php
                            $free = isset($_GET['free']) ? intval($_GET['free']) : 0;
                            $pro = isset($_GET['pro']) ? intval($_GET['pro']) : 0;
                            echo sprintf(
                                __('✅ Migration réussie ! %d utilisateur(s) "free" → "free_trial", %d utilisateur(s) "pro" → "professional"', 'ai-content-studio'),
                                $free,
                                $pro
                            );
                            ?>
                        </p>
                    </div>
                <?php elseif ($_GET['message'] === 'migration_error'): ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php _e('❌ Erreur lors de la migration des plans.', 'ai-content-studio'); ?></p>
                    </div>
                <?php elseif ($_GET['message'] === 'error'): ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php _e('❌ Une erreur s\'est produite.', 'ai-content-studio'); ?></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!$wc_active || !$wc_subs_active): ?>
                <div class="notice notice-error">
                    <p>
                        <strong><?php _e('Attention:', 'ai-content-studio'); ?></strong>
                        <?php if (!$wc_active): ?>
                            <?php _e('WooCommerce n\'est pas installé. Veuillez installer et activer WooCommerce.', 'ai-content-studio'); ?>
                        <?php elseif (!$wc_subs_active): ?>
                            <?php _e('WooCommerce Subscriptions n\'est pas installé. Veuillez installer et activer WooCommerce Subscriptions.', 'ai-content-studio'); ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>

                <!-- Plans Configuration -->
                <div class="card" style="max-width: 100%; margin-top: 20px;">
                    <h2><?php _e('Configuration des Plans', 'ai-content-studio'); ?></h2>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Plan', 'ai-content-studio'); ?></th>
                                <th><?php _e('Prix', 'ai-content-studio'); ?></th>
                                <th><?php _e('Posts/mois', 'ai-content-studio'); ?></th>
                                <th><?php _e('Articles/mois', 'ai-content-studio'); ?></th>
                                <th><?php _e('Images/mois', 'ai-content-studio'); ?></th>
                                <th><?php _e('Produit WC', 'ai-content-studio'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plans as $plan_slug => $plan_data): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($plan_data['name']); ?></strong>
                                        <?php if (isset($plan_data['is_trial']) && $plan_data['is_trial']): ?>
                                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;" title="Plan d'essai"></span>
                                        <?php endif; ?>
                                        <?php if (isset($plan_data['popular']) && $plan_data['popular']): ?>
                                            <span style="background: #2271b1; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-left: 5px;">POPULAIRE</span>
                                        <?php endif; ?>
                                        <br>
                                        <small style="color: #666;"><?php echo esc_html($plan_data['description']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($plan_data['price'] == 0): ?>
                                            <strong style="color: #46b450;">GRATUIT</strong>
                                        <?php else: ?>
                                            <strong style="color: #2271b1; font-size: 16px;">$<?php echo esc_html($plan_data['price']); ?></strong>
                                            <small>/mois</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $posts = $plan_data['limits']['posts_per_month'];
                                        echo $posts === -1 ? '<strong>∞ Illimité</strong>' : esc_html($posts);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $articles = $plan_data['limits']['articles_per_month'];
                                        echo $articles === -1 ? '<strong>∞ Illimité</strong>' : esc_html($articles);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $images = $plan_data['limits']['images_per_month'];
                                        echo $images === -1 ? '<strong>∞ Illimité</strong>' : esc_html($images);
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($plan_data['price'] > 0): ?>
                                            <?php
                                            $product_id = $plan_data['wc_product_id'];
                                            if ($product_id && wc_get_product($product_id)):
                                            ?>
                                                <a href="<?php echo admin_url('post.php?post=' . $product_id . '&action=edit'); ?>" target="_blank">
                                                    ✅ #<?php echo $product_id; ?>
                                                </a>
                                            <?php else: ?>
                                                <span style="color: #dc3232;">❌ Non créé</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #666;">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- WooCommerce Products Generation -->
                <div class="card" style="max-width: 100%; margin-top: 20px;">
                    <h2><?php _e('Génération des Produits WooCommerce', 'ai-content-studio'); ?></h2>

                    <p><?php _e('Cliquez sur le bouton ci-dessous pour créer ou mettre à jour automatiquement les produits d\'abonnement WooCommerce.', 'ai-content-studio'); ?></p>

                    <p><strong><?php _e('Plans à créer:', 'ai-content-studio'); ?></strong></p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <?php foreach ($paid_plans as $plan_slug => $plan_data): ?>
                            <li>
                                <strong><?php echo esc_html($plan_data['name']); ?></strong> -
                                $<?php echo esc_html($plan_data['price']); ?>/mois
                                <?php
                                $product_id = $plan_data['wc_product_id'];
                                if ($product_id && wc_get_product($product_id)):
                                ?>
                                    <span style="color: #46b450;">(déjà créé)</span>
                                <?php else: ?>
                                    <span style="color: #dc3232;">(à créer)</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top: 20px;">
                        <?php wp_nonce_field('acs_generate_products', 'acs_nonce'); ?>
                        <input type="hidden" name="action" value="acs_generate_products">

                        <button type="submit" class="button button-primary button-hero" style="margin-right: 10px;">
                            <span class="dashicons dashicons-products" style="margin-top: 7px;"></span>
                            <?php if ($products_exist): ?>
                                <?php _e('Mettre à jour les produits d\'abonnement', 'ai-content-studio'); ?>
                            <?php else: ?>
                                <?php _e('Créer les produits d\'abonnement', 'ai-content-studio'); ?>
                            <?php endif; ?>
                        </button>

                        <?php if ($products_exist): ?>
                            <button type="button" class="button button-secondary" onclick="if(confirm('Êtes-vous sûr de vouloir supprimer tous les produits d\'abonnement ?')) { document.getElementById('delete-form').submit(); }">
                                <span class="dashicons dashicons-trash" style="margin-top: 7px;"></span>
                                <?php _e('Supprimer tous les produits', 'ai-content-studio'); ?>
                            </button>
                        <?php endif; ?>
                    </form>

                    <?php if ($products_exist): ?>
                        <form id="delete-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: none;">
                            <?php wp_nonce_field('acs_delete_products', 'acs_nonce'); ?>
                            <input type="hidden" name="action" value="acs_delete_products">
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Current Products Status -->
                <?php if ($products_exist): ?>
                    <div class="card" style="max-width: 100%; margin-top: 20px;">
                        <h2><?php _e('Produits WooCommerce Existants', 'ai-content-studio'); ?></h2>

                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Plan', 'ai-content-studio'); ?></th>
                                    <th><?php _e('Produit ID', 'ai-content-studio'); ?></th>
                                    <th><?php _e('Nom du Produit', 'ai-content-studio'); ?></th>
                                    <th><?php _e('Prix', 'ai-content-studio'); ?></th>
                                    <th><?php _e('Statut', 'ai-content-studio'); ?></th>
                                    <th><?php _e('Actions', 'ai-content-studio'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($product_ids as $plan_slug => $product_id): ?>
                                    <?php $product = wc_get_product($product_id); ?>
                                    <?php if ($product): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($plan_slug); ?></strong></td>
                                            <td>#<?php echo esc_html($product_id); ?></td>
                                            <td><?php echo esc_html($product->get_name()); ?></td>
                                            <td>$<?php echo esc_html($product->get_price()); ?>/mois</td>
                                            <td>
                                                <?php if ($product->get_status() === 'publish'): ?>
                                                    <span style="color: #46b450;">✅ Publié</span>
                                                <?php else: ?>
                                                    <span style="color: #dc3232;">❌ <?php echo esc_html($product->get_status()); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo admin_url('post.php?post=' . $product_id . '&action=edit'); ?>" class="button button-small" target="_blank">
                                                    <?php _e('Modifier', 'ai-content-studio'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Instructions -->
                <div class="card" style="max-width: 100%; margin-top: 20px;">
                    <h2><?php _e('Instructions', 'ai-content-studio'); ?></h2>

                    <ol style="line-height: 2;">
                        <li><?php _e('Cliquez sur "Créer les produits d\'abonnement" pour générer automatiquement les 3 produits WooCommerce.', 'ai-content-studio'); ?></li>
                        <li><?php _e('Les produits seront créés comme des abonnements mensuels récurrents.', 'ai-content-studio'); ?></li>
                        <li><?php _e('Les produits sont cachés de la boutique (catalog_visibility: hidden).', 'ai-content-studio'); ?></li>
                        <li><?php _e('Les utilisateurs pourront s\'abonner via votre interface personnalisée avec la popup de pricing.', 'ai-content-studio'); ?></li>
                        <li><?php _e('Quand un utilisateur s\'abonne, son plan sera automatiquement mis à jour via les webhooks WooCommerce.', 'ai-content-studio'); ?></li>
                        <li><?php _e('Les limites d\'utilisation seront appliquées automatiquement selon le plan.', 'ai-content-studio'); ?></li>
                    </ol>

                    <p><strong><?php _e('Note:', 'ai-content-studio'); ?></strong> <?php _e('Le plan "Free Trial" n\'est pas un produit WooCommerce car il est gratuit et attribué automatiquement à l\'inscription.', 'ai-content-studio'); ?></p>
                </div>

                <!-- User Plan Migration -->
                <div class="card" style="max-width: 100%; margin-top: 20px;">
                    <h2><?php _e('Migration des Utilisateurs', 'ai-content-studio'); ?></h2>

                    <p><?php _e('Cette section permet de migrer les utilisateurs existants des anciens noms de plans vers les nouveaux:', 'ai-content-studio'); ?></p>

                    <ul style="list-style: disc; margin-left: 20px; margin-bottom: 15px;">
                        <li><code>free</code> → <code>free_trial</code></li>
                        <li><code>pro</code> → <code>professional</code></li>
                    </ul>

                    <?php
                    $migration_needed = Plan_Migration::check_migration_needed();
                    if ($migration_needed['total'] > 0):
                    ?>
                        <div class="notice notice-warning inline" style="margin: 15px 0; padding: 10px;">
                            <p>
                                <span class="dashicons dashicons-warning" style="color: #f0b849;"></span>
                                <?php echo sprintf(__('<strong>%d utilisateur(s)</strong> utilisent encore les anciens noms de plans et doivent être migrés.', 'ai-content-studio'), $migration_needed['total']); ?>
                            </p>
                        </div>

                        <table class="widefat" style="margin: 15px 0; max-width: 500px;">
                            <tr>
                                <td style="padding: 10px;">
                                    <span class="dashicons dashicons-admin-users" style="color: #2271b1;"></span>
                                    Utilisateurs avec plan "free"
                                </td>
                                <td style="padding: 10px; text-align: right;">
                                    <strong><?php echo esc_html($migration_needed['free_users']); ?></strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 10px; background: #f6f7f7;">
                                    <span class="dashicons dashicons-admin-users" style="color: #2271b1;"></span>
                                    Utilisateurs avec plan "pro"
                                </td>
                                <td style="padding: 10px; text-align: right; background: #f6f7f7;">
                                    <strong><?php echo esc_html($migration_needed['pro_users']); ?></strong>
                                </td>
                            </tr>
                        </table>

                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" onsubmit="return confirm('Êtes-vous sûr de vouloir migrer <?php echo esc_js($migration_needed['total']); ?> utilisateur(s) ?');">
                            <?php wp_nonce_field('acs_migrate_plans', 'acs_nonce'); ?>
                            <input type="hidden" name="action" value="acs_migrate_plans">
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                                <?php _e('Migrer les utilisateurs maintenant', 'ai-content-studio'); ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="notice notice-success inline" style="margin: 15px 0; padding: 10px;">
                            <p>
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                <?php _e('Tous les utilisateurs sont déjà sur les nouveaux plans. Aucune migration nécessaire.', 'ai-content-studio'); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        </div>

        <style>
            .card {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                padding: 20px;
                margin-bottom: 20px;
            }
            .card h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            .button-hero {
                height: 50px;
                line-height: 50px;
                padding: 0 20px;
                font-size: 14px;
            }
        </style>
        <?php
    }

    /**
     * Handle product generation
     */
    public function handle_generate_products() {
        // Security check
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'ai-content-studio'));
        }

        if (!isset($_POST['acs_nonce']) || !wp_verify_nonce($_POST['acs_nonce'], 'acs_generate_products')) {
            wp_die(__('Nonce invalide.', 'ai-content-studio'));
        }

        // Generate products
        $result = Product_Generator::generate_all_products();

        if ($result['success']) {
            wp_redirect(admin_url('admin.php?page=ai-content-studio-subscriptions&message=products_generated'));
        } else {
            wp_redirect(admin_url('admin.php?page=ai-content-studio-subscriptions&message=error'));
        }
        exit;
    }

    /**
     * Handle product deletion
     */
    public function handle_delete_products() {
        // Security check
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'ai-content-studio'));
        }

        if (!isset($_POST['acs_nonce']) || !wp_verify_nonce($_POST['acs_nonce'], 'acs_delete_products')) {
            wp_die(__('Nonce invalide.', 'ai-content-studio'));
        }

        // Delete products
        $result = Product_Generator::delete_all_products();

        if ($result['success']) {
            wp_redirect(admin_url('admin.php?page=ai-content-studio-subscriptions&message=products_deleted'));
        } else {
            wp_redirect(admin_url('admin.php?page=ai-content-studio-subscriptions&message=error'));
        }
        exit;
    }

    /**
     * Handle plan migration
     */
    public function handle_migrate_plans() {
        // Security check
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'ai-content-studio'));
        }

        if (!isset($_POST['acs_nonce']) || !wp_verify_nonce($_POST['acs_nonce'], 'acs_migrate_plans')) {
            wp_die(__('Nonce invalide.', 'ai-content-studio'));
        }

        // Migrate plans
        $result = Plan_Migration::migrate_user_plans();

        if (count($result['errors']) === 0) {
            $message = sprintf(
                'plans_migrated&free=%d&pro=%d',
                $result['free_to_trial'],
                $result['pro_to_professional']
            );
            wp_redirect(admin_url('admin.php?page=ai-content-studio-subscriptions&message=' . $message));
        } else {
            wp_redirect(admin_url('admin.php?page=ai-content-studio-subscriptions&message=migration_error'));
        }
        exit;
    }
}
