<?php
/**
 * Middleware de Autenticação
 * Valida JWT e carrega dados do usuário
 */

require_once __DIR__ . '/JWT.php';

class Auth {
    /**
     * Verifica se o usuário está autenticado
     * Retorna os dados do usuário ou lança exceção
     */
    public static function authenticate() {
        $token = JWT::getBearerToken();

        if (!$token) {
            http_response_code(401);
            die(json_encode(['error' => 'Token não fornecido']));
        }

        try {
            $payload = JWT::decode($token);

            if (!isset($payload['user_id'])) {
                throw new Exception('Token inválido');
            }

            return [
                'user_id' => $payload['user_id'],
                'email' => $payload['email'] ?? null,
                'name' => $payload['name'] ?? null
            ];
        } catch (Exception $e) {
            http_response_code(401);
            die(json_encode(['error' => $e->getMessage()]));
        }
    }

    /**
     * Tenta autenticar, mas não falha se não houver token
     * Útil para rotas opcionalmente autenticadas
     */
    public static function tryAuthenticate() {
        $token = JWT::getBearerToken();

        if (!$token) {
            return null;
        }

        try {
            $payload = JWT::decode($token);

            if (!isset($payload['user_id'])) {
                return null;
            }

            return [
                'user_id' => $payload['user_id'],
                'email' => $payload['email'] ?? null,
                'name' => $payload['name'] ?? null
            ];
        } catch (Exception $e) {
            return null;
        }
    }
}
