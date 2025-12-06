import dotenv from 'dotenv';

dotenv.config();

export const config = {
  port: process.env.PORT || 3001,
  nodeEnv: process.env.NODE_ENV || 'development',
  jwtSecret: process.env.JWT_SECRET || 'fallback-secret-key',

  ai: {
    defaultProvider: (process.env.DEFAULT_AI_PROVIDER || 'groq') as 'groq' | 'openai' | 'anthropic',
    groq: {
      apiKey: process.env.GROQ_API_KEY || '',
    },
    openai: {
      apiKey: process.env.OPENAI_API_KEY || '',
    },
    anthropic: {
      apiKey: process.env.ANTHROPIC_API_KEY || '',
    },
  },
};
