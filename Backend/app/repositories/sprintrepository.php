<?php

namespace Repositories;

use Models\Sprint;
use Models\SprintStatus;
use DateTime;

class SprintRepository extends Repository {

    /* Get sprint by ID */
    public function getSprintById(int $sprint_id): ?Sprint {
        $query = $this->connection->prepare("SELECT * FROM sprints WHERE sprint_id = ?");
        $query->execute([$sprint_id]);
        
        if ($query->rowCount() === 0) {
            return null;
        }
        
        return $this->mapRowToSprint($query->fetch());
    }

    /* Get all sprints for a project */
    public function getSprintsByProjectId(int $project_id): array {
        $query = $this->connection->prepare("
            SELECT * FROM sprints 
            WHERE project_id = ?
            ORDER BY start_date DESC
        ");
        $query->execute([$project_id]);
        $sprints = [];
        
        while ($row = $query->fetch()) {
            $sprints[] = $this->mapRowToSprint($row);
        }
        
        return $sprints;
    }

    /* Get active sprint for a project */
    public function getActiveSprint(int $project_id): ?Sprint {
        $query = $this->connection->prepare("
            SELECT * FROM sprints 
            WHERE project_id = ? AND status = 'active'
            LIMIT 1
        ");
        $query->execute([$project_id]);
        
        if ($query->rowCount() === 0) {
            return null;
        }
        
        return $this->mapRowToSprint($query->fetch());
    }

    /* Create new sprint */
    public function createSprint(Sprint $sprint): ?int {
        $query = $this->connection->prepare("
            INSERT INTO sprints (project_id, sprint_name, description, start_date, end_date, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $query->execute([
            $sprint->project_id,
            $sprint->sprint_name,
            $sprint->description,
            $sprint->start_date,
            $sprint->end_date,
            $sprint->status->value
        ]);
        
        if ($result) {
            return (int)$this->connection->lastInsertId();
        }
        
        return null;
    }

    /* Update sprint */
    public function updateSprint(Sprint $sprint): bool {
        $query = $this->connection->prepare("
            UPDATE sprints 
            SET sprint_name = ?, description = ?, start_date = ?, end_date = ?, status = ?
            WHERE sprint_id = ?
        ");
        
        return $query->execute([
            $sprint->sprint_name,
            $sprint->description,
            $sprint->start_date,
            $sprint->end_date,
            $sprint->status->value,
            $sprint->sprint_id
        ]);
    }

    /* Delete sprint */
    public function deleteSprint(int $sprint_id): bool {
        $query = $this->connection->prepare("DELETE FROM sprints WHERE sprint_id = ?");
        return $query->execute([$sprint_id]);
    }

    /* Map database row to Sprint object */
    private function mapRowToSprint($row): Sprint {
        $sprint = new Sprint();
        $sprint->sprint_id = (int)$row['sprint_id'];
        $sprint->project_id = (int)$row['project_id'];
        $sprint->sprint_name = $row['sprint_name'];
        $sprint->description = $row['description'];
        $sprint->start_date = $row['start_date'];
        $sprint->end_date = $row['end_date'];
        $sprint->status = SprintStatus::from($row['status']);
        $sprint->created_at = $row['created_at'] ? new DateTime($row['created_at']) : null;
        $sprint->updated_at = $row['updated_at'] ? new DateTime($row['updated_at']) : null;
        
        return $sprint;
    }
}

?>
