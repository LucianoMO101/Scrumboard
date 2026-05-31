<?php

namespace Services;

use Repositories\ActivityLogRepository;

class ActivityLogService {

    private $activityLogRepository;

    public function __construct() {
        $this->activityLogRepository = new ActivityLogRepository();
    }

    /* Log an activity event */
    public function log(
        int $user_id,
        ?int $project_id,
        string $entity_type,
        string $action,
        ?string $entity_name = null,
        ?string $details = null
    ): void {
        // Non-blocking: ignore errors so they never interrupt the main operation
        try {
            $this->activityLogRepository->log($user_id, $project_id, $entity_type, $action, $entity_name, $details);
        } catch (\Exception $e) {
            // Silently ignore logging failures
        }
    }

    /* Get paginated activity log for a project */
    public function getProjectLog(
        int $project_id,
        int $page = 1,
        int $limit = 20,
        ?string $action_filter = null,
        ?string $entity_type_filter = null
    ): array {
        $logs = $this->activityLogRepository->getByProjectId(
            $project_id, $page, $limit, $action_filter, $entity_type_filter
        );

        $total = $this->activityLogRepository->countByProjectId(
            $project_id, $action_filter, $entity_type_filter
        );

        return [
            'data' => $logs,
            'meta' => [
                'total' => $total,
                'page'  => $page,
                'limit' => $limit,
                'pages' => $limit > 0 ? (int)ceil($total / $limit) : 1,
            ],
        ];
    }
}

?>
