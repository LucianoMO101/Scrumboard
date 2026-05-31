<?php

namespace Services;

use Repositories\ProjectRoleRepository;
use Models\ProjectRole;

class ProjectRoleService {

    private $projectRoleRepository;

    public function __construct() {
        $this->projectRoleRepository = new ProjectRoleRepository();
    }

    /* Check if user is owner of project (has owner role) */
    public function isOwner(int $user_id, int $project_id): bool {
        return $this->projectRoleRepository->hasProjectRoleType(
            $user_id,
            $project_id,
            ProjectRole::Owner
        );
    }

    /* Check if user is editor (owner or editor) */
    public function isEditor(int $user_id, int $project_id): bool {
        return $this->projectRoleRepository->hasProjectRoleType($user_id, $project_id, ProjectRole::Owner) ||
               $this->projectRoleRepository->hasProjectRoleType($user_id, $project_id, ProjectRole::Editor);
    }

    /* Check if user has access to project (any role) */
    public function hasAccess(int $user_id, int $project_id): bool {
        return $this->projectRoleRepository->hasProjectRole($user_id, $project_id);
    }

    /* Get user role in project */
    public function getUserRole(int $user_id, int $project_id): ?ProjectRole {
        $roleMapping = $this->projectRoleRepository->getUserProjectRole($user_id, $project_id);
        
        if ($roleMapping === null) {
            return null;
        }

        return $roleMapping->role;
    }

    /* Assign role to user in project */
    public function assignRole(int $user_id, int $project_id, ProjectRole $role, int $assigned_by_user_id): bool {
        // Only owners can assign roles
        if (!$this->isOwner($assigned_by_user_id, $project_id)) {
            throw new \Exception("Only project owners can assign roles");
        }

        $roleMapping = $this->projectRoleRepository->getUserProjectRole($user_id, $project_id);

        if ($roleMapping === null) {
            // Create new role mapping
            $newRole = new \Models\ProjectRoleMapping();
            $newRole->user_id = $user_id;
            $newRole->project_id = $project_id;
            $newRole->role = $role;

            return $this->projectRoleRepository->createProjectRole($newRole) !== null;
        } else {
            // Update existing role
            $roleMapping->role = $role;
            return $this->projectRoleRepository->updateProjectRole($roleMapping);
        }
    }

    /* Remove user from project */
    public function removeUserFromProject(int $user_id, int $project_id, int $removed_by_user_id): bool {
        // Only owners can remove users
        if (!$this->isOwner($removed_by_user_id, $project_id)) {
            throw new \Exception("Only project owners can remove users");
        }

        // Don't allow removing the last owner
        if ($this->isOwner($user_id, $project_id)) {
            $owners = $this->projectRoleRepository->getUsersByProjectRole($project_id, ProjectRole::Owner);
            
            if (count($owners) <= 1) {
                throw new \Exception("Cannot remove the last project owner");
            }
        }

        return $this->projectRoleRepository->deleteProjectRole($user_id, $project_id);
    }

    /* Get all users in project */
    public function getProjectUsers(int $project_id): array {
        return $this->projectRoleRepository->getProjectRoles($project_id);
    }
}

?>
