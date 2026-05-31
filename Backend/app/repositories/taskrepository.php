<?php

namespace Repositories;

use Models\Task;
use Models\TaskStatus;
use DateTime;

class TaskRepository extends Repository {

    /* Get task by ID */
    public function getTaskById(int $task_id): ?Task {
        $query = $this->connection->prepare("
            SELECT t.*, CONCAT(u.firstname, ' ', u.lastname) AS assigned_user_name
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.user_id
            WHERE t.task_id = ?
        ");
        $query->execute([$task_id]);
        
        if ($query->rowCount() === 0) {
            return null;
        }
        
        return $this->mapRowToTask($query->fetch());
    }

    /* Get all tasks in a sprint */
    public function getTasksBySprintId(int $sprint_id): array {
        $query = $this->connection->prepare("
            SELECT t.*, CONCAT(u.firstname, ' ', u.lastname) AS assigned_user_name
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.user_id
            WHERE t.sprint_id = ?
            ORDER BY t.status DESC, t.created_at ASC
        ");
        $query->execute([$sprint_id]);
        $tasks = [];
        
        while ($row = $query->fetch()) {
            $tasks[] = $this->mapRowToTask($row);
        }
        
        return $tasks;
    }

    /* Get all tasks in a project with optional filtering and pagination */
    public function getTasksByProjectId(
        int $project_id,
        ?string $status_filter = null,
        ?int $sprint_id_filter = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $where = ['t.project_id = ?'];
        $params = [$project_id];

        if (!empty($status_filter)) {
            $where[] = 't.status = ?';
            $params[] = $status_filter;
        }
        if ($sprint_id_filter !== null) {
            $where[] = 't.sprint_id = ?';
            $params[] = $sprint_id_filter;
        }

        $whereClause = implode(' AND ', $where);
        $stmt = $this->connection->prepare("
            SELECT t.*, CONCAT(u.firstname, ' ', u.lastname) AS assigned_user_name
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.user_id
            WHERE {$whereClause}
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?
        ");

        $i = 1;
        foreach ($params as $value) { $stmt->bindValue($i++, $value); }
        $stmt->bindValue($i++, $limit,  \PDO::PARAM_INT);
        $stmt->bindValue($i,   $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $tasks = [];
        while ($row = $stmt->fetch()) {
            $tasks[] = $this->mapRowToTask($row);
        }
        return $tasks;
    }

    /* Count tasks in a project with optional filters */
    public function countTasksByProjectId(
        int $project_id,
        ?string $status_filter = null,
        ?int $sprint_id_filter = null
    ): int {
        $where = ['project_id = ?'];
        $params = [$project_id];
        if (!empty($status_filter)) { $where[] = 'status = ?'; $params[] = $status_filter; }
        if ($sprint_id_filter !== null) { $where[] = 'sprint_id = ?'; $params[] = $sprint_id_filter; }
        $stmt = $this->connection->prepare(
            'SELECT COUNT(*) as total FROM tasks WHERE ' . implode(' AND ', $where)
        );
        $stmt->execute($params);
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    /* Get backlog tasks for a project (not assigned to any sprint) */
    public function getProjectBacklogTasks(int $project_id): array {
        $query = $this->connection->prepare("
            SELECT t.*, CONCAT(u.firstname, ' ', u.lastname) AS assigned_user_name
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.user_id
            WHERE t.project_id = ? AND t.sprint_id IS NULL
            ORDER BY t.created_at DESC
        ");
        $query->execute([$project_id]);
        $tasks = [];
        
        while ($row = $query->fetch()) {
            $tasks[] = $this->mapRowToTask($row);
        }
        
        return $tasks;
    }

    /* Get tasks assigned to a user */
    public function getTasksByUserId(int $user_id): array {
        $query = $this->connection->prepare("
            SELECT * FROM tasks 
            WHERE assigned_to = ?
            ORDER BY created_at DESC
        ");
        $query->execute([$user_id]);
        $tasks = [];
        
        while ($row = $query->fetch()) {
            $tasks[] = $this->mapRowToTask($row);
        }
        
        return $tasks;
    }

    /* Create new task */
    public function createTask(Task $task): ?int {
        $query = $this->connection->prepare("
            INSERT INTO tasks (sprint_id, project_id, task_name, description, assigned_to, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $query->execute([
            $task->sprint_id,
            $task->project_id,
            $task->task_name,
            $task->description,
            $task->assigned_to,
            $task->status->value
        ]);
        
        if ($result) {
            return (int)$this->connection->lastInsertId();
        }
        
        return null;
    }

    /* Update task */
    public function updateTask(Task $task): bool {
        $query = $this->connection->prepare("
            UPDATE tasks 
            SET sprint_id = ?, task_name = ?, description = ?, assigned_to = ?, status = ?
            WHERE task_id = ?
        ");
        
        return $query->execute([
            $task->sprint_id,
            $task->task_name,
            $task->description,
            $task->assigned_to,
            $task->status->value,
            $task->task_id
        ]);
    }

    /* Update task status */
    public function updateTaskStatus(int $task_id, TaskStatus $status): bool {
        $query = $this->connection->prepare("
            UPDATE tasks 
            SET status = ?
            WHERE task_id = ?
        ");
        
        return $query->execute([$status->value, $task_id]);
    }

    /* Delete task */
    public function deleteTask(int $task_id): bool {
        $query = $this->connection->prepare("DELETE FROM tasks WHERE task_id = ?");
        return $query->execute([$task_id]);
    }

    /* Map database row to Task object */
    private function mapRowToTask($row): Task {
        $task = new Task();
        $task->task_id = (int)$row['task_id'];
        $task->sprint_id = isset($row['sprint_id']) && $row['sprint_id'] !== null ? (int)$row['sprint_id'] : null;
        $task->project_id = (int)$row['project_id'];
        $task->task_name = $row['task_name'];
        $task->description = $row['description'];
        $task->assigned_to = isset($row['assigned_to']) && $row['assigned_to'] !== null ? (int)$row['assigned_to'] : null;
        $task->assigned_user_name = $row['assigned_user_name'] ?? null;
        $task->status = TaskStatus::from($row['status']);
        $task->created_at = $row['created_at'] ? new DateTime($row['created_at']) : null;
        $task->updated_at = $row['updated_at'] ? new DateTime($row['updated_at']) : null;
        
        return $task;
    }
}

?>
