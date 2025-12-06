// Types for Chatwoot Automation Compiler

export interface ChatwootCondition {
  attribute_key: string;
  query_operator: 'equal_to' | 'not_equal_to' | 'contains' | 'does_not_contain';
  values: string[];
  filter_operator?: 'and' | 'or';
}

export interface ChatwootAction {
  action_name: string;
  action_params: string[];
}

export interface ChatwootAutomationRule {
  name: string;
  description: string;
  event_name: 'message_created' | 'conversation_created' | 'conversation_updated' | 'conversation_resolved';
  active: boolean;
  conditions: ChatwootCondition[];
  actions: ChatwootAction[];
}

export interface ChatwootAutomationResponse {
  id: number;
  account_id: number;
  name: string;
  description: string;
  event_name: string;
  conditions: ChatwootCondition[];
  actions: ChatwootAction[];
  created_on: number;
  active: boolean;
}

export interface GeneratedAutomation {
  name: string;
  description: string;
  eventName: string;
  conditions: ChatwootCondition[];
  actions: ChatwootAction[];
  reasoning: string; // Explicação da IA sobre por que criou esta automação
}

export interface AIAnalysisResult {
  automations: GeneratedAutomation[];
  summary: string; // Resumo da análise
}

export type AIProvider = 'groq' | 'openai' | 'anthropic';

export interface ChatwootConfig {
  chatwootUrl: string;
  accountId: string;
  apiAccessToken: string;
}

// Attribute keys disponíveis no Chatwoot
export const ATTRIBUTE_KEYS = [
  'message_type',
  'content',
  'email',
  'inbox_id',
  'status',
  'assignee_id',
  'team_id',
  'priority',
  'conversation_language',
  'phone_number',
  'contact_label',
  'labels'
] as const;

export type AttributeKey = typeof ATTRIBUTE_KEYS[number];

// Ações disponíveis no Chatwoot
export const ACTION_NAMES = [
  'assign_agent',
  'assign_team',
  'add_label',
  'remove_label',
  'add_contact_label',
  'remove_contact_label',
  'send_email_to_team',
  'send_email_transcript',
  'mute_conversation',
  'snooze_conversation',
  'resolve_conversation',
  'open_conversation',
  'send_webhook_event',
  'send_attachment',
  'send_message',
  'sleep_m',
  'add_private_note',
  'change_priority',
  'add_sla',
  'redistribute_to_online_agents'
] as const;

export type ActionName = typeof ACTION_NAMES[number];
