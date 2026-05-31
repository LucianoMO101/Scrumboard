<?php

namespace Services;

use Repositories\UserRepository;
use Models\User;

class UserService {

    private $userRepository;
    private $jwtService;
    private $teamService;

    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->jwtService = new JwtService();
        $this->teamService = new TeamService();
    }

    /* Register new user */
    public function register(string $firstname, string $lastname, string $email, string $password): ?array {
        
        // Validate input
        if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
            throw new \Exception("All fields are required");
        }

        // Check if user already exists
        if ($this->userRepository->getUserByEmail($email) !== null) {
            throw new \Exception("Email already registered");
        }

        // Create user object
        $user = new User();
        $user->firstname = $firstname;
        $user->lastname = $lastname;
        $user->email = $email;
        $user->password = $password; // Will be hashed in repository

        // Save to database
        $user_id = $this->userRepository->createUser($user);

        if ($user_id === null) {
            throw new \Exception("Failed to create user");
        }

        // Auto-create a personal default team for this user
        $team_id = $this->teamService->createTeam(
            $firstname . "'s Team",
            'Personal workspace for ' . $firstname . ' ' . $lastname,
            $user_id
        );

        if ($team_id !== null) {
            $this->userRepository->updateDefaultTeam($user_id, $team_id);
        }

        // Return user data
        return [
            'user_id'         => $user_id,
            'firstname'       => $firstname,
            'lastname'        => $lastname,
            'email'           => $email,
            'default_team_id' => $team_id,
            'message'         => 'User registered successfully'
        ];
    }

    /* Login user */
    public function login(string $email, string $password): ?array {
        
        // Validate input
        if (empty($email) || empty($password)) {
            throw new \Exception("Email and password are required");
        }

        // Get user from database
        $user = $this->userRepository->getUserByEmail($email);

        if ($user === null) {
            throw new \Exception("Invalid email or password");
        }

        // Verify password
        if (!password_verify($password, $user->password)) {
            throw new \Exception("Invalid email or password");
        }

        // Generate tokens
        $accessToken = $this->jwtService->createAccessToken($user->user_id, $user->email);
        $refreshToken = $this->jwtService->createRefreshToken();

        // Save refresh token to database
        $this->userRepository->updateRefreshToken($user->user_id, $refreshToken);

        // Return login data
        return [
            'user_id'         => $user->user_id,
            'firstname'       => $user->firstname,
            'lastname'        => $user->lastname,
            'email'           => $user->email,
            'default_team_id' => $user->default_team_id,
            'access_token'    => $accessToken,
            'refresh_token'   => $refreshToken,
            'token_type'      => 'Bearer',
            'expires_in'      => 3600
        ];
    }

    /* Get user by ID */
    public function getUserById(int $user_id): ?User {
        return $this->userRepository->getUserById($user_id);
    }

    /* Get user by email */
    public function getUserByEmail(string $email): ?User {
        return $this->userRepository->getUserByEmail($email);
    }

    /* Update user */
    public function updateUser(int $user_id, string $firstname, string $lastname, string $email): bool {
        $user = $this->userRepository->getUserById($user_id);

        if ($user === null) {
            throw new \Exception("User not found");
        }

        $user->firstname = $firstname;
        $user->lastname = $lastname;
        $user->email = $email;

        return $this->userRepository->updateUser($user);
    }

    /* Delete user */
    public function deleteUser(int $user_id): bool {
        return $this->userRepository->deleteUser($user_id);
    }

    /* Refresh access token using refresh token */
    public function refreshAccessToken(int $user_id, string $refreshToken): ?array {
        $user = $this->userRepository->getUserById($user_id);

        if ($user === null) {
            throw new \Exception("User not found");
        }

        // Verify refresh token matches what's in database
        if ($user->refresh_token !== $refreshToken) {
            throw new \Exception("Invalid refresh token");
        }

        // Generate new access token
        $newAccessToken = $this->jwtService->createAccessToken($user->user_id, $user->email);

        return [
            'access_token' => $newAccessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ];
    }
}

?>
