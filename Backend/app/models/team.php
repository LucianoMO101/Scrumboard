<?php

namespace Models;

use DateTime;

class Team {
    public int $team_id;
    public string $team_name;
    public string $description;
    public int $owner_id; // User who created the team
    public ?DateTime $created_at;
    public ?DateTime $updated_at;
}

?>