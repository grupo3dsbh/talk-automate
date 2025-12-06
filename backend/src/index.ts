import express from 'express';
import cors from 'cors';
import { config } from './config';

// Routes
import authRoutes from './routes/auth.routes';
import chatwootConfigRoutes from './routes/chatwoot-config.routes';
import submissionRoutes from './routes/submission.routes';
import automationRoutes from './routes/automation.routes';

const app = express();

// Middleware
app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Health check
app.get('/health', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// Routes
app.use('/api/auth', authRoutes);
app.use('/api/chatwoot-configs', chatwootConfigRoutes);
app.use('/api/submissions', submissionRoutes);
app.use('/api/automations', automationRoutes);

// 404 handler
app.use((req, res) => {
  res.status(404).json({ error: 'Rota não encontrada' });
});

// Error handler
app.use((err: any, req: express.Request, res: express.Response, next: express.NextFunction) => {
  console.error('Erro não tratado:', err);
  res.status(500).json({ error: 'Erro interno do servidor' });
});

// Start server
app.listen(config.port, () => {
  console.log(`🚀 Servidor rodando na porta ${config.port}`);
  console.log(`📝 Ambiente: ${config.nodeEnv}`);
  console.log(`🤖 Provider de IA padrão: ${config.ai.defaultProvider}`);
});
