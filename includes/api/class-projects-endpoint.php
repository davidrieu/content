<?php
/**
 * Projects REST Endpoint
 *
 * @package ACS\API
 */

namespace ACS\API;

use ACS\Models\Project;

class Projects_Endpoint extends REST_Controller {

    public function register_routes() {
        // Get all projects for current user
        register_rest_route($this->namespace, '/projects', [
            'methods' => 'GET',
            'callback' => [$this, 'get_projects'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        // Get active project
        register_rest_route($this->namespace, '/projects/active', [
            'methods' => 'GET',
            'callback' => [$this, 'get_active_project'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        // Create new project
        register_rest_route($this->namespace, '/projects', [
            'methods' => 'POST',
            'callback' => [$this, 'create_project'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        // Switch active project
        register_rest_route($this->namespace, '/projects/switch/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'switch_project'],
            'permission_callback' => [$this, 'permission_check'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // Update project
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_project'],
            'permission_callback' => [$this, 'permission_check'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // Delete project
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_project'],
            'permission_callback' => [$this, 'permission_check'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);
    }

    /**
     * Get all projects for current user
     */
    public function get_projects($request) {
        $user_id = $this->get_current_user_id();
        $project_model = new Project();

        $projects = $project_model->get_by_user($user_id);

        return $this->success([
            'projects' => $projects,
            'total' => count($projects),
        ]);
    }

    /**
     * Get active project for current user
     */
    public function get_active_project($request) {
        $user_id = $this->get_current_user_id();
        $project_model = new Project();

        $active_project = $project_model->get_active_project($user_id);

        if (!$active_project) {
            // No active project - check if user has any projects
            $all_projects = $project_model->get_by_user($user_id);

            if (empty($all_projects)) {
                return $this->success([
                    'project' => null,
                    'has_projects' => false,
                    'message' => __('Aucun projet trouvé. Veuillez créer un projet.', 'ai-content-studio'),
                ]);
            }

            // User has projects but none active - activate the first one
            $first_project = $all_projects[0];
            $project_model->set_active_project($user_id, $first_project['id']);
            $active_project = $project_model->get_by_id($first_project['id']);
        }

        return $this->success([
            'project' => $active_project,
            'has_projects' => true,
        ]);
    }

    /**
     * Create new project
     */
    public function create_project($request) {
        $user_id = $this->get_current_user_id();
        $project_model = new Project();

        $data = $request->get_json_params();

        // Add user_id to data
        $data['user_id'] = $user_id;

        // Check if this should be the active project
        $existing_projects = $project_model->get_by_user($user_id);
        $is_first_project = empty($existing_projects);

        // First project is automatically active
        $data['is_active'] = $is_first_project || ($data['is_active'] ?? false);

        $project_id = $project_model->create($data);

        if (!$project_id) {
            return $this->error(
                __('Erreur lors de la création du projet', 'ai-content-studio'),
                'project_creation_failed',
                500
            );
        }

        $project = $project_model->get_by_id($project_id);

        return $this->success([
            'project' => $project,
            'message' => __('Projet créé avec succès', 'ai-content-studio'),
        ]);
    }

    /**
     * Switch active project
     */
    public function switch_project($request) {
        $user_id = $this->get_current_user_id();
        $project_id = $request->get_param('id');
        $project_model = new Project();

        // Verify project belongs to user
        $project = $project_model->get_by_id($project_id);

        if (!$project || $project['user_id'] != $user_id) {
            return $this->error(
                __('Projet introuvable ou accès refusé', 'ai-content-studio'),
                'project_not_found',
                404
            );
        }

        $success = $project_model->set_active_project($user_id, $project_id);

        if (!$success) {
            return $this->error(
                __('Erreur lors du changement de projet', 'ai-content-studio'),
                'switch_failed',
                500
            );
        }

        $updated_project = $project_model->get_by_id($project_id);

        return $this->success([
            'project' => $updated_project,
            'message' => __('Projet activé avec succès', 'ai-content-studio'),
        ]);
    }

    /**
     * Update project
     */
    public function update_project($request) {
        $user_id = $this->get_current_user_id();
        $project_id = $request->get_param('id');
        $project_model = new Project();

        // Verify project belongs to user
        $project = $project_model->get_by_id($project_id);

        if (!$project || $project['user_id'] != $user_id) {
            return $this->error(
                __('Projet introuvable ou accès refusé', 'ai-content-studio'),
                'project_not_found',
                404
            );
        }

        $data = $request->get_json_params();

        $result = $project_model->update($project_id, $data);

        if ($result === false) {
            return $this->error(
                __('Erreur lors de la mise à jour du projet', 'ai-content-studio'),
                'update_failed',
                500
            );
        }

        $updated_project = $project_model->get_by_id($project_id);

        return $this->success([
            'project' => $updated_project,
            'message' => __('Projet mis à jour avec succès', 'ai-content-studio'),
        ]);
    }

    /**
     * Delete project
     */
    public function delete_project($request) {
        $user_id = $this->get_current_user_id();
        $project_id = $request->get_param('id');
        $project_model = new Project();

        // Verify project belongs to user
        $project = $project_model->get_by_id($project_id);

        if (!$project || $project['user_id'] != $user_id) {
            return $this->error(
                __('Projet introuvable ou accès refusé', 'ai-content-studio'),
                'project_not_found',
                404
            );
        }

        // Check if this is the only project
        $all_projects = $project_model->get_by_user($user_id);
        if (count($all_projects) === 1) {
            return $this->error(
                __('Impossible de supprimer le dernier projet. Vous devez avoir au moins un projet.', 'ai-content-studio'),
                'cannot_delete_last_project',
                400
            );
        }

        // If deleting active project, activate another one
        if ($project['is_active']) {
            foreach ($all_projects as $other_project) {
                if ($other_project['id'] != $project_id) {
                    $project_model->set_active_project($user_id, $other_project['id']);
                    break;
                }
            }
        }

        $result = $project_model->delete($project_id, $user_id);

        if (!$result) {
            return $this->error(
                __('Erreur lors de la suppression du projet', 'ai-content-studio'),
                'delete_failed',
                500
            );
        }

        return $this->success([
            'message' => __('Projet supprimé avec succès', 'ai-content-studio'),
        ]);
    }
}
