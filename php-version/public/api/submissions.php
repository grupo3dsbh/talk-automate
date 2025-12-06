<?php
/**
 * API de Submissões
 * Rotas: GET, POST, DELETE /api/submissions
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/models/Submission.php';
require_once __DIR__ . '/../../src/models/Automation.php';
require_once __DIR__ . '/../../src/models/ChatwootConfig.php';
require_once __DIR__ . '/../../src/services/AIService.php';
require_once __DIR__ . '/../../src/middleware/Auth.php';

// Autentica o usuário
$user = Auth::authenticate();

// Captura o método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Captura o ID se fornecido na URL
$path = str_replace('/api/submissions', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$submissionId = trim($path, '/');

try {
    $submissionModel = new Submission();
    $automationModel = new Automation();

    // GET /api/submissions - Lista todas as submissões
    if ($method === 'GET' && empty($submissionId)) {
        $submissions = $submissionModel->findByUserId($user['user_id']);

        echo json_encode(['submissions' => $submissions]);
        exit;
    }

    // GET /api/submissions/:id - Busca uma submissão com suas automações
    if ($method === 'GET' && !empty($submissionId)) {
        $submission = $submissionModel->findById($submissionId);

        if (!$submission || $submission['user_id'] !== $user['user_id']) {
            http_response_code(404);
            echo json_encode(['error' => 'Submissão não encontrada']);
            exit;
        }

        // Busca as automações desta submissão
        $automations = $automationModel->findBySubmissionId($submissionId);
        $submission['automations'] = $automations;

        echo json_encode(['submission' => $submission]);
        exit;
    }

    // POST /api/submissions - Cria nova submissão e processa com IA
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['config_id'], $data['original_text'], $data['title'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados inválidos. Forneça config_id, original_text e title']);
            exit;
        }

        // Verifica se a configuração pertence ao usuário
        $configModel = new ChatwootConfig();
        $config = $configModel->findById($data['config_id']);

        if (!$config || $config['user_id'] !== $user['user_id']) {
            http_response_code(404);
            echo json_encode(['error' => 'Configuração não encontrada']);
            exit;
        }

        $aiProvider = $data['ai_provider'] ?? DEFAULT_AI_PROVIDER;

        // Cria a submissão
        $submission = $submissionModel->create($user['user_id'], $data['config_id'], [
            'original_text' => $data['original_text'],
            'title' => $data['title'],
            'ai_provider' => $aiProvider,
            'status' => 'PROCESSING'
        ]);

        // Processa o texto com a IA
        try {
            $aiService = new AIService();
            $result = $aiService->analyzeText($data['original_text'], $aiProvider);

            // Cria as automações geradas
            if (isset($result['automations']) && is_array($result['automations'])) {
                foreach ($result['automations'] as $automationData) {
                    $automationModel->create($submission['id'], [
                        'name' => $automationData['name'] ?? 'Automação',
                        'description' => $automationData['description'] ?? '',
                        'event_name' => $automationData['eventName'] ?? 'message_created',
                        'conditions' => $automationData['conditions'] ?? [],
                        'actions' => $automationData['actions'] ?? [],
                        'status' => 'DRAFT',
                        'active' => true
                    ]);
                }
            }

            // Atualiza status da submissão
            $submissionModel->updateStatus($submission['id'], 'COMPLETED');

        } catch (Exception $e) {
            // Marca como falha
            $submissionModel->updateStatus($submission['id'], 'FAILED');

            http_response_code(500);
            echo json_encode([
                'error' => 'Erro ao processar com IA',
                'message' => $e->getMessage()
            ]);
            exit;
        }

        // Retorna a submissão com as automações
        $submission = $submissionModel->findById($submission['id']);
        $automations = $automationModel->findBySubmissionId($submission['id']);
        $submission['automations'] = $automations;

        http_response_code(201);
        echo json_encode(['submission' => $submission]);
        exit;
    }

    // DELETE /api/submissions/:id - Deleta submissão
    if ($method === 'DELETE' && !empty($submissionId)) {
        $result = $submissionModel->delete($submissionId, $user['user_id']);

        if (!$result) {
            http_response_code(404);
            echo json_encode(['error' => 'Submissão não encontrada']);
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
