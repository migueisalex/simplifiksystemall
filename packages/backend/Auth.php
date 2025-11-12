<?php
/**
 * Classe de Autenticação com JWT
 * 
 * Gerencia autenticação de usuários usando JSON Web Tokens
 */

class Auth {
    
    /**
     * Gera um JWT token
     */
    public static function generateToken($userId, $email, $role = 'user') {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $payload = [
            'iss' => APP_URL,
            'sub' => $userId,
            'email' => $email,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + JWT_EXPIRY
        ];
        
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            JWT_SECRET,
            true
        );
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    /**
     * Valida e decodifica um JWT token
     */
    public static function validateToken($token) {
        try {
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return false;
            }
            
            list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
            
            // Verifica a assinatura
            $signature = hash_hmac(
                'sha256',
                $headerEncoded . '.' . $payloadEncoded,
                JWT_SECRET,
                true
            );
            $expectedSignature = self::base64UrlEncode($signature);
            
            if ($signatureEncoded !== $expectedSignature) {
                return false;
            }
            
            // Decodifica o payload
            $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
            
            // Verifica expiração
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }
            
            return $payload;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtém o token do header Authorization
     */
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            
            if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Obtém o usuário autenticado
     */
    public static function getAuthenticatedUser() {
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            return null;
        }
        
        $payload = self::validateToken($token);
        
        if (!$payload) {
            return null;
        }
        
        return $payload;
    }
    
    /**
     * Verifica se o usuário está autenticado
     */
    public static function isAuthenticated() {
        return self::getAuthenticatedUser() !== null;
    }
    
    /**
     * Verifica se o usuário é admin
     */
    public static function isAdmin() {
        $user = self::getAuthenticatedUser();
        return $user && isset($user['role']) && $user['role'] === 'admin';
    }
    
    /**
     * Requer autenticação
     */
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autorizado']);
            exit;
        }
    }
    
    /**
     * Requer role de admin
     */
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado']);
            exit;
        }
    }
    
    /**
     * Codifica string em base64 URL-safe
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Decodifica string em base64 URL-safe
     */
    private static function base64UrlDecode($data) {
        $padding = 4 - (strlen($data) % 4);
        if ($padding !== 4) {
            $data .= str_repeat('=', $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

?>
