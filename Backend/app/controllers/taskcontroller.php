<?php

namespace Controllers;

use Exception;
use Services\TaskService;
use Services\ProjectRoleService;
use Services\ActivityLogService;

class TaskController extends Controller {

    private $taskService;
    private $projectRoleService;
    private $activityLogService;

    public function __construct() {
        parent::__construct();
        $this->taskService = new TaskService();
        $this->projectRoleService = new ProjectRoleService();
        $this->activityLogService = new ActivityLogService();
    }

    /* GET /sprints/:sprint_id/tasks and Get all tasks in sprint */
    public function getSprintTasks(): void {
        try {
            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $sprint_id = $this->getUrlParam('sprint_id');
            if (empty($sprint_id)) {
                $this->respondWithError(400, "Sprint ID is required");
            }

            $tasks = $this->taskService->getSprintTasks((int)$sprint_id);

            $result = $this->formatTasks($tasks);
            $this->respond(['success' => true, 'data' => $result], 200);

        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    /* GET /projects/:id/tasks and Get all tasks in project */
    public function getProjectTasks(): void {
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

            $status_filter    = $_GET['status']    ?? null;
            $sprint_filter    = isset($_GET['sprint_id']) ? (int)$_GET['sprint_id'] : null;
            $limit  = min(200, max(1, (int)($_GET['limit']  ?? 200)));
            $page   = max(1, (int)($_GET['page'] ?? 1));
            $offset = ($page - 1) * $limit;

            $tasks  = $this->taskService->getProjectTasksFiltered((int)$project_id, $status_filter, $sprint_filter, $limit, $offset);
            $total  = $this->taskService->countProjectTasks((int)$project_id, $status_filter, $sprint_filter);
            $result = $this->formatTasks($tasks);

            $this->respond([
                'success' => true,
                'data'    => $result,
                'meta'    => [
                    'total'  => $total,
                    'page'   => $page,
                    'limit'  => $limit,
                    'pages'  => (int)ceil($total / $limit),
                ],
            ], 200);

        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    /* GET /projects/:id/backlog and Get backlog tasks (no sprint) for a project */
    public function getProjectBacklogTasks(): void {
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

            $tasks = $this->taskService->getProjectBacklogTasks((int)$project_id);
            $result = $this->formatTasks($tasks);
            $this->respond(['success' => true, 'data' => $result], 200);

        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    /* Helper: format tasks array to response format */
    private function formatTasks(array $tasks): array {
        $result = [];
        foreach ($tasks as $task) {
            $result[] = [
                'task_id'             => $task->task_id,
                'sprint_id'           => $task->sprint_id,
                'project_id'          => $task->project_id,
                'task_name'           => $task->task_name,
                'description'         => $task->description,
                'assigned_to'         => $task->assigned_to ?: null,
                'assigned_user_name'  => $task->assigned_user_name ?? null,
                'status'              => $task->status->value,
                'created_at'          => $task->created_at?->format('Y-m-d H:i:s')
            ];
        }
        return $result;
    }

    /* GET /tasks/:id and Get task by ID */
    public function getTask(): void {
        try {
            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $task_id = $this->getUrlParam('id');
            if (empty($task_id)) {
                $this->respondWithError(400, "Task ID is required");
            }

            $task = $this->taskService->getTask((int)$task_id);
            if ($task === null) {
                $this->respondWithError(404, "Task not found");
            }

            // Check if user has access
            if (!$this->projectRoleService->hasAccess($user_id, $task->project_id)) {
                $this->respondWithError(403, "You don't have access to this task");
            }

            $result = [
                'task_id'            => $task->task_id,
                'sprint_id'          => $task->sprint_id,
                'project_id'         => $task->project_id,
                'task_name'          => $task->task_name,
                'description'        => $task->description,
                'assigned_to'        => $task->assigned_to ?: null,
                'assigned_user_name' => $task->assigned_user_name ?? null,
                'status'             => $task->status->value,
                'created_at'         => $task->created_at?->format('Y-m-d H:i:s')
            ];

            $this->respond(['success' => true, 'data' => $result], 200);

        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    /* POST /tasks and Create new task */
    public function createTask(): void {
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

            if (empty($data['project_id']) || empty($data['task_name'])) {
                $this->respondWithError(400, "Project ID and task name are required");
            }

            // Only editors/owners can create tasks
            if (!$this->projectRoleService->isEditor($user_id, (int)$data['project_id'])) {
                $this->respondWithError(403, "You need editor or owner role to create tasks");
            }

            $sprint_id = null;
            if (array_key_exists('sprint_id', $data) && $data['sprint_id'] !== '' && $data['sprint_id'] !== null && $data['sprint_id'] !== 'null') {
                $sprint_id = (int)$data['sprint_id'];
            }

            $task_id = $this->taskService->createTask(
                $sprint_id,
                (int)$data['project_id'],
                $data['task_name'],
                $data['description'] ?? '',
                $data['assigned_to'] ?? null,
                $user_id,
                $data['status'] ?? 'backlog'
            );

            $task = $this->taskService->getTask($task_id);

            $this->activityLogService->log($user_id, (int)$data['project_id'], 'task', 'created', $data['task_name']);

            $this->respond([
                'success' => true,
                'data' => [
                    'task_id'            => $task->task_id,
                    'sprint_id'          => $task->sprint_id,
                    'project_id'         => $task->project_id,
                    'task_name'          => $task->task_name,
                    'description'        => $task->description,
                    'assigned_to'        => $task->assigned_to ?: null,
                    'assigned_user_name' => $task->assigned_user_name ?? null,
                    'status'             => $task->status->value,
                    'created_at'         => $task->created_at?->format('Y-m-d H:i:s')
                ]
            ], 201);

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* PUT /tasks/:id and Update task */
    public function updateTask(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $task_id = $this->getUrlParam('id');
            if (empty($task_id)) {
                $this->respondWithError(400, "Task ID is required");
            }

            $existingTask = $this->taskService->getTask((int)$task_id);
            if ($existingTask === null) {
                $this->respondWithError(404, "Task not found");
            }

            // Only editors/owners can update tasks
            if (!$this->projectRoleService->isEditor($user_id, $existingTask->project_id)) {
                $this->respondWithError(403, "You need editor or owner role to update tasks");
            }

            $data = $this->getJsonData();

            if (empty($data['task_name'])) {
                $this->respondWithError(400, "Task name is required");
            }

            $sprint_id = null;
            if (array_key_exists('sprint_id', $data) && $data['sprint_id'] !== '' && $data['sprint_id'] !== null && $data['sprint_id'] !== 'null') {
                $sprint_id = (int)$data['sprint_id'];
            }

            $success = $this->taskService->updateTask(
                (int)$task_id,
                $data['task_name'],
                $data['description'] ?? '',
                $data['assigned_to'] ?? null,
                $user_id,
                $sprint_id,
                $data['status'] ?? ''
            );

            if ($success) {
                $task = $this->taskService->getTask((int)$task_id);
                $this->activityLogService->log($user_id, $task->project_id, 'task', 'updated', $task->task_name);
                $this->respond(['success' => true, 'data' => [
                    'task_id'     => $task->task_id,
                    'sprint_id'   => $task->sprint_id,
                    'project_id'  => $task->project_id,
                    'task_name'   => $task->task_name,
                    'description' => $task->description,
                    'assigned_to' => $task->assigned_to ?: null,
                    'status'      => $task->status->value,
                    'created_at'  => $task->created_at?->format('Y-m-d H:i:s')
                ]], 200);
            } else {
                $this->respondWithError(500, "Failed to update task");
            }

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* PATCH /tasks/:id/status and Update task status (for kanban board) */
    public function updateTaskStatus(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $task_id = $this->getUrlParam('id');
            if (empty($task_id)) {
                $this->respondWithError(400, "Task ID is required");
            }

            $data = $this->getJsonData();

            if (empty($data['status'])) {
                $this->respondWithError(400, "Status is required");
            }

            $statusTask = $this->taskService->getTask((int)$task_id);
            if ($statusTask === null) {
                $this->respondWithError(404, "Task not found");
            }

            // Only editors/owners can change task status
            if (!$this->projectRoleService->isEditor($user_id, $statusTask->project_id)) {
                $this->respondWithError(403, "You need editor or owner role to change task status");
            }

            $success = $this->taskService->updateTaskStatus((int)$task_id, $data['status'], $user_id);

            if ($success) {
                $this->activityLogService->log($user_id, $statusTask->project_id, 'task', 'status_changed', $statusTask->task_name, "Status changed to {$data['status']}");
                $this->respond(['success' => true, 'message' => 'Task status updated'], 200);
            } else {
                $this->respondWithError(500, "Failed to update task status");
            }

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* POST /tasks/:id/assign and Assign task to user */
    public function assignTask(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $task_id = $this->getUrlParam('id');
            if (empty($task_id)) {
                $this->respondWithError(400, "Task ID is required");
            }

            $assignTask = $this->taskService->getTask((int)$task_id);
            if ($assignTask === null) {
                $this->respondWithError(404, "Task not found");
            }

            // Only editors/owners can assign tasks
            if (!$this->projectRoleService->isEditor($user_id, $assignTask->project_id)) {
                $this->respondWithError(403, "You need editor or owner role to assign tasks");
            }

            $data = $this->getJsonData();

            if (empty($data['assigned_to'])) {
                $this->respondWithError(400, "Assigned user ID is required");
            }

            $success = $this->taskService->assignTask((int)$task_id, (int)$data['assigned_to'], $user_id);

            if ($success) {
                $this->activityLogService->log($user_id, $assignTask->project_id, 'task', 'assigned', $assignTask->task_name);
                $this->respond(['success' => true, 'message' => 'Task assigned'], 200);
            } else {
                $this->respondWithError(500, "Failed to assign task");
            }

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* DELETE /tasks/:id and Delete task */
    public function deleteTask(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                $this->respondWithError(405, "Method not allowed");
            }

            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $task_id = $this->getUrlParam('id');
            if (empty($task_id)) {
                $this->respondWithError(400, "Task ID is required");
            }

            $deleteTask = $this->taskService->getTask((int)$task_id);
            if ($deleteTask === null) {
                $this->respondWithError(404, "Task not found");
            }

            // Only editors/owners can delete tasks
            if (!$this->projectRoleService->isEditor($user_id, $deleteTask->project_id)) {
                $this->respondWithError(403, "You need editor or owner role to delete tasks");
            }

            $success = $this->taskService->deleteTask((int)$task_id, $user_id);

            if ($success) {
                $this->activityLogService->log($user_id, $deleteTask->project_id, 'task', 'deleted', $deleteTask->task_name);
                $this->respond(['success' => true, 'message' => 'Task deleted'], 200);
            } else {
                $this->respondWithError(500, "Failed to delete task");
            }

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }
}

?>
