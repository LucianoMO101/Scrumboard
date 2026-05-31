<?php
namespace Models;

use DateTime;

class Sprint {
    public int $sprint_id;
    public int $project_id;
    public string $sprint_name;
    public string $description;
    public string $start_date;
    public string $end_date;
    public SprintStatus $status; // Enum: planned, active, completed
    public ?DateTime $created_at;
    public ?DateTime $updated_at;
}