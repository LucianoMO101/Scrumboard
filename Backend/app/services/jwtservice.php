<?php

namespace Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JwtService {

    private const SECRET_KEY = 'your_secret_key_change_this_in_production';
    private const ALGORITHM = 'HS256';
    private const ACCESS_TOKEN_EXPIRY = 3600; // 1 hour
    private const REFRESH_TOKEN_EXPIRY = 604800; // 7 days

    /* Create access token (JWT) */
    public function createAccessToken(int $user_id, string $email): string {
        $payload = [
            'iss' => $_ENV['APP_URL'] ?? 'http://localhost',
            'aud' => $_ENV['APP_URL'] ?? 'http://localhost',
            'iat' => time(),
            'exp' => time() + self::ACCESS_TOKEN_EXPIRY,
            'user_id' => $user_id,
            'email' => $email,
            'type' => 'access'
        ];

        return JWT::encode($payload, self::SECRET_KEY, self::ALGORITHM);
    }

    /* Create refresh token (longer lived, stored in DB) */
    public function createRefreshToken(): string {
        return bin2hex(random_bytes(32));
    }

    /* Verify access token */
    public function verifyAccessToken(string $token): ?object {
        try {
            $decoded = JWT::decode($token, new Key(self::SECRET_KEY, self::ALGORITHM));
            
            // Verify it's an access token
            if ($decoded->type !== 'access') {
                return null;
            }
            
            return $decoded;
        } catch (ExpiredException $e) {
            error_log("Token expired: " . $e->getMessage());
            return null;
        } catch (SignatureInvalidException $e) {
            error_log("Invalid token signature: " . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            error_log("Token verification failed: " . $e->getMessage());
            return null;
        }
    }

    /* Extract token from Authorization header */
    public function getTokenFromHeader(): ?string {
        $headers = apache_request_headers();
        
        if (!isset($headers['Authorization'])) {
            return null;
        }

        $authHeader = $headers['Authorization'];
        
        // Expected format: "Bearer {token}"
        if (preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /* Get user ID from token */
    public function getUserIdFromToken(string $token): ?int {
        $decoded = $this->verifyAccessToken($token);
        
        if ($decoded === null) {
            return null;
        }

        return $decoded->user_id ?? null;
    }
}

?>
