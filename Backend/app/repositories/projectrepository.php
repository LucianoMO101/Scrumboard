<?php

namespace Repositories;

use Models\Project;
use DateTime;

class ProjectRepository extends Repository {

    /* Get project by ID */
    public function getProjectById(int $project_id): ?Project {
        $query = $this->connection->prepare("SELECT * FROM projects WHERE project_id = ?");
        $query->execute([$project_id]);
        
        if ($query->rowCount() === 0) {
            return null;
        }
        
        return $this->mapRowToProject($query->fetch());
    }

    /* Get all projects in a team */
    public function getProjectsByTeamId(int $team_id): array {
        $query = $this->connection->prepare("
            SELECT * FROM projects 
            WHERE team_id = ?
            ORDER BY created_at DESC
        ");
        $query->execute([$team_id]);
        $projects = [];
        
        while ($row = $query->fetch()) {
            $projects[] = $this->mapRowToProject($row);
        }
        
        return $projects;
    }

    /* Get all projects visible to a user via team membership (active members only) */
    public function getProjectsForUser(int $user_id): array {
        $query = $this->connection->prepare("
            SELECT p.*, t.team_name
            FROM projects p
            INNER JOIN teams t ON p.team_id = t.team_id
            WHERE t.owner_id = ?
               OR p.team_id IN (
                   SELECT team_id FROM team_members WHERE user_id = ?
               )
            ORDER BY t.team_name ASC, p.created_at DESC
        ");
        $query->execute([$user_id, $user_id]);
        $projects = [];

        while ($row = $query->fetch()) {
            $project = $this->mapRowToProject($row);
            $project->team_name = $row['team_name'];
            $projects[] = $project;
        }

        return $projects;
    }

    /* Create new project */
    public function createProject(Project $project): ?int {
        $query = $this->connection->prepare("
            INSERT INTO projects (project_name, description, team_id, owner_id)
            VALUES (?, ?, ?, ?)
        ");
        
        $result = $query->execute([
            $project->project_name,
            $project->description,
            $project->team_id,
            $project->owner_id
        ]);
        
        if ($result) {
            return (int)$this->connection->lastInsertId();
        }
        
        return null;
    }

    /* Update project */
    public function updateProject(Project $project): bool {
        $query = $this->connection->prepare("
            UPDATE projects 
            SET project_name = ?, description = ?
            WHERE project_id = ?
        ");
        
        return $query->execute([
            $project->project_name,
            $project->description,
            $project->project_id
        ]);
    }

    /* Delete project */
    public function deleteProject(int $project_id): bool {
        $query = $this->connection->prepare("DELETE FROM projects WHERE project_id = ?");
        return $query->execute([$project_id]);
    }

    /* Map database row to Project object */
    private function mapRowToProject($row): Project {
        $project = new Project();
        $project->project_id = (int)$row['project_id'];
        $project->project_name = $row['project_name'];
        $project->description = $row['description'];
        $project->team_id = (int)$row['team_id'];
        $project->owner_id = (int)$row['owner_id'];
        $project->created_at = $row['created_at'] ? new DateTime($row['created_at']) : null;
        $project->updated_at = $row['updated_at'] ? new DateTime($row['updated_at']) : null;
        
        return $project;
    }
}

?>
