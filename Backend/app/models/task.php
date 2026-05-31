<?php

namespace Models;

use DateTime;

class Task {
    public int $task_id;
    public ?int $sprint_id; // null = backlog (not assigned to a sprint)
    public int $project_id;
    public string $task_name;
    public string $description;
    public ?int $assigned_to; // null = unassigned
    public ?string $assigned_user_name = null; // joined from users table
    public TaskStatus $status; // Enum: backlog, todo, doing, done
    public ?DateTime $created_at;
    public ?DateTime $updated_at;
}