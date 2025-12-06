<?php
/**
 * API de Autenticação
 * Rotas: POST /api/auth/register, POST /api/auth/login
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/models/User.php';
require_once __DIR__ . '/../../src/middleware/JWT.php';

// Captura o método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Captura o path após /api/auth/
$path = str_replace('/api/auth', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = trim($path, '/');

try {
    $userModel = new User();

    // Rota: POST /api/auth/register
    if ($method === 'POST' && $path === 'register') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['email'], $data['password'], $data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados inválidos. Forneça email, password e name']);
            exit;
        }

        // Validações básicas
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email inválido']);
            exit;
        }

        if (strlen($data['password']) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'Senha deve ter no mínimo 6 caracteres']);
            exit;
        }

        $user = $userModel->create($data['email'], $data['password'], $data['name']);

        // Gera token JWT
        $token = JWT::encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name']
        ]);

        http_response_code(201);
        echo json_encode([
            'user' => $user,
            'token' => $token
        ]);
        exit;
    }

    // Rota: POST /api/auth/login
    if ($method === 'POST' && $path === 'login') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['email'], $data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Forneça email e password']);
            exit;
        }

        $user = $userModel->verifyCredentials($data['email'], $data['password']);

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Credenciais inválidas']);
            exit;
        }

        // Gera token JWT
        $token = JWT::encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name']
        ]);

        http_response_code(200);
        echo json_encode([
            'user' => $user,
            'token' => $token
        ]);
        exit;
    }

    // Rota não encontrada
    http_response_code(404);
    echo json_encode(['error' => 'Rota não encontrada']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno',
        'message' => APP_DEBUG ? $e->getMessage() : 'Internal server error'
    ]);
}
