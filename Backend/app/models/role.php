<?php

namespace Models;

use DateTime;

class ProjectRoleMapping {
    public int $role_id;
    public int $user_id;
    public int $project_id;
    public ProjectRole $role; // Enum: owner, editor, viewer
    public ?DateTime $created_at;
    public ?string $user_name = null;
    public ?string $email = null;
}

?>