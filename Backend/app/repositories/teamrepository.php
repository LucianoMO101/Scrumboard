<?php

namespace Repositories;

use Models\Team;
use DateTime;

class TeamRepository extends Repository {

    public function __construct() {
        parent::__construct();
        $this->ensureTeamSchema();
    }

    private function ensureTeamSchema(): void {
        try {
            $columnQuery = $this->connection->query("SHOW COLUMNS FROM team_members LIKE 'role'");
            if ($columnQuery && $columnQuery->rowCount() === 0) {
                $this->connection->exec("ALTER TABLE team_members ADD COLUMN role ENUM('admin','member') NOT NULL DEFAULT 'member' AFTER user_id");
            }
        } catch (\PDOException $e) {
            // If the table does not exist yet, do not stop initialization here.
        }

        try {
            $tableQuery = $this->connection->query("SHOW TABLES LIKE 'team_invitations'");
            if ($tableQuery && $tableQuery->rowCount() === 0) {
                $this->connection->exec(
                    "CREATE TABLE IF NOT EXISTS `team_invitations` (" .
                    "`invitation_id` INT(11) NOT NULL AUTO_INCREMENT, " .
                    "`team_id` INT(11) NOT NULL, " .
                    "`invited_user_id` INT(11) NOT NULL, " .
                    "`invited_by` INT(11) NOT NULL, " .
                    "`status` ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending', " .
                    "`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, " .
                    "PRIMARY KEY (`invitation_id`), " .
                    "FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE, " .
                    "FOREIGN KEY (`invited_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE, " .
                    "FOREIGN KEY (`invited_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE, " .
                    "INDEX `idx_invited_user` (`invited_user_id`)" .
                    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                );
            }
        } catch (\PDOException $e) {
            // Ignore startup schema repair errors if the database is not yet initialized.
        }
    }

    /* Get team by ID */
    public function getTeamById(int $team_id): ?Team {
        $query = $this->connection->prepare("SELECT * FROM teams WHERE team_id = ?");
        $query->execute([$team_id]);
        
        if ($query->rowCount() === 0) {
            return null;
        }
        
        return $this->mapRowToTeam($query->fetch());
    }

    /* Get all teams for a user (teams owned or member of) */
    public function getTeamsByUserId(int $user_id): array {
        $query = $this->connection->prepare("
            SELECT DISTINCT t.* FROM teams t
            LEFT JOIN team_members tm ON t.team_id = tm.team_id
            WHERE t.owner_id = ? OR tm.user_id = ?
            ORDER BY t.created_at DESC
        ");
        $query->execute([$user_id, $user_id]);
        $teams = [];
        
        while ($row = $query->fetch()) {
            $teams[] = $this->mapRowToTeam($row);
        }
        
        return $teams;
    }

    /* Get all teams */
    public function getAllTeams(): array {
        $query = $this->connection->query("SELECT * FROM teams ORDER BY created_at DESC");
        $teams = [];
        
        while ($row = $query->fetch()) {
            $teams[] = $this->mapRowToTeam($row);
        }
        
        return $teams;
    }

    /* Create new team */
    public function createTeam(Team $team): ?int {
        $query = $this->connection->prepare("
            INSERT INTO teams (team_name, description, owner_id)
            VALUES (?, ?, ?)
        ");
        
        $result = $query->execute([
            $team->team_name,
            $team->description,
            $team->owner_id
        ]);
        
        if ($result) {
            return (int)$this->connection->lastInsertId();
        }
        
        return null;
    }

    /* Update team */
    public function updateTeam(Team $team): bool {
        $query = $this->connection->prepare("
            UPDATE teams 
            SET team_name = ?, description = ?
            WHERE team_id = ?
        ");
        
        return $query->execute([
            $team->team_name,
            $team->description,
            $team->team_id
        ]);
    }

    /* Delete team */
    public function deleteTeam(int $team_id): bool {
        $query = $this->connection->prepare("DELETE FROM teams WHERE team_id = ?");
        return $query->execute([$team_id]);
    }

    /* Add member to team with role */
    public function addTeamMember(int $team_id, int $user_id, string $role = 'member'): bool {
        $query = $this->connection->prepare("
            INSERT INTO team_members (team_id, user_id, role)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE role = VALUES(role)
        ");
        
        return $query->execute([$team_id, $user_id, $role]);
    }

    /* Remove member from team */
    public function removeTeamMember(int $team_id, int $user_id): bool {
        $query = $this->connection->prepare("
            DELETE FROM team_members 
            WHERE team_id = ? AND user_id = ?
        ");
        
        return $query->execute([$team_id, $user_id]);
    }

    /* Get all team members with their role */
    public function getTeamMembersWithRoles(int $team_id): array {
        $query = $this->connection->prepare("
            SELECT u.user_id, u.firstname, u.lastname, u.email, 'admin' AS role
            FROM teams t
            INNER JOIN users u ON t.owner_id = u.user_id
            WHERE t.team_id = ?
            UNION
            SELECT u.user_id, u.firstname, u.lastname, u.email, tm.role
            FROM users u
            INNER JOIN team_members tm ON u.user_id = tm.user_id
            WHERE tm.team_id = ? AND tm.user_id <> (SELECT owner_id FROM teams WHERE team_id = ?)
            ORDER BY role DESC, firstname ASC
        ");
        $query->execute([$team_id, $team_id, $team_id]);
        $members = [];

        while ($row = $query->fetch()) {
            $members[] = [
                'user_id'   => (int)$row['user_id'],
                'firstname' => $row['firstname'],
                'lastname'  => $row['lastname'],
                'email'     => $row['email'],
                'role'      => $row['role'],
            ];
        }

        return $members;
    }

    /* Get all team members */
    public function getTeamMembers(int $team_id): array {
        $query = $this->connection->prepare("
            SELECT u.* FROM users u
            INNER JOIN team_members tm ON u.user_id = tm.user_id
            WHERE tm.team_id = ?
            ORDER BY u.firstname ASC
        ");
        $query->execute([$team_id]);
        $members = [];
        
        while ($row = $query->fetch()) {
            // Simplified User mapping - you may need to import UserRepository
            $user = new \Models\User();
            $user->user_id = (int)$row['user_id'];
            $user->firstname = $row['firstname'];
            $user->lastname = $row['lastname'];
            $user->email = $row['email'];
            $members[] = $user;
        }
        
        return $members;
    }

    /* Check if user is team owner OR has admin role in team_members */
    public function isTeamAdminOrOwner(int $team_id, int $user_id): bool {
        // Check if owner
        $q = $this->connection->prepare("SELECT COUNT(*) as cnt FROM teams WHERE team_id = ? AND owner_id = ?");
        $q->execute([$team_id, $user_id]);
        if ((int)$q->fetch()['cnt'] > 0) return true;
        // Check if admin in team_members
        $q2 = $this->connection->prepare("SELECT COUNT(*) as cnt FROM team_members WHERE team_id = ? AND user_id = ? AND role = 'admin'");
        $q2->execute([$team_id, $user_id]);
        return (int)$q2->fetch()['cnt'] > 0;
    }

    /* Get teams with user's role included */
    public function getTeamsWithUserRole(int $user_id): array {
        $query = $this->connection->prepare("
            SELECT DISTINCT t.*,
                CASE
                    WHEN t.owner_id = :uid1 THEN 'admin'
                    ELSE COALESCE(tm.role, 'member')
                END AS user_role
            FROM teams t
            LEFT JOIN team_members tm ON t.team_id = tm.team_id AND tm.user_id = :uid2
            WHERE t.owner_id = :uid3 OR tm.user_id = :uid4
            ORDER BY t.created_at DESC
        ");
        $query->execute([':uid1' => $user_id, ':uid2' => $user_id, ':uid3' => $user_id, ':uid4' => $user_id]);
        $rows = [];
        while ($row = $query->fetch()) {
            $team = $this->mapRowToTeam($row);
            $rows[] = ['team' => $team, 'user_role' => $row['user_role']];
        }
        return $rows;
    }

    /* Check if user is member of team */
    public function isTeamMember(int $team_id, int $user_id): bool {
        $query = $this->connection->prepare("
            SELECT COUNT(*) as count FROM team_members 
            WHERE team_id = ? AND user_id = ?
        ");
        $query->execute([$team_id, $user_id]);
        $result = $query->fetch();
        
        return $result['count'] > 0;
    }

    /* Create invitation */
    public function createInvitation(int $team_id, int $invited_user_id, int $invited_by): bool {
        try {
            $query = $this->connection->prepare("
                INSERT INTO team_invitations (team_id, invited_user_id, invited_by)
                VALUES (?, ?, ?)
            ");
            if (!$query) {
                throw new \Exception("Failed to prepare invitation insert query: " . implode(", ", $this->connection->errorInfo()));
            }
            $result = $query->execute([$team_id, $invited_user_id, $invited_by]);
            if (!$result) {
                throw new \Exception("Failed to execute invitation insert: " . implode(", ", $query->errorInfo()));
            }
            return true;
        } catch (\Exception $e) {
            error_log("createInvitation error: " . $e->getMessage());
            return false;
        }
    }

    /* Get invitation by ID */
    public function getInvitationById(int $invitation_id): ?array {
        $query = $this->connection->prepare("SELECT * FROM team_invitations WHERE invitation_id = ?");
        $query->execute([$invitation_id]);
        $row = $query->fetch();
        return $row ?: null;
    }

    /* Get pending invitations for a user (with team info) */
    public function getPendingInvitationsForUser(int $user_id): array {
        $query = $this->connection->prepare("
            SELECT ti.invitation_id, ti.team_id, ti.invited_by, ti.status, ti.created_at,
                   t.team_name,
                   u.firstname AS inviter_firstname, u.lastname AS inviter_lastname
            FROM team_invitations ti
            INNER JOIN teams t ON ti.team_id = t.team_id
            INNER JOIN users u ON ti.invited_by = u.user_id
            WHERE ti.invited_user_id = ? AND ti.status = 'pending'
            ORDER BY ti.created_at DESC
        ");
        $query->execute([$user_id]);
        $rows = [];
        while ($row = $query->fetch()) {
            $rows[] = [
                'invitation_id'     => (int)$row['invitation_id'],
                'team_id'           => (int)$row['team_id'],
                'team_name'         => $row['team_name'],
                'invited_by'        => (int)$row['invited_by'],
                'inviter_name'      => $row['inviter_firstname'] . ' ' . $row['inviter_lastname'],
                'status'            => $row['status'],
                'created_at'        => $row['created_at'],
            ];
        }
        return $rows;
    }

    /* Check if pending invitation already exists */
    public function hasPendingInvitation(int $team_id, int $user_id): bool {
        $query = $this->connection->prepare("
            SELECT COUNT(*) as cnt FROM team_invitations
            WHERE team_id = ? AND invited_user_id = ? AND status = 'pending'
        ");
        $query->execute([$team_id, $user_id]);
        return (int)$query->fetch()['cnt'] > 0;
    }

    /* Update invitation status */
    public function updateInvitationStatus(int $invitation_id, string $status): bool {
        $query = $this->connection->prepare("
            UPDATE team_invitations SET status = ? WHERE invitation_id = ?
        ");
        return $query->execute([$status, $invitation_id]);
    }

    /* Get user_id by email */
    public function getUserIdByEmail(string $email): ?int {
        $query = $this->connection->prepare("SELECT user_id FROM users WHERE email = ?");
        $query->execute([$email]);
        $row = $query->fetch();
        return $row ? (int)$row['user_id'] : null;
    }

    /* Map database row to Team object */
    private function mapRowToTeam($row): Team {
        $team = new Team();
        $team->team_id = (int)$row['team_id'];
        $team->team_name = $row['team_name'];
        $team->description = $row['description'];
        $team->owner_id = (int)$row['owner_id'];
        $team->created_at = $row['created_at'] ? new DateTime($row['created_at']) : null;
        $team->updated_at = $row['updated_at'] ? new DateTime($row['updated_at']) : null;
        
        return $team;
    }
}

?>
