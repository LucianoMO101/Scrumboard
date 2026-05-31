<?php

namespace Controllers;

use Exception;
use Services\TeamService;

class TeamController extends Controller {

    private $teamService;

    public function __construct() {
        parent::__construct();
        $this->teamService = new TeamService();
    }

    /* GET /teams — Get all teams for authenticated user */
    public function getMyTeams(): void {
        try {
            if (!$this->verifyToken()) return;
            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $teamsWithRole = $this->teamService->getTeamsWithUserRole($user_id);
            $result = array_map(function($entry) {
                return array_merge($this->formatTeam($entry['team']), ['user_role' => $entry['user_role']]);
            }, $teamsWithRole);

            $this->respond(['success' => true, 'data' => $result], 200);
        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    /* GET /teams/:id — Get team detail with members */
    public function getTeam(): void {
        try {
            if (!$this->verifyToken()) return;
            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $team_id = $this->getUrlParam('id');
            if (empty($team_id)) $this->respondWithError(400, "Team ID is required");

            $team = $this->teamService->getTeam((int)$team_id);
            if ($team === null) $this->respondWithError(404, "Team not found");

            if (!$this->teamService->isMember((int)$team_id, $user_id)) {
                $this->respondWithError(403, "You are not a member of this team");
            }

            $members = $this->teamService->getTeamMembersWithRoles((int)$team_id);

            $this->respond([
                'success' => true,
                'data' => array_merge($this->formatTeam($team), ['members' => $members])
            ], 200);
        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    /* POST /teams — Create new team */
    public function createTeam(): void {
        try {
            if (!$this->verifyToken()) return;
            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $data = $this->getJsonData();
            if (empty($data['team_name'])) $this->respondWithError(400, "Team name is required");

            $team_id = $this->teamService->createTeam(
                $data['team_name'],
                $data['description'] ?? '',
                $user_id
            );

            $team = $this->teamService->getTeam($team_id);
            $this->respond(['success' => true, 'data' => $this->formatTeam($team)], 201);
        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* PUT /teams/:id — Update team */
    public function updateTeam(): void {
        try {
            if (!$this->verifyToken()) return;
            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $team_id = $this->getUrlParam('id');
            if (empty($team_id)) $this->respondWithError(400, "Team ID is required");

            $data = $this->getJsonData();
            if (empty($data['team_name'])) $this->respondWithError(400, "Team name is required");

            $this->teamService->updateTeam((int)$team_id, $data['team_name'], $data['description'] ?? '', $user_id);
            $team = $this->teamService->getTeam((int)$team_id);
            $this->respond(['success' => true, 'data' => $this->formatTeam($team)], 200);
        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* DELETE /teams/:id — Delete team */
    public function deleteTeam(): void {
        try {
            if (!$this->verifyToken()) return;
            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $team_id = $this->getUrlParam('id');
            if (empty($team_id)) $this->respondWithError(400, "Team ID is required");

            $this->teamService->deleteTeam((int)$team_id, $user_id);
            $this->respond(['success' => true, 'message' => 'Team deleted'], 200);
        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* POST /teams/:id/invite — Invite user by user_id or email */
    public function inviteToTeam(): void {
        try {
            if (!$this->verifyToken()) return;
            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $team_id = $this->getUrlParam('id');
            if (empty($team_id)) $this->respondWithError(400, "Team ID is required");

            $data = $this->getJsonData();

            $invited_user_id = null;
            if (!empty($data['user_id'])) {
                $invited_user_id = (int)$data['user_id'];
            } elseif (!empty($data['email'])) {
                $invited_user_id = $this->teamService->getUserIdByEmail($data['email']);
                if ($invited_user_id === null) $this->respondWithError(404, "No user found with that email");
            } else {
                $this->respondWithError(400, "user_id or email is required");
            }

            $this->teamService->inviteUser((int)$team_id, $invited_user_id, $user_id);
            $this->respond(['success' => true, 'message' => 'Invitation sent'], 201);
        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* DELETE /teams/:id/members/:user_id — Remove member */
    public function removeMember(): void {
        try {
            if (!$this->verifyToken()) return;
            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $team_id = $this->getUrlParam('id');
            $member_user_id = $this->getSecondUrlParam();
            if (empty($team_id) || empty($member_user_id)) $this->respondWithError(400, "Team ID and user ID are required");

            $this->teamService->removeTeamMember((int)$team_id, (int)$member_user_id, $user_id);
            $this->respond(['success' => true, 'message' => 'Member removed'], 200);
        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* GET /users/me/invitations — Get my pending invitations */
    public function getMyInvitations(): void {
        try {
            if (!$this->verifyToken()) return;
            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $invitations = $this->teamService->getPendingInvitations($user_id);
            $this->respond(['success' => true, 'data' => $invitations], 200);
        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    /* POST /invitations/:id/accept */
    public function acceptInvitation(): void {
        try {
            if (!$this->verifyToken()) return;
            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $invitation_id = $this->getUrlParam('id');
            if (empty($invitation_id)) $this->respondWithError(400, "Invitation ID is required");

            $this->teamService->acceptInvitation((int)$invitation_id, $user_id);
            $this->respond(['success' => true, 'message' => 'Invitation accepted'], 200);
        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* POST /invitations/:id/decline */
    public function declineInvitation(): void {
        try {
            if (!$this->verifyToken()) return;
            $user_id = $this->getAuthenticatedUserId();
            if ($user_id === null) return;

            $invitation_id = $this->getUrlParam('id');
            if (empty($invitation_id)) $this->respondWithError(400, "Invitation ID is required");

            $this->teamService->declineInvitation((int)$invitation_id, $user_id);
            $this->respond(['success' => true, 'message' => 'Invitation declined'], 200);
        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    private function formatTeam($team): array {
        return [
            'team_id'     => $team->team_id,
            'team_name'   => $team->team_name,
            'description' => $team->description,
            'owner_id'    => $team->owner_id,
            'created_at'  => $team->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
