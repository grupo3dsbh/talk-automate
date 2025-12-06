export interface User {
  id: string;
  email: string;
  name: string;
  createdAt: string;
}

export interface ChatwootConfig {
  id: string;
  name: string;
  chatwootUrl: string;
  accountId: string;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface Submission {
  id: string;
  title: string;
  originalText: string;
  status: 'PROCESSING' | 'COMPLETED' | 'FAILED';
  aiProvider: string;
  createdAt: string;
  updatedAt: string;
  config: {
    id: string;
    name: string;
  };
  automations?: Automation[];
}

export interface Automation {
  id: string;
  name: string;
  description: string;
  eventName: string;
  conditions: any;
  actions: any;
  status: 'DRAFT' | 'SENT' | 'ERROR';
  active: boolean;
  chatwootRuleId?: string;
  createdAt: string;
  updatedAt: string;
  sentAt?: string;
}

export interface AuthResponse {
  user: User;
  token: string;
}
