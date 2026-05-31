<?php

namespace Services;

use Repositories\TaskRepository;
use Models\Task;
use Models\TaskStatus;

class TaskService {

    private $taskRepository;
    private $projectRoleService;

    public function __construct() {
        $this->taskRepository = new TaskRepository();
        $this->projectRoleService = new ProjectRoleService();
    }

    /* Create new task */
    public function createTask(?int $sprint_id, int $project_id, string $task_name, string $description, ?int $assigned_to, int $user_id, string $status = 'backlog'): ?int {
        
        // Check authorization (only editors+)
        if (!$this->projectRoleService->isEditor($user_id, $project_id)) {
            throw new \Exception("Only project editors or owners can create tasks");
        }

        if (empty($task_name)) {
            throw new \Exception("Task name is required");
        }

        $task = new Task();
        $task->sprint_id = $sprint_id;
        $task->project_id = $project_id;
        $task->task_name = $task_name;
        $task->description = $description;
        $task->assigned_to = $assigned_to; // null means unassigned
        $task->status = TaskStatus::from($status);

        return $this->taskRepository->createTask($task);
    }

    /* Get task by ID */
    public function getTask(int $task_id): ?Task {
        return $this->taskRepository->getTaskById($task_id);
    }

    /* Get all tasks in sprint */
    public function getSprintTasks(int $sprint_id): array {
        return $this->taskRepository->getTasksBySprintId($sprint_id);
    }

    /* Get all tasks in project (with optional filter + pagination) */
    public function getProjectTasksFiltered(
        int $project_id,
        ?string $status_filter = null,
        ?int $sprint_id_filter = null,
        int $limit = 200,
        int $offset = 0
    ): array {
        return $this->taskRepository->getTasksByProjectId($project_id, $status_filter, $sprint_id_filter, $limit, $offset);
    }

    /* Count project tasks (for pagination meta) */
    public function countProjectTasks(int $project_id, ?string $status_filter = null, ?int $sprint_id_filter = null): int {
        return $this->taskRepository->countTasksByProjectId($project_id, $status_filter, $sprint_id_filter);
    }

    /* Get all tasks in project (legacy, no filters) */
    public function getProjectTasks(int $project_id): array {
        return $this->taskRepository->getTasksByProjectId($project_id);
    }

    /* Get backlog tasks for a project (no sprint assigned) */
    public function getProjectBacklogTasks(int $project_id): array {
        return $this->taskRepository->getProjectBacklogTasks($project_id);
    }

    /* Get tasks assigned to user */
    public function getUserTasks(int $user_id): array {
        return $this->taskRepository->getTasksByUserId($user_id);
    }

    /* Update task */
    public function updateTask(int $task_id, string $task_name, string $description, ?int $assigned_to, int $user_id, ?int $sprint_id = null, string $status = ''): bool {
        $task = $this->taskRepository->getTaskById($task_id);

        if ($task === null) {
            throw new \Exception("Task not found");
        }

        // Check authorization
        if (!$this->projectRoleService->isEditor($user_id, $task->project_id)) {
            throw new \Exception("Only project editors or owners can update tasks");
        }

        $task->task_name = $task_name;
        $task->description = $description;
        $task->assigned_to = $assigned_to ?? null;
        $task->sprint_id = $sprint_id;
        if ($status !== '') {
            $task->status = \Models\TaskStatus::from($status);
        }

        return $this->taskRepository->updateTask($task);
    }

    /* Update task status (for kanban board drag & drop) */
    public function updateTaskStatus(int $task_id, string $status, int $user_id): bool {
        $task = $this->taskRepository->getTaskById($task_id);

        if ($task === null) {
            throw new \Exception("Task not found");
        }

        // Check authorization
        if (!$this->projectRoleService->isEditor($user_id, $task->project_id)) {
            throw new \Exception("Only project editors or owners can update task status");
        }

        // Validate status
        try {
            $newStatus = TaskStatus::from($status);
        } catch (\ValueError $e) {
            throw new \Exception("Invalid task status: " . $status);
        }

        return $this->taskRepository->updateTaskStatus($task_id, $newStatus);
    }

    /* Assign task to user */
    public function assignTask(int $task_id, int $assigned_to, int $user_id): bool {
        $task = $this->taskRepository->getTaskById($task_id);

        if ($task === null) {
            throw new \Exception("Task not found");
        }

        // Check authorization
        if (!$this->projectRoleService->isEditor($user_id, $task->project_id)) {
            throw new \Exception("Only project editors or owners can assign tasks");
        }

        $task->assigned_to = $assigned_to;
        return $this->taskRepository->updateTask($task);
    }

    /* Unassign task */
    public function unassignTask(int $task_id, int $user_id): bool {
        $task = $this->taskRepository->getTaskById($task_id);

        if ($task === null) {
            throw new \Exception("Task not found");
        }

        // Check authorization
        if (!$this->projectRoleService->isEditor($user_id, $task->project_id)) {
            throw new \Exception("Only project editors or owners can unassign tasks");
        }

        $task->assigned_to = 0; // 0 means unassigned
        return $this->taskRepository->updateTask($task);
    }

    /* Delete task */
    public function deleteTask(int $task_id, int $user_id): bool {
        $task = $this->taskRepository->getTaskById($task_id);

        if ($task === null) {
            throw new \Exception("Task not found");
        }

        // Check authorization
        if (!$this->projectRoleService->isEditor($user_id, $task->project_id)) {
            throw new \Exception("Only project editors or owners can delete tasks");
        }

        return $this->taskRepository->deleteTask($task_id);
    }
}

?>
