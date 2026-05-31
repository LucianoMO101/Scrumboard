<?php

namespace Repositories;

use Models\User;
use DateTime;

class UserRepository extends Repository {

    /* Get user by ID */
    public function getUserById(int $user_id): ?User {
        $query = $this->connection->prepare("SELECT * FROM users WHERE user_id = ?");
        $query->execute([$user_id]);
        
        if ($query->rowCount() === 0) {
            return null;
        }
        
        return $this->mapRowToUser($query->fetch());
    }

    /* Get user by email */
    public function getUserByEmail(string $email): ?User {
        $query = $this->connection->prepare("SELECT * FROM users WHERE email = ?");
        $query->execute([$email]);
        
        if ($query->rowCount() === 0) {
            return null;
        }
        
        return $this->mapRowToUser($query->fetch());
    }

    /* Get all users */
    public function getAllUsers(): array {
        $query = $this->connection->query("SELECT * FROM users ORDER BY created_at DESC");
        $users = [];
        
        while ($row = $query->fetch()) {
            $users[] = $this->mapRowToUser($row);
        }
        
        return $users;
    }

    /* Create new user */
    public function createUser(User $user): ?int {
        $query = $this->connection->prepare("
            INSERT INTO users (firstname, lastname, email, password)
            VALUES (?, ?, ?, ?)
        ");
        
        $hashedPassword = password_hash($user->password, PASSWORD_BCRYPT);
        
        $result = $query->execute([
            $user->firstname,
            $user->lastname,
            $user->email,
            $hashedPassword
        ]);
        
        if ($result) {
            return (int)$this->connection->lastInsertId();
        }
        
        return null;
    }

    /* Update user */
    public function updateUser(User $user): bool {
        $query = $this->connection->prepare("
            UPDATE users 
            SET firstname = ?, lastname = ?, email = ?
            WHERE user_id = ?
        ");
        
        return $query->execute([
            $user->firstname,
            $user->lastname,
            $user->email,
            $user->user_id
        ]);
    }

    /* Update refresh token */
    public function updateRefreshToken(int $user_id, ?string $token): bool {
        $query = $this->connection->prepare("
            UPDATE users 
            SET refresh_token = ?
            WHERE user_id = ?
        ");
        
        return $query->execute([$token, $user_id]);
    }

    /* Delete user */
    public function deleteUser(int $user_id): bool {
        $query = $this->connection->prepare("DELETE FROM users WHERE user_id = ?");
        return $query->execute([$user_id]);
    }

    /* Update default team for user */
    public function updateDefaultTeam(int $user_id, int $team_id): bool {
        $query = $this->connection->prepare("
            UPDATE users SET default_team_id = ? WHERE user_id = ?
        ");
        return $query->execute([$team_id, $user_id]);
    }

    /* Map database row to User object */
    private function mapRowToUser($row): User {
        $user = new User();
        $user->user_id = (int)$row['user_id'];
        $user->firstname = $row['firstname'];
        $user->lastname = $row['lastname'];
        $user->email = $row['email'];
        $user->password = $row['password'];
        $user->refresh_token = $row['refresh_token'];
        $user->default_team_id = isset($row['default_team_id']) && $row['default_team_id'] !== null ? (int)$row['default_team_id'] : null;
        $user->created_at = $row['created_at'] ? new DateTime($row['created_at']) : null;
        $user->updated_at = $row['updated_at'] ? new DateTime($row['updated_at']) : null;
        
        return $user;
    }
}

?>

