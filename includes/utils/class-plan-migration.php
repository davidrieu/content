<?php
/**
 * Migration script pour mettre à jour les plans des utilisateurs existants
 *
 * Ce script migre:
 * - 'free' -> 'free_trial'
 * - 'pro' -> 'professional'
 *
 * @package ACS
 */

namespace ACS\Utils;

if (!defined('ABSPATH')) {
    exit;
}

class Plan_Migration {

    /**
     * Exécuter la migration des plans
     *
     * @return array Résultats de la migration
     */
    public static function migrate_user_plans() {
        global $wpdb;

        $results = [
            'free_to_trial' => 0,
            'pro_to_professional' => 0,
            'errors' => [],
        ];

        // Récupérer tous les utilisateurs avec l'ancien plan 'free'
        $free_users = $wpdb->get_results("
            SELECT user_id, meta_value
            FROM {$wpdb->usermeta}
            WHERE meta_key = 'acs_subscription_plan'
            AND meta_value = 'free'
        ");

        foreach ($free_users as $user) {
            $updated = update_user_meta($user->user_id, 'acs_subscription_plan', 'free_trial');
            if ($updated) {
                $results['free_to_trial']++;
                Logger::info('User plan migrated', [
                    'user_id' => $user->user_id,
                    'from' => 'free',
                    'to' => 'free_trial',
                ], 'migration');
            } else {
                $results['errors'][] = "Failed to migrate user {$user->user_id} from free to free_trial";
            }
        }

        // Récupérer tous les utilisateurs avec l'ancien plan 'pro'
        $pro_users = $wpdb->get_results("
            SELECT user_id, meta_value
            FROM {$wpdb->usermeta}
            WHERE meta_key = 'acs_subscription_plan'
            AND meta_value = 'pro'
        ");

        foreach ($pro_users as $user) {
            $updated = update_user_meta($user->user_id, 'acs_subscription_plan', 'professional');
            if ($updated) {
                $results['pro_to_professional']++;
                Logger::info('User plan migrated', [
                    'user_id' => $user->user_id,
                    'from' => 'pro',
                    'to' => 'professional',
                ], 'migration');
            } else {
                $results['errors'][] = "Failed to migrate user {$user->user_id} from pro to professional";
            }
        }

        return $results;
    }

    /**
     * Vérifier combien d'utilisateurs nécessitent une migration
     *
     * @return array Statistiques
     */
    public static function check_migration_needed() {
        global $wpdb;

        $free_count = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->usermeta}
            WHERE meta_key = 'acs_subscription_plan'
            AND meta_value = 'free'
        ");

        $pro_count = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->usermeta}
            WHERE meta_key = 'acs_subscription_plan'
            AND meta_value = 'pro'
        ");

        return [
            'free_users' => intval($free_count),
            'pro_users' => intval($pro_count),
            'total' => intval($free_count) + intval($pro_count),
        ];
    }

    /**
     * Afficher un rapport de migration
     */
    public static function display_migration_report() {
        $needed = self::check_migration_needed();

        echo '<div class="notice notice-info">';
        echo '<h3>📊 ' . __('Rapport de Migration des Plans', 'ai-content-studio') . '</h3>';

        if ($needed['total'] == 0) {
            echo '<p>✅ ' . __('Aucune migration nécessaire. Tous les utilisateurs sont déjà sur les nouveaux plans.', 'ai-content-studio') . '</p>';
        } else {
            echo '<p>' . sprintf(
                __('%d utilisateur(s) nécessitent une migration:', 'ai-content-studio'),
                $needed['total']
            ) . '</p>';
            echo '<ul>';
            echo '<li>' . sprintf(__('%d utilisateur(s) avec le plan "free" à migrer vers "free_trial"', 'ai-content-studio'), $needed['free_users']) . '</li>';
            echo '<li>' . sprintf(__('%d utilisateur(s) avec le plan "pro" à migrer vers "professional"', 'ai-content-studio'), $needed['pro_users']) . '</li>';
            echo '</ul>';
        }

        echo '</div>';
    }
}
