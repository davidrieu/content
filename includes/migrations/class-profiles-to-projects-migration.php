<?php
/**
 * Migration: Business Profiles to Projects
 *
 * Converts existing business_profiles records to projects table
 *
 * @package ACS\Migrations
 */

namespace ACS\Migrations;

use ACS\Models\Project;
use ACS\Utils\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Profiles_To_Projects_Migration class
 */
class Profiles_To_Projects_Migration {

    /**
     * Option name to track if migration was run
     */
    const MIGRATION_FLAG = 'acs_profiles_to_projects_migration_completed';

    /**
     * Check if migration was already run
     *
     * @return bool
     */
    public static function is_completed() {
        return get_option(self::MIGRATION_FLAG, false);
    }

    /**
     * Mark migration as completed
     */
    private static function mark_completed() {
        update_option(self::MIGRATION_FLAG, true);
    }

    /**
     * Run the migration
     *
     * @return array Results with success/error messages
     */
    public static function run() {
        // Check if already run
        if (self::is_completed()) {
            return [
                'success' => false,
                'message' => __('La migration a déjà été exécutée.', 'ai-content-studio'),
                'already_run' => true,
            ];
        }

        global $wpdb;
        $profiles_table = $wpdb->prefix . ACS_TABLE_PREFIX . 'business_profiles';
        $projects_table = $wpdb->prefix . ACS_TABLE_PREFIX . 'projects';

        // Check if business_profiles table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$profiles_table'") !== $profiles_table) {
            return [
                'success' => false,
                'message' => __('La table business_profiles n\'existe pas.', 'ai-content-studio'),
            ];
        }

        // Get all business profiles
        $profiles = $wpdb->get_results("SELECT * FROM $profiles_table", ARRAY_A);

        if (empty($profiles)) {
            // No profiles to migrate, mark as completed anyway
            self::mark_completed();
            return [
                'success' => true,
                'message' => __('Aucun profil à migrer.', 'ai-content-studio'),
                'migrated_count' => 0,
            ];
        }

        $project_model = new Project();
        $migrated_count = 0;
        $errors = [];

        Logger::info('Starting profiles to projects migration', ['count' => count($profiles)]);

        foreach ($profiles as $profile) {
            try {
                // Check if user already has projects
                $existing_projects = $project_model->get_by_user($profile['user_id']);

                // Only migrate if user has no projects yet
                if (empty($existing_projects)) {
                    // Prepare project data from profile
                    $project_data = [
                        'user_id' => $profile['user_id'],
                        'project_name' => $profile['business_name'],
                        'user_type' => $profile['user_type'] ?? '',
                        'sector' => $profile['sector'] ?? '',
                        'description' => $profile['description'] ?? '',
                        'website' => $profile['website'] ?? '',
                        'goals' => $profile['goals'] ?? '[]',
                        'platforms' => $profile['social_platforms'] ?? '[]',
                        'posting_frequency' => $profile['posting_frequency'] ?? 'weekly',
                        'has_blog' => !empty($profile['has_blog']) ? 1 : 0,
                        'blog_topics' => $profile['blog_topics'] ?? '[]',
                        'seo_goals' => $profile['seo_goals'] ?? '[]',
                        'primary_keywords' => $profile['primary_keywords'] ?? '[]',
                        'niche' => $profile['niche'] ?? '',
                        'target_audience' => $profile['target_audience'] ?? '',
                        'languages' => $profile['languages'] ?? '["fr"]',
                    ];

                    // Create project (will automatically set as active since it's the first one)
                    $project_id = $project_model->create($project_data);

                    if ($project_id) {
                        $migrated_count++;
                        Logger::info('Profile migrated to project', [
                            'user_id' => $profile['user_id'],
                            'profile_id' => $profile['id'],
                            'project_id' => $project_id,
                        ]);
                    } else {
                        $errors[] = sprintf(
                            __('Erreur lors de la migration du profil ID %d (utilisateur %d)', 'ai-content-studio'),
                            $profile['id'],
                            $profile['user_id']
                        );
                    }
                } else {
                    Logger::info('User already has projects, skipping', [
                        'user_id' => $profile['user_id'],
                    ]);
                }
            } catch (\Exception $e) {
                $error_msg = sprintf(
                    __('Exception lors de la migration du profil ID %d: %s', 'ai-content-studio'),
                    $profile['id'],
                    $e->getMessage()
                );
                $errors[] = $error_msg;
                Logger::error($error_msg);
            }
        }

        // Mark as completed even if there were some errors
        self::mark_completed();

        Logger::info('Profiles to projects migration completed', [
            'total_profiles' => count($profiles),
            'migrated' => $migrated_count,
            'errors' => count($errors),
        ]);

        $message = sprintf(
            __('%d profil(s) migrés sur %d.', 'ai-content-studio'),
            $migrated_count,
            count($profiles)
        );

        if (!empty($errors)) {
            $message .= ' ' . __('Erreurs:', 'ai-content-studio') . ' ' . implode(', ', $errors);
        }

        return [
            'success' => true,
            'message' => $message,
            'migrated_count' => $migrated_count,
            'total_profiles' => count($profiles),
            'errors' => $errors,
        ];
    }

    /**
     * Reset migration flag (for testing purposes)
     */
    public static function reset() {
        delete_option(self::MIGRATION_FLAG);
    }
}
