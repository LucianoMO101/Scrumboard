<?php
namespace Models;

use DateTime;

class Project {
    public int $project_id;
    public string $project_name;
    public string $description;
    public int $owner_id; // User who created the project
    public int $team_id; // Team that owns this project
    public ?string $team_name = null; // Populated on joins
    public ?DateTime $created_at;
    public ?DateTime $updated_at;
}