<?php
namespace Models;

use DateTime;

class User {
    public int $user_id;
    public string $firstname;
    public string $lastname;
    public string $email;
    public string $password;
    public ?string $refresh_token;
    public ?int $default_team_id;
    public ?DateTime $created_at;
    public ?DateTime $updated_at;
}

?>