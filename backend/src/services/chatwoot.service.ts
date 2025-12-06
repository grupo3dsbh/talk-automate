import axios, { AxiosInstance } from 'axios';
import { ChatwootAutomationRule, ChatwootAutomationResponse, ChatwootConfig } from '../types';

export class ChatwootService {
  private client: AxiosInstance;
  private config: ChatwootConfig;

  constructor(config: ChatwootConfig) {
    this.config = config;
    this.client = axios.create({
      baseURL: `${config.chatwootUrl}/api/v1`,
      headers: {
        'api_access_token': config.apiAccessToken,
        'Content-Type': 'application/json',
      },
    });
  }

  /**
   * Cria uma nova automation rule no Chatwoot
   */
  async createAutomationRule(rule: ChatwootAutomationRule): Promise<ChatwootAutomationResponse> {
    try {
      const response = await this.client.post<{ payload: ChatwootAutomationResponse }>(
        `/accounts/${this.config.accountId}/automation_rules`,
        rule
      );

      return response.data.payload;
    } catch (error: any) {
      console.error('Erro ao criar automation rule:', error.response?.data || error.message);
      throw new Error(`Falha ao criar automação no Chatwoot: ${error.response?.data?.message || error.message}`);
    }
  }

  /**
   * Lista todas as automation rules da conta
   */
  async listAutomationRules(): Promise<ChatwootAutomationResponse[]> {
    try {
      const response = await this.client.get<{ payload: ChatwootAutomationResponse[] }>(
        `/accounts/${this.config.accountId}/automation_rules`
      );

      return response.data.payload;
    } catch (error: any) {
      console.error('Erro ao listar automation rules:', error.response?.data || error.message);
      throw new Error(`Falha ao listar automações do Chatwoot: ${error.response?.data?.message || error.message}`);
    }
  }

  /**
   * Atualiza uma automation rule existente
   */
  async updateAutomationRule(ruleId: string, rule: Partial<ChatwootAutomationRule>): Promise<ChatwootAutomationResponse> {
    try {
      const response = await this.client.patch<{ payload: ChatwootAutomationResponse }>(
        `/accounts/${this.config.accountId}/automation_rules/${ruleId}`,
        rule
      );

      return response.data.payload;
    } catch (error: any) {
      console.error('Erro ao atualizar automation rule:', error.response?.data || error.message);
      throw new Error(`Falha ao atualizar automação no Chatwoot: ${error.response?.data?.message || error.message}`);
    }
  }

  /**
   * Deleta uma automation rule
   */
  async deleteAutomationRule(ruleId: string): Promise<void> {
    try {
      await this.client.delete(
        `/accounts/${this.config.accountId}/automation_rules/${ruleId}`
      );
    } catch (error: any) {
      console.error('Erro ao deletar automation rule:', error.response?.data || error.message);
      throw new Error(`Falha ao deletar automação no Chatwoot: ${error.response?.data?.message || error.message}`);
    }
  }

  /**
   * Testa a conexão com o Chatwoot
   */
  async testConnection(): Promise<boolean> {
    try {
      const response = await this.client.get(`/accounts/${this.config.accountId}`);
      return response.status === 200;
    } catch (error) {
      return false;
    }
  }
}
