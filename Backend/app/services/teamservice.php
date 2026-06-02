<?php

namespace Services;

use Repositories\TeamRepository;
use Models\Team;

class TeamService {

    private $teamRepository;

    public function __construct() {
        $this->teamRepository = new TeamRepository();
    }

    /* Create new team */
    public function createTeam(string $team_name, string $description, int $owner_id): ?int {
        if (empty($team_name)) {
            throw new \Exception("Team name is required");
        }
        $team = new Team();
        $team->team_name = $team_name;
        $team->description = $description;
        $team->owner_id = $owner_id;
        $team_id = $this->teamRepository->createTeam($team);
        if ($team_id !== null) {
            $this->teamRepository->addTeamMember($team_id, $owner_id, 'admin');
        }
        return $team_id;
    }

    /* Get team by ID */
    public function getTeam(int $team_id): ?Team {
        return $this->teamRepository->getTeamById($team_id);
    }

    /* Get teams for user */
    public function getUserTeams(int $user_id): array {
        return $this->teamRepository->getTeamsByUserId($user_id);
    }

    /* Update team */
    public function updateTeam(int $team_id, string $team_name, string $description, int $user_id): bool {
        $team = $this->teamRepository->getTeamById($team_id);
        if ($team === null) throw new \Exception("Team not found");
        if ($team->owner_id !== $user_id) throw new \Exception("Only team owner can update team");
        $team->team_name = $team_name;
        $team->description = $description;
        return $this->teamRepository->updateTeam($team);
    }

    /* Delete team */
    public function deleteTeam(int $team_id, int $user_id): bool {
        $team = $this->teamRepository->getTeamById($team_id);
        if ($team === null) throw new \Exception("Team not found");
        if ($team->owner_id !== $user_id) throw new \Exception("Only team owner can delete team");
        return $this->teamRepository->deleteTeam($team_id);
    }

    /* Check if user is team admin or owner (can create projects etc.) */
    public function isAdminOrOwner(int $team_id, int $user_id): bool {
        return $this->teamRepository->isTeamAdminOrOwner($team_id, $user_id);
    }

    /* Get teams with the requesting user's role included */
    public function getTeamsWithUserRole(int $user_id): array {
        return $this->teamRepository->getTeamsWithUserRole($user_id);
    }

    /* Check if user is member of team (includes owner) */
    public function isMember(int $team_id, int $user_id): bool {
        $team = $this->teamRepository->getTeamById($team_id);
        if ($team === null) return false;
        if ($team->owner_id === $user_id) return true;
        return $this->teamRepository->isTeamMember($team_id, $user_id);
    }

    /* Get team members (simple) */
    public function getTeamMembers(int $team_id): array {
        return $this->teamRepository->getTeamMembers($team_id);
    }

    /* Get team members with roles */
    public function getTeamMembersWithRoles(int $team_id): array {
        return $this->teamRepository->getTeamMembersWithRoles($team_id);
    }

    /* Add member to team (direct, by owner) */
    public function addTeamMember(int $team_id, int $user_id, int $added_by_user_id): bool {
        $team = $this->teamRepository->getTeamById($team_id);
        if ($team === null) throw new \Exception("Team not found");
        if ($team->owner_id !== $added_by_user_id) throw new \Exception("Only team owner can add members");
        if ($this->teamRepository->isTeamMember($team_id, $user_id)) throw new \Exception("User is already a team member");
        return $this->teamRepository->addTeamMember($team_id, $user_id, 'member');
    }

    /* Remove member from team */
    public function removeTeamMember(int $team_id, int $user_id, int $removed_by_user_id): bool {
        $team = $this->teamRepository->getTeamById($team_id);
        if ($team === null) throw new \Exception("Team not found");
        if ($team->owner_id !== $removed_by_user_id) throw new \Exception("Only team owner can remove members");
        return $this->teamRepository->removeTeamMember($team_id, $user_id);
    }

    /* Get user ID by email */
    public function getUserIdByEmail(string $email): ?int {
        return $this->teamRepository->getUserIdByEmail($email);
    }

    /* Invite user to team */
    public function inviteUser(int $team_id, int $invited_user_id, int $invited_by): void {
        $team = $this->teamRepository->getTeamById($team_id);
        if ($team === null) throw new \Exception("Team not found");
        if (!$this->isMember($team_id, $invited_by)) throw new \Exception("You are not a member of this team");
        if ($this->isMember($team_id, $invited_user_id)) throw new \Exception("User is already a member of this team");
        if ($this->teamRepository->hasPendingInvitation($team_id, $invited_user_id)) throw new \Exception("A pending invitation already exists for this user");
        $success = $this->teamRepository->createInvitation($team_id, $invited_user_id, $invited_by);
        if (!$success) throw new \Exception("Failed to create invitation");
    }

    /* Get pending invitations for a user */
    public function getPendingInvitations(int $user_id): array {
        return $this->teamRepository->getPendingInvitationsForUser($user_id);
    }

    /* Accept invitation */
    public function acceptInvitation(int $invitation_id, int $user_id): void {
        $invitation = $this->teamRepository->getInvitationById($invitation_id);
        if ($invitation === null) throw new \Exception("Invitation not found");
        if ((int)$invitation['invited_user_id'] !== $user_id) throw new \Exception("This invitation is not for you");
        if ($invitation['status'] !== 'pending') throw new \Exception("Invitation is no longer pending");
        $this->teamRepository->updateInvitationStatus($invitation_id, 'accepted');
        $this->teamRepository->addTeamMember((int)$invitation['team_id'], $user_id, 'member');
    }

    /* Decline invitation */
    public function declineInvitation(int $invitation_id, int $user_id): void {
        $invitation = $this->teamRepository->getInvitationById($invitation_id);
        if ($invitation === null) throw new \Exception("Invitation not found");
        if ((int)$invitation['invited_user_id'] !== $user_id) throw new \Exception("This invitation is not for you");
        if ($invitation['status'] !== 'pending') throw new \Exception("Invitation is no longer pending");
        $this->teamRepository->updateInvitationStatus($invitation_id, 'declined');
    }
}

?>
