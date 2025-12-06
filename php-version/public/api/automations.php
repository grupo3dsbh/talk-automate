<?php
/**
 * API de Automações
 * Rotas: GET, PUT, DELETE, POST /api/automations/:id/send
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/models/Automation.php';
require_once __DIR__ . '/../../src/models/Submission.php';
require_once __DIR__ . '/../../src/models/ChatwootConfig.php';
require_once __DIR__ . '/../../src/services/ChatwootService.php';
require_once __DIR__ . '/../../src/middleware/Auth.php';

// Autentica o usuário
$user = Auth::authenticate();

// Captura o método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Captura o path
$path = str_replace('/api/automations', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$parts = explode('/', trim($path, '/'));
$automationId = $parts[0] ?? '';
$action = $parts[1] ?? '';

try {
    $automationModel = new Automation();
    $submissionModel = new Submission();
    $configModel = new ChatwootConfig();

    // PUT /api/automations/:id - Atualiza uma automação
    if ($method === 'PUT' && !empty($automationId) && empty($action)) {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados inválidos']);
            exit;
        }

        // Verifica se a automação pertence ao usuário (via submission)
        $automation = $automationModel->findById($automationId);
        if (!$automation) {
            http_response_code(404);
            echo json_encode(['error' => 'Automação não encontrada']);
            exit;
        }

        $submission = $submissionModel->findById($automation['submission_id']);
        if (!$submission || $submission['user_id'] !== $user['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado']);
            exit;
        }

        $automation = $automationModel->update($automationId, $data);

        echo json_encode(['automation' => $automation]);
        exit;
    }

    // POST /api/automations/:id/send - Envia automação para o Chatwoot
    if ($method === 'POST' && !empty($automationId) && $action === 'send') {
        // Busca a automação
        $automation = $automationModel->findById($automationId);
        if (!$automation) {
            http_response_code(404);
            echo json_encode(['error' => 'Automação não encontrada']);
            exit;
        }

        // Verifica permissão
        $submission = $submissionModel->findById($automation['submission_id']);
        if (!$submission || $submission['user_id'] !== $user['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado']);
            exit;
        }

        // Busca a configuração do Chatwoot
        $config = $configModel->findById($submission['config_id']);
        if (!$config) {
            http_response_code(404);
            echo json_encode(['error' => 'Configuração do Chatwoot não encontrada']);
            exit;
        }

        try {
            // Envia para o Chatwoot
            $chatwootService = new ChatwootService([
                'chatwoot_url' => $config['chatwoot_url'],
                'account_id' => $config['account_id'],
                'api_access_token' => $config['api_access_token']
            ]);

            $rule = [
                'name' => $automation['name'],
                'description' => $automation['description'],
                'event_name' => $automation['event_name'],
                'conditions' => $automation['conditions'],
                'actions' => $automation['actions'],
                'active' => $automation['active']
            ];

            $result = $chatwootService->createAutomationRule($rule);

            // Atualiza o status da automação
            $automationModel->markAsSent($automationId, $result['id'] ?? null);

            echo json_encode([
                'success' => true,
                'automation' => $automationModel->findById($automationId),
                'chatwoot_response' => $result
            ]);
            exit;

        } catch (Exception $e) {
            $automationModel->markAsError($automationId);

            http_response_code(500);
            echo json_encode([
                'error' => 'Erro ao enviar para o Chatwoot',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    // DELETE /api/automations/:id - Deleta uma automação
    if ($method === 'DELETE' && !empty($automationId)) {
        $automation = $automationModel->findById($automationId);
        if (!$automation) {
            http_response_code(404);
            echo json_encode(['error' => 'Automação não encontrada']);
            exit;
        }

        $submission = $submissionModel->findById($automation['submission_id']);
        if (!$submission || $submission['user_id'] !== $user['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado']);
            exit;
        }

        $automationModel->delete($automationId);

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
