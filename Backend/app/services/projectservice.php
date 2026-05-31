<?php

namespace Services;

use Repositories\ProjectRepository;
use Repositories\ProjectRoleRepository;
use Models\Project;
use Models\ProjectRoleMapping;
use Models\ProjectRole;

class ProjectService {

    private $projectRepository;
    private $projectRoleRepository;
    private $projectRoleService;

    public function __construct() {
        $this->projectRepository = new ProjectRepository();
        $this->projectRoleRepository = new ProjectRoleRepository();
        $this->projectRoleService = new ProjectRoleService();
    }

    /* Create new project */
    public function createProject(string $project_name, string $description, int $team_id, int $owner_id): ?int {
        
        if (empty($project_name)) {
            throw new \Exception("Project name is required");
        }

        // Only team admins / owners may create projects
        $teamService = new TeamService();
        if (!$teamService->isAdminOrOwner($team_id, $owner_id)) {
            throw new \Exception("Only team admins can create projects");
        }

        $project = new Project();
        $project->project_name = $project_name;
        $project->description = $description;
        $project->team_id = $team_id;
        $project->owner_id = $owner_id;

        $project_id = $this->projectRepository->createProject($project);

        if ($project_id === null) {
            throw new \Exception("Failed to create project");
        }

        // Assign owner role to creator
        $roleMapping = new ProjectRoleMapping();
        $roleMapping->user_id = $owner_id;
        $roleMapping->project_id = $project_id;
        $roleMapping->role = ProjectRole::Owner;
        
        $this->projectRoleRepository->createProjectRole($roleMapping);

        return $project_id;
    }

    /* Get project by ID */
    public function getProject(int $project_id): ?Project {
        return $this->projectRepository->getProjectById($project_id);
    }

    /* Get projects in team */
    public function getTeamProjects(int $team_id): array {
        return $this->projectRepository->getProjectsByTeamId($team_id);
    }

    /* Get user projects (all projects from teams user is actively a member of) */
    public function getUserProjects(int $user_id): array {
        return $this->projectRepository->getProjectsForUser($user_id);
    }

    /* Update project */
    public function updateProject(int $project_id, string $project_name, string $description, int $user_id): bool {
        $project = $this->projectRepository->getProjectById($project_id);

        if ($project === null) {
            throw new \Exception("Project not found");
        }

        // Only owners can update project
        if (!$this->projectRoleService->isOwner($user_id, $project_id)) {
            throw new \Exception("Only project owners can update project");
        }

        $project->project_name = $project_name;
        $project->description = $description;

        return $this->projectRepository->updateProject($project);
    }

    /* Delete project */
    public function deleteProject(int $project_id, int $user_id): bool {
        $project = $this->projectRepository->getProjectById($project_id);

        if ($project === null) {
            throw new \Exception("Project not found");
        }

        // Only owners can delete project
        if (!$this->projectRoleService->isOwner($user_id, $project_id)) {
            throw new \Exception("Only project owners can delete project");
        }

        return $this->projectRepository->deleteProject($project_id);
    }

    /* Get project members with roles */
    public function getProjectMembers(int $project_id): array {
        return $this->projectRoleRepository->getProjectRoles($project_id);
    }
}

?>
