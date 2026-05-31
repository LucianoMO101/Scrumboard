<?php

namespace Services;

use Repositories\SprintRepository;
use Models\Sprint;
use Models\SprintStatus;

class SprintService {

    private $sprintRepository;
    private $projectRoleService;

    public function __construct() {
        $this->sprintRepository = new SprintRepository();
        $this->projectRoleService = new ProjectRoleService();
    }

    /* Create new sprint */
    public function createSprint(int $project_id, string $sprint_name, string $description, string $start_date, string $end_date, int $user_id): ?int {
        
        // Check authorization (only editors+)
        if (!$this->projectRoleService->isEditor($user_id, $project_id)) {
            throw new \Exception("Only project editors or owners can create sprints");
        }

        if (empty($sprint_name)) {
            throw new \Exception("Sprint name is required");
        }

        $sprint = new Sprint();
        $sprint->project_id = $project_id;
        $sprint->sprint_name = $sprint_name;
        $sprint->description = $description;
        $sprint->start_date = $start_date;
        $sprint->end_date = $end_date;
        $sprint->status = SprintStatus::Planned;

        return $this->sprintRepository->createSprint($sprint);
    }

    /* Get sprint by ID */
    public function getSprint(int $sprint_id): ?Sprint {
        return $this->sprintRepository->getSprintById($sprint_id);
    }

    /* Get all sprints for project */
    public function getProjectSprints(int $project_id): array {
        return $this->sprintRepository->getSprintsByProjectId($project_id);
    }

    /* Get active sprint */
    public function getActiveSprint(int $project_id): ?Sprint {
        return $this->sprintRepository->getActiveSprint($project_id);
    }

    /* Update sprint */
    public function updateSprint(int $sprint_id, string $sprint_name, string $description, string $start_date, string $end_date, int $user_id): bool {
        $sprint = $this->sprintRepository->getSprintById($sprint_id);

        if ($sprint === null) {
            throw new \Exception("Sprint not found");
        }

        // Check authorization
        if (!$this->projectRoleService->isEditor($user_id, $sprint->project_id)) {
            throw new \Exception("Only project editors or owners can update sprints");
        }

        $sprint->sprint_name = $sprint_name;
        $sprint->description = $description;
        $sprint->start_date = $start_date;
        $sprint->end_date = $end_date;

        return $this->sprintRepository->updateSprint($sprint);
    }

    /* Start sprint (set status to active) */
    public function startSprint(int $sprint_id, int $user_id): bool {
        $sprint = $this->sprintRepository->getSprintById($sprint_id);

        if ($sprint === null) {
            throw new \Exception("Sprint not found");
        }

        // Check authorization
        if (!$this->projectRoleService->isEditor($user_id, $sprint->project_id)) {
            throw new \Exception("Only project editors or owners can start sprints");
        }

        $sprint->status = SprintStatus::Active;
        return $this->sprintRepository->updateSprint($sprint);
    }

    /* Reopen sprint (set status back to active) */
    public function reopenSprint(int $sprint_id, int $user_id): bool {
        $sprint = $this->sprintRepository->getSprintById($sprint_id);

        if ($sprint === null) {
            throw new \Exception("Sprint not found");
        }

        // Check authorization
        if (!$this->projectRoleService->isEditor($user_id, $sprint->project_id)) {
            throw new \Exception("Only project editors or owners can reopen sprints");
        }

        $sprint->status = SprintStatus::Active;
        return $this->sprintRepository->updateSprint($sprint);
    }

    /* Complete sprint (set status to completed) */
    public function completeSprint(int $sprint_id, int $user_id): bool {
        $sprint = $this->sprintRepository->getSprintById($sprint_id);

        if ($sprint === null) {
            throw new \Exception("Sprint not found");
        }

        // Check authorization
        if (!$this->projectRoleService->isEditor($user_id, $sprint->project_id)) {
            throw new \Exception("Only project editors or owners can complete sprints");
        }

        $sprint->status = SprintStatus::Completed;
        return $this->sprintRepository->updateSprint($sprint);
    }

    /* Delete sprint */
    public function deleteSprint(int $sprint_id, int $user_id): bool {
        $sprint = $this->sprintRepository->getSprintById($sprint_id);

        if ($sprint === null) {
            throw new \Exception("Sprint not found");
        }

        // Check authorization
        if (!$this->projectRoleService->isEditor($user_id, $sprint->project_id)) {
            throw new \Exception("Only project editors or owners can delete sprints");
        }

        return $this->sprintRepository->deleteSprint($sprint_id);
    }
}

?>
