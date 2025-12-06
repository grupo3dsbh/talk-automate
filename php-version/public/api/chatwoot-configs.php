<?php
/**
 * API de Configurações do Chatwoot
 * Rotas: GET, POST, PUT, DELETE /api/chatwoot-configs
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/models/ChatwootConfig.php';
require_once __DIR__ . '/../../src/middleware/Auth.php';

// Autentica o usuário
$user = Auth::authenticate();

// Captura o método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Captura o ID se fornecido na URL
$path = str_replace('/api/chatwoot-configs', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$configId = trim($path, '/');

try {
    $configModel = new ChatwootConfig();

    // GET /api/chatwoot-configs - Lista todas as configurações
    if ($method === 'GET' && empty($configId)) {
        $configs = $configModel->findByUserId($user['user_id']);

        echo json_encode(['configs' => $configs]);
        exit;
    }

    // GET /api/chatwoot-configs/:id - Busca uma configuração
    if ($method === 'GET' && !empty($configId)) {
        $config = $configModel->findById($configId);

        if (!$config || $config['user_id'] !== $user['user_id']) {
            http_response_code(404);
            echo json_encode(['error' => 'Configuração não encontrada']);
            exit;
        }

        echo json_encode(['config' => $config]);
        exit;
    }

    // POST /api/chatwoot-configs - Cria nova configuração
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['chatwoot_url'], $data['account_id'], $data['api_access_token'], $data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados inválidos']);
            exit;
        }

        $config = $configModel->create($user['user_id'], $data);

        http_response_code(201);
        echo json_encode(['config' => $config]);
        exit;
    }

    // PUT /api/chatwoot-configs/:id - Atualiza configuração
    if ($method === 'PUT' && !empty($configId)) {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados inválidos']);
            exit;
        }

        $config = $configModel->update($configId, $user['user_id'], $data);

        if (!$config) {
            http_response_code(404);
            echo json_encode(['error' => 'Configuração não encontrada']);
            exit;
        }

        echo json_encode(['config' => $config]);
        exit;
    }

    // DELETE /api/chatwoot-configs/:id - Deleta configuração
    if ($method === 'DELETE' && !empty($configId)) {
        $result = $configModel->delete($configId, $user['user_id']);

        if (!$result) {
            http_response_code(404);
            echo json_encode(['error' => 'Configuração não encontrada']);
            exit;
        }

        echo json_encode(['success' => true]);
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
