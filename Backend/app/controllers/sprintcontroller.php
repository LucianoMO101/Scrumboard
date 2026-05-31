<?php

namespace Controllers;

use Exception;
use Services\SprintService;
use Services\ProjectRoleService;
use Services\ActivityLogService;

class SprintController extends Controller {

    private $sprintService;
    private $projectRoleService;
    private $activityLogService;

    public function __construct() {
        parent::__construct();
        $this->sprintService = new SprintService();
        $this->projectRoleService = new ProjectRoleService();
        $this->activityLogService = new ActivityLogService();
    }

    /* GET /projects/:project_id/sprints and Get all sprints for project */
    public function getSprints(): void {
        try {
            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $project_id = $this->getUrlParam('project_id');
            if (empty($project_id)) {
                $this->respondWithError(400, "Project ID is required");
            }

            // Check if user has access
            if (!$this->projectRoleService->hasAccess($user_id, (int)$project_id)) {
                $this->respondWithError(403, "You don't have access to this project");
            }

            $sprints = $this->sprintService->getProjectSprints((int)$project_id);

            $result = [];
            foreach ($sprints as $sprint) {
                $result[] = [
                    'sprint_id' => $sprint->sprint_id,
                    'project_id' => $sprint->project_id,
                    'sprint_name' => $sprint->sprint_name,
                    'description' => $sprint->description,
                    'start_date' => $sprint->start_date,
                    'end_date' => $sprint->end_date,
                    'status' => $sprint->status->value,
                    'created_at' => $sprint->created_at?->format('Y-m-d H:i:s')
                ];
            }

            $this->respond(['success' => true, 'data' => $result], 200);

        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    /* GET /sprints/:id and Get sprint by ID */
    public function getSprint(): void {
        try {
            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $sprint_id = $this->getUrlParam('id');
            if (empty($sprint_id)) {
                $this->respondWithError(400, "Sprint ID is required");
            }

            $sprint = $this->sprintService->getSprint((int)$sprint_id);
            if ($sprint === null) {
                $this->respondWithError(404, "Sprint not found");
            }

            // Check if user has access
            if (!$this->projectRoleService->hasAccess($user_id, $sprint->project_id)) {
                $this->respondWithError(403, "You don't have access to this sprint");
            }

            $result = [
                'sprint_id' => $sprint->sprint_id,
                'project_id' => $sprint->project_id,
                'sprint_name' => $sprint->sprint_name,
                'description' => $sprint->description,
                'start_date' => $sprint->start_date,
                'end_date' => $sprint->end_date,
                'status' => $sprint->status->value,
                'created_at' => $sprint->created_at?->format('Y-m-d H:i:s')
            ];

            $this->respond(['success' => true, 'data' => $result], 200);

        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    /* POST /sprints and Create new sprint */
    public function createSprint(): void {
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

            if (empty($data['project_id']) || empty($data['sprint_name']) || empty($data['start_date']) || empty($data['end_date'])) {
                $this->respondWithError(400, "Project ID, sprint name, start date and end date are required");
            }

            // Only editors/owners can create sprints
            if (!$this->projectRoleService->isEditor($user_id, (int)$data['project_id'])) {
                $this->respondWithError(403, "You need editor or owner role to create sprints");
            }

            $sprint_id = $this->sprintService->createSprint(
                (int)$data['project_id'],
                $data['sprint_name'],
                $data['description'] ?? '',
                $data['start_date'],
                $data['end_date'],
                $user_id
            );

            $sprint = $this->sprintService->getSprint($sprint_id);

            $this->activityLogService->log($user_id, (int)$data['project_id'], 'sprint', 'created', $data['sprint_name']);

            $this->respond([
                'success' => true,
                'data' => [
                    'sprint_id'   => $sprint->sprint_id,
                    'project_id'  => $sprint->project_id,
                    'sprint_name' => $sprint->sprint_name,
                    'description' => $sprint->description,
                    'start_date'  => $sprint->start_date,
                    'end_date'    => $sprint->end_date,
                    'status'      => $sprint->status->value,
                    'created_at'  => $sprint->created_at?->format('Y-m-d H:i:s')
                ]
            ], 201);

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* PUT /sprints/:id and Update sprint */
    public function updateSprint(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $sprint_id = $this->getUrlParam('id');
            if (empty($sprint_id)) {
                $this->respondWithError(400, "Sprint ID is required");
            }

            $updateSprint = $this->sprintService->getSprint((int)$sprint_id);
            if ($updateSprint === null) {
                $this->respondWithError(404, "Sprint not found");
            }

            // Only editors/owners can update sprints
            if (!$this->projectRoleService->isEditor($user_id, $updateSprint->project_id)) {
                $this->respondWithError(403, "You need editor or owner role to update sprints");
            }

            $data = $this->getJsonData();

            if (empty($data['sprint_name'])) {
                $this->respondWithError(400, "Sprint name is required");
            }

            $success = $this->sprintService->updateSprint(
                (int)$sprint_id,
                $data['sprint_name'],
                $data['description'] ?? '',
                $data['start_date'] ?? '',
                $data['end_date'] ?? '',
                $user_id
            );

            if ($success) {
                $sprint = $this->sprintService->getSprint((int)$sprint_id);
                $this->activityLogService->log($user_id, $sprint->project_id, 'sprint', 'updated', $sprint->sprint_name);
                $this->respond(['success' => true, 'data' => [
                    'sprint_id'   => $sprint->sprint_id,
                    'project_id'  => $sprint->project_id,
                    'sprint_name' => $sprint->sprint_name,
                    'description' => $sprint->description,
                    'start_date'  => $sprint->start_date,
                    'end_date'    => $sprint->end_date,
                    'status'      => $sprint->status->value,
                    'created_at'  => $sprint->created_at?->format('Y-m-d H:i:s')
                ]], 200);
            } else {
                $this->respondWithError(500, "Failed to update sprint");
            }

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* POST /sprints/:id/start and Start sprint */
    public function startSprint(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $sprint_id = $this->getUrlParam('id');
            if (empty($sprint_id)) {
                $this->respondWithError(400, "Sprint ID is required");
            }

            $startSprint = $this->sprintService->getSprint((int)$sprint_id);
            if ($startSprint === null) {
                $this->respondWithError(404, "Sprint not found");
            }

            // Only editors/owners can start sprints
            if (!$this->projectRoleService->isEditor($user_id, $startSprint->project_id)) {
                $this->respondWithError(403, "You need editor or owner role to start sprints");
            }

            $success = $this->sprintService->startSprint((int)$sprint_id, $user_id);

            if ($success) {
                $sprint = $this->sprintService->getSprint((int)$sprint_id);
                $this->activityLogService->log($user_id, $sprint->project_id, 'sprint', 'started', $sprint->sprint_name);
                $this->respond(['success' => true, 'data' => [
                    'sprint_id'   => $sprint->sprint_id,
                    'project_id'  => $sprint->project_id,
                    'sprint_name' => $sprint->sprint_name,
                    'description' => $sprint->description,
                    'start_date'  => $sprint->start_date,
                    'end_date'    => $sprint->end_date,
                    'status'      => $sprint->status->value,
                    'created_at'  => $sprint->created_at?->format('Y-m-d H:i:s')
                ]], 200);
            } else {
                $this->respondWithError(500, "Failed to start sprint");
            }

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* POST /sprints/:id/complete and Complete sprint */
    public function completeSprint(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $sprint_id = $this->getUrlParam('id');
            if (empty($sprint_id)) {
                $this->respondWithError(400, "Sprint ID is required");
            }

            $completeSprint = $this->sprintService->getSprint((int)$sprint_id);
            if ($completeSprint === null) {
                $this->respondWithError(404, "Sprint not found");
            }

            // Only editors/owners can complete sprints
            if (!$this->projectRoleService->isEditor($user_id, $completeSprint->project_id)) {
                $this->respondWithError(403, "You need editor or owner role to complete sprints");
            }

            $success = $this->sprintService->completeSprint((int)$sprint_id, $user_id);

            if ($success) {
                $sprint = $this->sprintService->getSprint((int)$sprint_id);
                $this->activityLogService->log($user_id, $sprint->project_id, 'sprint', 'completed', $sprint->sprint_name);
                $this->respond(['success' => true, 'data' => [
                    'sprint_id'   => $sprint->sprint_id,
                    'project_id'  => $sprint->project_id,
                    'sprint_name' => $sprint->sprint_name,
                    'description' => $sprint->description,
                    'start_date'  => $sprint->start_date,
                    'end_date'    => $sprint->end_date,
                    'status'      => $sprint->status->value,
                    'created_at'  => $sprint->created_at?->format('Y-m-d H:i:s')
                ]], 200);
            } else {
                $this->respondWithError(500, "Failed to complete sprint");
            }

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* DELETE /sprints/:id and Delete sprint */
    public function deleteSprint(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $sprint_id = $this->getUrlParam('id');
            if (empty($sprint_id)) {
                $this->respondWithError(400, "Sprint ID is required");
            }

            $deleteSprint = $this->sprintService->getSprint((int)$sprint_id);
            if ($deleteSprint === null) {
                $this->respondWithError(404, "Sprint not found");
            }

            // Only editors/owners can delete sprints
            if (!$this->projectRoleService->isEditor($user_id, $deleteSprint->project_id)) {
                $this->respondWithError(403, "You need editor or owner role to delete sprints");
            }

            $success = $this->sprintService->deleteSprint((int)$sprint_id, $user_id);

            if ($success) {
                $this->activityLogService->log($user_id, $deleteSprint->project_id, 'sprint', 'deleted', $deleteSprint->sprint_name);
                $this->respond(['success' => true, 'message' => 'Sprint deleted'], 200);
            } else {
                $this->respondWithError(500, "Failed to delete sprint");
            }

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* POST /sprints/:id/reopen and Reopen a completed sprint */
    public function reopenSprint(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $sprint_id = $this->getUrlParam('id');
            if (empty($sprint_id)) {
                $this->respondWithError(400, "Sprint ID is required");
            }

            $sprint = $this->sprintService->getSprint((int)$sprint_id);
            if ($sprint === null) {
                $this->respondWithError(404, "Sprint not found");
            }

            if (!$this->projectRoleService->isEditor($user_id, $sprint->project_id)) {
                $this->respondWithError(403, "You need editor or owner role to reopen sprints");
            }

            $success = $this->sprintService->reopenSprint((int)$sprint_id, $user_id);

            if ($success) {
                $sprint = $this->sprintService->getSprint((int)$sprint_id);
                $this->activityLogService->log($user_id, $sprint->project_id, 'sprint', 'updated', $sprint->sprint_name);
                $this->respond(['success' => true, 'data' => [
                    'sprint_id'   => $sprint->sprint_id,
                    'project_id'  => $sprint->project_id,
                    'sprint_name' => $sprint->sprint_name,
                    'description' => $sprint->description,
                    'start_date'  => $sprint->start_date,
                    'end_date'    => $sprint->end_date,
                    'status'      => $sprint->status->value,
                    'created_at'  => $sprint->created_at?->format('Y-m-d H:i:s')
                ]], 200);
            } else {
                $this->respondWithError(500, "Failed to reopen sprint");
            }

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }
}

?>
