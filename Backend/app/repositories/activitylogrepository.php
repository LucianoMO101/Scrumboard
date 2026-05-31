<?php

namespace Repositories;

class ActivityLogRepository extends Repository {

    /* Log an activity */
    public function log(
        int $user_id,
        ?int $project_id,
        string $entity_type,
        string $action,
        ?string $entity_name,
        ?string $details
    ): bool {
        $query = $this->connection->prepare("
            INSERT INTO activity_log (user_id, project_id, entity_type, action, entity_name, details)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $query->execute([$user_id, $project_id, $entity_type, $action, $entity_name, $details]);
    }

    /* Get activity logs for a project with optional filtering and pagination */
    public function getByProjectId(
        int $project_id,
        int $page,
        int $limit,
        ?string $action_filter,
        ?string $entity_type_filter
    ): array {
        $offset = ($page - 1) * $limit;

        $where = ['al.project_id = ?'];
        $params = [$project_id];

        if (!empty($action_filter)) {
            $where[] = 'al.action = ?';
            $params[] = $action_filter;
        }

        if (!empty($entity_type_filter)) {
            $where[] = 'al.entity_type = ?';
            $params[] = $entity_type_filter;
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $this->connection->prepare("
            SELECT
                al.log_id,
                al.user_id,
                al.project_id,
                al.entity_type,
                al.action,
                al.entity_name,
                al.details,
                al.created_at,
                u.firstname,
                u.lastname
            FROM activity_log al
            INNER JOIN users u ON al.user_id = u.user_id
            WHERE {$whereClause}
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ");

        // Bind WHERE params as strings, LIMIT/OFFSET as integers
        $i = 1;
        foreach ($params as $value) {
            $stmt->bindValue($i++, $value);
        }
        $stmt->bindValue($i++, $limit,  \PDO::PARAM_INT);
        $stmt->bindValue($i,   $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $logs = [];
        while ($row = $stmt->fetch()) {
            $logs[] = [
                'log_id'      => (int)$row['log_id'],
                'user_id'     => (int)$row['user_id'],
                'project_id'  => (int)$row['project_id'],
                'entity_type' => $row['entity_type'],
                'action'      => $row['action'],
                'entity_name' => $row['entity_name'],
                'details'     => $row['details'],
                'created_at'  => $row['created_at'],
                'user_name'   => $row['firstname'] . ' ' . $row['lastname'],
            ];
        }

        return $logs;
    }

    /* Count total entries for pagination meta */
    public function countByProjectId(
        int $project_id,
        ?string $action_filter,
        ?string $entity_type_filter
    ): int {
        $where = ['project_id = ?'];
        $params = [$project_id];

        if (!empty($action_filter)) {
            $where[] = 'action = ?';
            $params[] = $action_filter;
        }

        if (!empty($entity_type_filter)) {
            $where[] = 'entity_type = ?';
            $params[] = $entity_type_filter;
        }

        $whereClause = implode(' AND ', $where);

        $query = $this->connection->prepare("
            SELECT COUNT(*) as total FROM activity_log WHERE {$whereClause}
        ");

        $query->execute($params);
        $row = $query->fetch();

        return (int)($row['total'] ?? 0);
    }
}

?>
