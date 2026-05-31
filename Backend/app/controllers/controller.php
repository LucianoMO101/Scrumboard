<?php

namespace Controllers;

use Exception;
use Services\JwtService;

class Controller {

    protected $jwtService;

    public function __construct() {
        $this->jwtService = new JwtService();
    }

    /* Get authenticated user ID from JWT token */
    protected function getAuthenticatedUserId(): ?int {
        $token = $this->jwtService->getTokenFromHeader();
        
        if ($token === null) {
            $this->respondWithError(401, "No token provided");
            return null;
        }

        $userId = $this->jwtService->getUserIdFromToken($token);
        
        if ($userId === null) {
            $this->respondWithError(401, "Invalid or expired token");
            return null;
        }

        return $userId;
    }

    /* Verify JWT token is valid */
    protected function verifyToken(): bool {
        $token = $this->jwtService->getTokenFromHeader();
        
        if ($token === null) {
            $this->respondWithError(401, "No token provided");
            return false;
        }

        if ($this->jwtService->verifyAccessToken($token) === null) {
            $this->respondWithError(401, "Invalid or expired token");
            return false;
        }

        return true;
    }

    /* Send success response */
    protected function respond($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
        exit;
    }

    /* Send error response  */
    protected function respondWithError(int $statusCode, string $message): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message], JSON_UNESCAPED_SLASHES);
        exit;
    }

    /* Get JSON data from request body */
    protected function getJsonData(): array {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if ($data === null) {
            $this->respondWithError(400, "Invalid JSON data");
        }

        return $data;
    }

    /* Get query parameter */
    protected function getQueryParam(string $name, $default = null) {
        return $_GET[$name] ?? $default;
    }

    /* Get URL parameter from path (Bramus Router passes regex captures as function args,
       so we parse the REQUEST_URI to find the first numeric segment as the resource ID) */
    protected function getUrlParam(string $name) {
        if (isset($_GET[$name])) return $_GET[$name];
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        foreach (array_filter(explode('/', $uri)) as $segment) {
            if (ctype_digit($segment)) return $segment;
        }
        return null;
    }

    /* Get second numeric segment from URL (e.g. /teams/:id/members/:user_id) */
    protected function getSecondUrlParam(): ?string {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $found = 0;
        foreach (array_filter(explode('/', $uri)) as $segment) {
            if (ctype_digit($segment)) {
                $found++;
                if ($found === 2) return $segment;
            }
        }
        return null;
    }
}
