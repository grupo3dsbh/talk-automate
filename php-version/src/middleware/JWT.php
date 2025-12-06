<?php
/**
 * Classe JWT para autenticação
 * Implementação simples de JSON Web Tokens
 */

class JWT {
    /**
     * Gera um token JWT
     */
    public static function encode($payload) {
        $header = [
            'typ' => 'JWT',
            'alg' => JWT_ALGORITHM
        ];

        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRATION;

        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            "{$headerEncoded}.{$payloadEncoded}",
            JWT_SECRET,
            true
        );

        $signatureEncoded = self::base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    /**
     * Decodifica e valida um token JWT
     */
    public static function decode($token) {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new Exception('Token JWT inválido');
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        // Verifica a assinatura
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac(
            'sha256',
            "{$headerEncoded}.{$payloadEncoded}",
            JWT_SECRET,
            true
        );

        if (!hash_equals($signature, $expectedSignature)) {
            throw new Exception('Assinatura do token inválida');
        }

        // Decodifica o payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);

        if (!$payload) {
            throw new Exception('Payload do token inválido');
        }

        // Verifica expiração
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token expirado');
        }

        return $payload;
    }

    /**
     * Extrai o token do header Authorization
     */
    public static function getBearerToken() {
        $headers = self::getAuthorizationHeader();

        if (!empty($headers)) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Obtém o header Authorization
     */
    private static function getAuthorizationHeader() {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );

            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        return $headers;
    }

    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
