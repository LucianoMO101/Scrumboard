<?php

namespace Repositories;

use Models\ProjectRoleMapping;
use Models\ProjectRole;
use DateTime;

class ProjectRoleRepository extends Repository {

    /* Get role for user in project */
    public function getUserProjectRole(int $user_id, int $project_id): ?ProjectRoleMapping {
        $query = $this->connection->prepare("
            SELECT * FROM project_roles 
            WHERE user_id = ? AND project_id = ?
        ");
        $query->execute([$user_id, $project_id]);
        
        if ($query->rowCount() === 0) {
            return null;
        }
        
        return $this->mapRowToProjectRole($query->fetch());
    }

    /* Get all roles for a project (with user details) */
    public function getProjectRoles(int $project_id): array {
        $query = $this->connection->prepare("
            SELECT pr.*, CONCAT(u.firstname, ' ', u.lastname) AS user_name, u.email
            FROM project_roles pr
            INNER JOIN users u ON pr.user_id = u.user_id
            WHERE pr.project_id = ?
            ORDER BY pr.created_at DESC
        ");
        $query->execute([$project_id]);
        $roles = [];
        
        while ($row = $query->fetch()) {
            $roles[] = $this->mapRowToProjectRole($row);
        }
        
        return $roles;
    }

    /* Get all projects for a user with their roles */
    public function getUserProjectRoles(int $user_id): array {
        $query = $this->connection->prepare("
            SELECT * FROM project_roles 
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $query->execute([$user_id]);
        $roles = [];
        
        while ($row = $query->fetch()) {
            $roles[] = $this->mapRowToProjectRole($row);
        }
        
        return $roles;
    }

    /* Create new project role */
    public function createProjectRole(ProjectRoleMapping $roleMapping): ?int {
        $query = $this->connection->prepare("
            INSERT INTO project_roles (user_id, project_id, role)
            VALUES (?, ?, ?)
        ");
        
        $result = $query->execute([
            $roleMapping->user_id,
            $roleMapping->project_id,
            $roleMapping->role->value
        ]);
        
        if ($result) {
            return (int)$this->connection->lastInsertId();
        }
        
        return null;
    }

    /* Update project role */
    public function updateProjectRole(ProjectRoleMapping $roleMapping): bool {
        $query = $this->connection->prepare("
            UPDATE project_roles 
            SET role = ?
            WHERE user_id = ? AND project_id = ?
        ");
        
        return $query->execute([
            $roleMapping->role->value,
            $roleMapping->user_id,
            $roleMapping->project_id
        ]);
    }

    /* Delete project role (remove user from project) */
    public function deleteProjectRole(int $user_id, int $project_id): bool {
        $query = $this->connection->prepare("
            DELETE FROM project_roles 
            WHERE user_id = ? AND project_id = ?
        ");
        
        return $query->execute([$user_id, $project_id]);
    }

    /* Check if user has role in project */
    public function hasProjectRole(int $user_id, int $project_id): bool {
        $query = $this->connection->prepare("
            SELECT COUNT(*) as count FROM project_roles 
            WHERE user_id = ? AND project_id = ?
        ");
        $query->execute([$user_id, $project_id]);
        $result = $query->fetch();
        
        return $result['count'] > 0;
    }

    /* Check if user has specific role in project */
    public function hasProjectRoleType(int $user_id, int $project_id, ProjectRole $roleType): bool {
        $query = $this->connection->prepare("
            SELECT COUNT(*) as count FROM project_roles 
            WHERE user_id = ? AND project_id = ? AND role = ?
        ");
        $query->execute([$user_id, $project_id, $roleType->value]);
        $result = $query->fetch();
        
        return $result['count'] > 0;
    }

    /* Get all users for a project with a specific role */
    public function getUsersByProjectRole(int $project_id, ProjectRole $roleType): array {
        $query = $this->connection->prepare("
            SELECT u.* FROM users u
            INNER JOIN project_roles pr ON u.user_id = pr.user_id
            WHERE pr.project_id = ? AND pr.role = ?
            ORDER BY u.firstname ASC
        ");
        $query->execute([$project_id, $roleType->value]);
        $users = [];
        
        while ($row = $query->fetch()) {
            // Simplified user mapping
            $user = new \Models\User();
            $user->user_id = (int)$row['user_id'];
            $user->firstname = $row['firstname'];
            $user->lastname = $row['lastname'];
            $user->email = $row['email'];
            $users[] = $user;
        }
        
        return $users;
    }

    /* Map database row to ProjectRoleMapping object */
    private function mapRowToProjectRole($row): ProjectRoleMapping {
        $roleMapping = new ProjectRoleMapping();
        $roleMapping->role_id = (int)$row['role_id'];
        $roleMapping->user_id = (int)$row['user_id'];
        $roleMapping->project_id = (int)$row['project_id'];
        $roleMapping->role = ProjectRole::from($row['role']);
        $roleMapping->created_at = $row['created_at'] ? new DateTime($row['created_at']) : null;
        $roleMapping->user_name = $row['user_name'] ?? null;
        $roleMapping->email = $row['email'] ?? null;
        
        return $roleMapping;
    }
}

?>
