<?php
/**
 * Serviço de Integração com IAs
 * Suporta: Groq, OpenAI, Anthropic Claude
 */

class AIService {
    private const SYSTEM_PROMPT = <<<'PROMPT'
Você é um especialista em automação do Chatwoot. Sua tarefa é analisar um texto fornecido pelo usuário e gerar automações inteligentes para o Chatwoot.

O texto geralmente contém:
- Informações de horários de funcionamento
- Endereços
- Preços e valores de serviços
- Informações sobre produtos/serviços
- FAQ (Perguntas Frequentes)

Você deve criar automações que:
1. Detectem palavras-chave relacionadas aos tópicos (horário, endereço, preço, etc.)
2. Respondam automaticamente com as informações corretas
3. Sejam acionadas quando uma MENSAGEM RECEBIDA (incoming message) contém certas palavras-chave

Estrutura das automações do Chatwoot:

**Condições disponíveis:**
- message_type: tipo da mensagem (incoming ou outgoing)
- content: conteúdo da mensagem
- Operadores: equal_to, not_equal_to, contains, does_not_contain

**Ações disponíveis:**
- send_message: enviar uma mensagem de resposta
- add_label: adicionar etiqueta à conversa
- assign_agent: atribuir a um agente
- assign_team: atribuir a uma equipe

Para cada automação, você deve retornar um objeto JSON com:
- name: nome descritivo da automação
- description: descrição do que ela faz
- eventName: "message_created" (para detectar novas mensagens)
- conditions: array de condições (ex: mensagem incoming contém "horário")
- actions: array de ações (ex: enviar mensagem com horários)
- reasoning: explicação de por que você criou esta automação

IMPORTANTE:
- Crie automações específicas e úteis
- Use palavras-chave variadas (sinônimos, variações)
- As respostas devem ser claras e diretas
- Sempre use message_type = "incoming" para detectar mensagens de clientes
- Use o operador "contains" para palavras-chave
- Máximo de 10 automações por análise
- Priorize as informações mais importantes

Retorne APENAS um JSON válido no seguinte formato:
{
  "automations": [
    {
      "name": "string",
      "description": "string",
      "eventName": "message_created",
      "conditions": [
        {
          "attribute_key": "message_type",
          "query_operator": "equal_to",
          "values": ["incoming"]
        },
        {
          "attribute_key": "content",
          "query_operator": "contains",
          "values": ["palavra-chave"],
          "filter_operator": "and"
        }
      ],
      "actions": [
        {
          "action_name": "send_message",
          "action_params": ["Mensagem de resposta aqui"]
        }
      ],
      "reasoning": "Explicação aqui"
    }
  ],
  "summary": "Resumo geral da análise"
}
PROMPT;

    /**
     * Analisa o texto e gera automações
     */
    public function analyzeText($text, $provider = null) {
        $provider = $provider ?: DEFAULT_AI_PROVIDER;

        switch ($provider) {
            case 'groq':
                return $this->analyzeWithGroq($text);
            case 'openai':
                return $this->analyzeWithOpenAI($text);
            case 'anthropic':
                return $this->analyzeWithAnthropic($text);
            default:
                throw new Exception("Provider '$provider' não suportado");
        }
    }

    /**
     * Análise com Groq
     */
    private function analyzeWithGroq($text) {
        if (empty(GROQ_API_KEY)) {
            throw new Exception('Groq API Key não configurada');
        }

        $url = 'https://api.groq.com/openai/v1/chat/completions';

        $data = [
            'model' => 'mixtral-8x7b-32768',
            'messages' => [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ['role' => 'user', 'content' => "Analise o seguinte texto e gere automações do Chatwoot:\n\n{$text}"]
            ],
            'temperature' => 0.3,
            'max_tokens' => 4096
        ];

        return $this->makeRequest($url, $data, GROQ_API_KEY);
    }

    /**
     * Análise com OpenAI
     */
    private function analyzeWithOpenAI($text) {
        if (empty(OPENAI_API_KEY)) {
            throw new Exception('OpenAI API Key não configurada');
        }

        $url = 'https://api.openai.com/v1/chat/completions';

        $data = [
            'model' => 'gpt-4-turbo-preview',
            'messages' => [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ['role' => 'user', 'content' => "Analise o seguinte texto e gere automações do Chatwoot:\n\n{$text}"]
            ],
            'temperature' => 0.3,
            'max_tokens' => 4096,
            'response_format' => ['type' => 'json_object']
        ];

        return $this->makeRequest($url, $data, OPENAI_API_KEY);
    }

    /**
     * Análise com Anthropic Claude
     */
    private function analyzeWithAnthropic($text) {
        if (empty(ANTHROPIC_API_KEY)) {
            throw new Exception('Anthropic API Key não configurada');
        }

        $url = 'https://api.anthropic.com/v1/messages';

        $data = [
            'model' => 'claude-3-sonnet-20240229',
            'max_tokens' => 4096,
            'temperature' => 0.3,
            'system' => self::SYSTEM_PROMPT,
            'messages' => [
                ['role' => 'user', 'content' => "Analise o seguinte texto e gere automações do Chatwoot:\n\n{$text}"]
            ]
        ];

        return $this->makeRequestAnthropic($url, $data, ANTHROPIC_API_KEY);
    }

    /**
     * Faz requisição HTTP para APIs (Groq e OpenAI)
     */
    private function makeRequest($url, $data, $apiKey) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('Erro cURL: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Erro da API (HTTP {$httpCode}): {$response}");
        }

        $result = json_decode($response, true);

        if (!$result || !isset($result['choices'][0]['message']['content'])) {
            throw new Exception('Resposta inválida da IA');
        }

        $content = $result['choices'][0]['message']['content'];
        $parsedContent = json_decode($content, true);

        if (!$parsedContent) {
            throw new Exception('IA retornou JSON inválido');
        }

        return $parsedContent;
    }

    /**
     * Faz requisição HTTP para Anthropic (formato diferente)
     */
    private function makeRequestAnthropic($url, $data, $apiKey) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('Erro cURL: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Erro da API Anthropic (HTTP {$httpCode}): {$response}");
        }

        $result = json_decode($response, true);

        if (!$result || !isset($result['content'][0]['text'])) {
            throw new Exception('Resposta inválida do Claude');
        }

        $content = $result['content'][0]['text'];
        $parsedContent = json_decode($content, true);

        if (!$parsedContent) {
            throw new Exception('Claude retornou JSON inválido');
        }

        return $parsedContent;
    }
}
