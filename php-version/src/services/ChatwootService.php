<?php
/**
 * Serviço de Integração com a API do Chatwoot
 */

class ChatwootService {
    private $chatwootUrl;
    private $accountId;
    private $apiAccessToken;

    public function __construct($config) {
        $this->chatwootUrl = rtrim($config['chatwoot_url'], '/');
        $this->accountId = $config['account_id'];
        $this->apiAccessToken = $config['api_access_token'];
    }

    /**
     * Cria uma nova automation rule no Chatwoot
     */
    public function createAutomationRule($rule) {
        $url = "{$this->chatwootUrl}/api/v1/accounts/{$this->accountId}/automation_rules";

        $data = [
            'name' => $rule['name'],
            'description' => $rule['description'],
            'event_name' => $rule['event_name'],
            'active' => $rule['active'] ?? true,
            'conditions' => $rule['conditions'],
            'actions' => $rule['actions']
        ];

        return $this->makeRequest('POST', $url, $data);
    }

    /**
     * Lista todas as automation rules
     */
    public function listAutomationRules() {
        $url = "{$this->chatwootUrl}/api/v1/accounts/{$this->accountId}/automation_rules";
        return $this->makeRequest('GET', $url);
    }

    /**
     * Atualiza uma automation rule
     */
    public function updateAutomationRule($ruleId, $rule) {
        $url = "{$this->chatwootUrl}/api/v1/accounts/{$this->accountId}/automation_rules/{$ruleId}";
        return $this->makeRequest('PATCH', $url, $rule);
    }

    /**
     * Deleta uma automation rule
     */
    public function deleteAutomationRule($ruleId) {
        $url = "{$this->chatwootUrl}/api/v1/accounts/{$this->accountId}/automation_rules/{$ruleId}";
        return $this->makeRequest('DELETE', $url);
    }

    /**
     * Testa a conexão com o Chatwoot
     */
    public function testConnection() {
        try {
            $url = "{$this->chatwootUrl}/api/v1/accounts/{$this->accountId}";
            $this->makeRequest('GET', $url);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Faz requisição HTTP para API do Chatwoot
     */
    private function makeRequest($method, $url, $data = null) {
        $ch = curl_init();

        $headers = [
            'Content-Type: application/json',
            'api_access_token: ' . $this->apiAccessToken
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;

            case 'PATCH':
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;

            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;

            case 'GET':
            default:
                // GET é o padrão
                break;
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("Erro cURL: {$error}");
        }

        curl_close($ch);

        if ($httpCode >= 400) {
            $errorMsg = "Erro na API do Chatwoot (HTTP {$httpCode})";
            if ($response) {
                $errorData = json_decode($response, true);
                if ($errorData && isset($errorData['message'])) {
                    $errorMsg .= ": " . $errorData['message'];
                }
            }
            throw new Exception($errorMsg);
        }

        if ($method === 'DELETE' && $httpCode === 200) {
            return ['success' => true];
        }

        $result = json_decode($response, true);

        // A resposta do Chatwoot vem em { payload: {...} }
        if (isset($result['payload'])) {
            return $result['payload'];
        }

        return $result;
    }
}
