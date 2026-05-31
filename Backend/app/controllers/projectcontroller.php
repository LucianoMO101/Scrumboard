<?php

namespace Controllers;

use Exception;
use Services\ProjectService;
use Services\ProjectRoleService;
use Services\ActivityLogService;
use Models\ProjectRole;

class ProjectController extends Controller {

    private $projectService;
    private $projectRoleService;
    private $activityLogService;

    public function __construct() {
        parent::__construct();
        $this->projectService = new ProjectService();
        $this->projectRoleService = new ProjectRoleService();
        $this->activityLogService = new ActivityLogService();
    }

    /* GET /projects and Get all projects for authenticated user */
    public function getAllProjects(): void {
        try {
            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $projects = $this->projectService->getUserProjects($user_id);

            $result = [];
            foreach ($projects as $project) {
                $result[] = [
                    'project_id'   => $project->project_id,
                    'project_name' => $project->project_name,
                    'description'  => $project->description,
                    'team_id'      => $project->team_id,
                    'team_name'    => $project->team_name,
                    'owner_id'     => $project->owner_id,
                    'created_at'   => $project->created_at?->format('Y-m-d H:i:s')
                ];
            }

            $this->respond(['success' => true, 'data' => $result], 200);

        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    /* GET /projects/:id and Get project by ID*/
    public function getProject(): void {
        try {
            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $project_id = $this->getUrlParam('id');
            if (empty($project_id)) {
                $this->respondWithError(400, "Project ID is required");
            }

            // Check if user has access
            if (!$this->projectRoleService->hasAccess($user_id, (int)$project_id)) {
                $this->respondWithError(403, "You don't have access to this project");
            }

            $project = $this->projectService->getProject((int)$project_id);
            if ($project === null) {
                $this->respondWithError(404, "Project not found");
            }

            $result = [
                'project_id' => $project->project_id,
                'project_name' => $project->project_name,
                'description' => $project->description,
                'team_id' => $project->team_id,
                'owner_id' => $project->owner_id,
                'created_at' => $project->created_at?->format('Y-m-d H:i:s'),
                'role' => $this->projectRoleService->getUserRole($user_id, $project->project_id)?->value
            ];

            $this->respond(['success' => true, 'data' => $result], 200);

        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    /* POST /projects and Create new project */
    public function createProject(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $data = $this->getJsonData();

            if (empty($data['project_name']) || empty($data['team_id'])) {
                $this->respondWithError(400, "Project name and team ID are required");
            }

            $project_id = $this->projectService->createProject(
                $data['project_name'],
                $data['description'] ?? '',
                (int)$data['team_id'],
                $user_id
            );

            $project = $this->projectService->getProject($project_id);

            $this->activityLogService->log($user_id, $project_id, 'project', 'created', $data['project_name']);

            $this->respond([
                'success' => true,
                'data' => [
                    'project_id' => $project->project_id,
                    'project_name' => $project->project_name,
                    'description' => $project->description,
                    'team_id' => $project->team_id,
                    'owner_id' => $project->owner_id,
                    'created_at' => $project->created_at?->format('Y-m-d H:i:s')
                ]
            ], 201);

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* PUT /projects/:id and Update project */
    public function updateProject(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $project_id = $this->getUrlParam('id');
            if (empty($project_id)) {
                $this->respondWithError(400, "Project ID is required");
            }

            $data = $this->getJsonData();

            if (empty($data['project_name'])) {
                $this->respondWithError(400, "Project name is required");
            }

            $success = $this->projectService->updateProject(
                (int)$project_id,
                $data['project_name'],
                $data['description'] ?? '',
                $user_id
            );

            if ($success) {
                $this->respond(['success' => true, 'message' => 'Project updated'], 200);
            } else {
                $this->respondWithError(500, "Failed to update project");
            }

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* DELETE /projects/:id and Delete project */
    public function deleteProject(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $project_id = $this->getUrlParam('id');
            if (empty($project_id)) {
                $this->respondWithError(400, "Project ID is required");
            }

            $success = $this->projectService->deleteProject((int)$project_id, $user_id);

            if ($success) {
                $this->respond(['success' => true, 'message' => 'Project deleted'], 200);
            } else {
                $this->respondWithError(500, "Failed to delete project");
            }

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* GET /projects/:id/members and Get project members */
    public function getProjectMembers(): void {
        try {
            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $project_id = $this->getUrlParam('id');
            if (empty($project_id)) {
                $this->respondWithError(400, "Project ID is required");
            }

            // Check if user has access
            if (!$this->projectRoleService->hasAccess($user_id, (int)$project_id)) {
                $this->respondWithError(403, "You don't have access to this project");
            }

            $members = $this->projectService->getProjectMembers((int)$project_id);

            $result = [];
            foreach ($members as $member) {
                $result[] = [
                    'user_id'    => $member->user_id,
                    'project_id' => $member->project_id,
                    'role'       => $member->role->value,
                    'user_name'  => $member->user_name ?? null,
                    'email'      => $member->email ?? null,
                ];
            }

            $this->respond(['success' => true, 'data' => $result], 200);

        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    /* POST /projects/:id/members and Add member to project */
    public function addProjectMember(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $project_id = $this->getUrlParam('id');
            if (empty($project_id)) {
                $this->respondWithError(400, "Project ID is required");
            }

            $data = $this->getJsonData();

            if (empty($data['user_id']) || empty($data['role'])) {
                $this->respondWithError(400, "User ID and role are required");
            }

            $roleEnum = ProjectRole::from($data['role']);

            $this->projectRoleService->assignRole((int)$data['user_id'], (int)$project_id, $roleEnum, $user_id);

            $this->activityLogService->log($user_id, (int)$project_id, 'member', 'assigned', null, "User {$data['user_id']} assigned as {$data['role']}");

            $this->respond(['success' => true, 'message' => 'Member added to project'], 201);

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* PUT /projects/:id/members/:user_id and Update member role */
    public function updateProjectMember(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $project_id = $this->getUrlParam('id');
            $target_user_id = $this->getSecondUrlParam();

            if (empty($project_id) || empty($target_user_id)) {
                $this->respondWithError(400, "Project ID and user ID are required");
            }

            $data = $this->getJsonData();

            if (empty($data['role'])) {
                $this->respondWithError(400, "Role is required");
            }

            $roleEnum = ProjectRole::from($data['role']);

            $this->projectRoleService->assignRole((int)$target_user_id, (int)$project_id, $roleEnum, $user_id);

            $this->activityLogService->log($user_id, (int)$project_id, 'member', 'updated', null, "User {$target_user_id} role changed to {$data['role']}");

            $this->respond(['success' => true, 'message' => 'Member role updated'], 200);

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* DELETE /projects/:id/members/:user_id and Remove member from project */
    public function removeProjectMember(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $project_id = $this->getUrlParam('id');
            $target_user_id = $this->getSecondUrlParam();

            if (empty($project_id) || empty($target_user_id)) {
                $this->respondWithError(400, "Project ID and user ID are required");
            }

            $this->projectRoleService->removeUserFromProject((int)$target_user_id, (int)$project_id, $user_id);

            $this->activityLogService->log($user_id, (int)$project_id, 'member', 'deleted', null, "User {$target_user_id} removed from project");

            $this->respond(['success' => true, 'message' => 'Member removed from project'], 200);

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* GET /projects/:id/activity and Get activity log for project */
    public function getProjectActivity(): void {
        try {
            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $project_id = $this->getUrlParam('id');
            if (empty($project_id)) {
                $this->respondWithError(400, "Project ID is required");
            }

            // Check if user has access to this project
            if (!$this->projectRoleService->hasAccess($user_id, (int)$project_id)) {
                $this->respondWithError(403, "You don't have access to this project");
            }

            $page   = max(1, (int)($_GET['page']  ?? 1));
            $limit  = min(100, max(1, (int)($_GET['limit'] ?? 20)));
            $action      = $_GET['action']      ?? null;
            $entity_type = $_GET['entity_type'] ?? null;

            $result = $this->activityLogService->getProjectLog(
                (int)$project_id,
                $page,
                $limit,
                $action      ?: null,
                $entity_type ?: null
            );

            $this->respond(['success' => true, 'data' => $result['data'], 'meta' => $result['meta']], 200);

        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }
}

?>
