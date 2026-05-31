<?php

namespace Controllers;

use Exception;
use Services\UserService;

class UserController extends Controller {

    private $userService;

    public function __construct() {
        parent::__construct();
        $this->userService = new UserService();
    }

    /*POST /auth/register and Register new user */
    public function register(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->respondWithError(405, "Method not allowed");
            }

            $data = $this->getJsonData();

            // Validate required fields
            if (empty($data['firstname']) || empty($data['lastname']) || empty($data['email']) || empty($data['password'])) {
                $this->respondWithError(400, "First name, last name, email and password are required");
            }

            // Call service
            $result = $this->userService->register(
                $data['firstname'],
                $data['lastname'],
                $data['email'],
                $data['password']
            );

            $this->respond(['success' => true, 'data' => $result], 201);

        } catch (Exception $e) {
            $this->respondWithError(400, $e->getMessage());
        }
    }

    /* POST /auth/login andLogin user and get tokens */
    public function login(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->respondWithError(405, "Method not allowed");
            }

            $data = $this->getJsonData();

            // Validate required fields
            if (empty($data['email']) || empty($data['password'])) {
                $this->respondWithError(400, "Email and password are required");
            }

            // Call service
            $result = $this->userService->login($data['email'], $data['password']);

            $this->respond(['success' => true, 'data' => $result], 200);

        } catch (Exception $e) {
            $this->respondWithError(401, $e->getMessage());
        }
    }

    /* POST /auth/refresh and Refresh access token */
    public function refresh(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->respondWithError(405, "Method not allowed");
            }

            $data = $this->getJsonData();

            // Validate required fields
            if (empty($data['user_id']) || empty($data['refresh_token'])) {
                $this->respondWithError(400, "User ID and refresh token are required");
            }

            // Call service
            $result = $this->userService->refreshAccessToken($data['user_id'], $data['refresh_token']);

            $this->respond(['success' => true, 'data' => $result], 200);

        } catch (Exception $e) {
            $this->respondWithError(401, $e->getMessage());
        }
    }

    /* GET /users/:id Get user by ID (authenticated) */
    public function getUser(): void {
        try {
            if (!$this->verifyToken()) {
                return;
            }

            $user_id = $this->getUrlParam('id');
            if (empty($user_id)) {
                $this->respondWithError(400, "User ID is required");
            }

            $user = $this->userService->getUserById((int)$user_id);

            if ($user === null) {
                $this->respondWithError(404, "User not found");
            }

            // Don't return password
            $userData = [
                'user_id' => $user->user_id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'created_at' => $user->created_at
            ];

            $this->respond(['success' => true, 'data' => $userData], 200);

        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }
}




//     public function register() {
//         try {
//             $user = $this->createObjectFromPostedJson("Models\\User");
//             $userId = $this->userService->register($user);

//             if(!$userId) {
//                 throw new Exception("Failed to register");
//             }

//             $user->id = $userId;
//             return$this->respond($user);
//         }
//         catch (Exception $e) {
//             return $this->respondWithError(500, $e->getMessage());
//         }
//     }
// }
