import Groq from 'groq-sdk';
import OpenAI from 'openai';
import Anthropic from '@anthropic-ai/sdk';
import { config } from '../config';
import { AIProvider, AIAnalysisResult } from '../types';

const SYSTEM_PROMPT = `Você é um especialista em automação do Chatwoot. Sua tarefa é analisar um texto fornecido pelo usuário e gerar automações inteligentes para o Chatwoot.

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
- E muitas outras...

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
}`;

export class AIService {
  private groqClient?: Groq;
  private openaiClient?: OpenAI;
  private anthropicClient?: Anthropic;

  constructor() {
    if (config.ai.groq.apiKey) {
      this.groqClient = new Groq({ apiKey: config.ai.groq.apiKey });
    }
    if (config.ai.openai.apiKey) {
      this.openaiClient = new OpenAI({ apiKey: config.ai.openai.apiKey });
    }
    if (config.ai.anthropic.apiKey) {
      this.anthropicClient = new Anthropic({ apiKey: config.ai.anthropic.apiKey });
    }
  }

  async analyzeText(text: string, provider: AIProvider = config.ai.defaultProvider): Promise<AIAnalysisResult> {
    const userPrompt = `Analise o seguinte texto e gere automações do Chatwoot:\n\n${text}`;

    let response: string;

    switch (provider) {
      case 'groq':
        response = await this.analyzeWithGroq(userPrompt);
        break;
      case 'openai':
        response = await this.analyzeWithOpenAI(userPrompt);
        break;
      case 'anthropic':
        response = await this.analyzeWithAnthropic(userPrompt);
        break;
      default:
        throw new Error(`Provider ${provider} não suportado`);
    }

    // Parse da resposta JSON
    try {
      const result = JSON.parse(response) as AIAnalysisResult;
      return result;
    } catch (error) {
      console.error('Erro ao fazer parse da resposta da IA:', response);
      throw new Error('IA retornou resposta inválida');
    }
  }

  private async analyzeWithGroq(userPrompt: string): Promise<string> {
    if (!this.groqClient) {
      throw new Error('Groq não configurado. Configure GROQ_API_KEY');
    }

    const completion = await this.groqClient.chat.completions.create({
      model: 'mixtral-8x7b-32768',
      messages: [
        { role: 'system', content: SYSTEM_PROMPT },
        { role: 'user', content: userPrompt },
      ],
      temperature: 0.3,
      max_tokens: 4096,
    });

    return completion.choices[0]?.message?.content || '{}';
  }

  private async analyzeWithOpenAI(userPrompt: string): Promise<string> {
    if (!this.openaiClient) {
      throw new Error('OpenAI não configurado. Configure OPENAI_API_KEY');
    }

    const completion = await this.openaiClient.chat.completions.create({
      model: 'gpt-4-turbo-preview',
      messages: [
        { role: 'system', content: SYSTEM_PROMPT },
        { role: 'user', content: userPrompt },
      ],
      temperature: 0.3,
      max_tokens: 4096,
      response_format: { type: 'json_object' },
    });

    return completion.choices[0]?.message?.content || '{}';
  }

  private async analyzeWithAnthropic(userPrompt: string): Promise<string> {
    if (!this.anthropicClient) {
      throw new Error('Anthropic não configurado. Configure ANTHROPIC_API_KEY');
    }

    const message = await this.anthropicClient.messages.create({
      model: 'claude-3-sonnet-20240229',
      max_tokens: 4096,
      temperature: 0.3,
      system: SYSTEM_PROMPT,
      messages: [
        { role: 'user', content: userPrompt },
      ],
    });

    const content = message.content[0];
    if (content.type === 'text') {
      return content.text;
    }

    return '{}';
  }
}
