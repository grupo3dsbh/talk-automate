import { Response } from 'express';
import { AuthRequest } from '../middleware/auth.middleware';
import { prisma } from '../utils/prisma';
import { AIService } from '../services/ai.service';
import { AIProvider } from '../types';

export class SubmissionController {
  private aiService: AIService;

  constructor() {
    this.aiService = new AIService();
  }

  /**
   * Lista todas as submissões do usuário
   */
  async list(req: AuthRequest, res: Response) {
    try {
      const submissions = await prisma.submission.findMany({
        where: { userId: req.userId },
        include: {
          config: {
            select: {
              id: true,
              name: true,
            },
          },
          automations: {
            select: {
              id: true,
              name: true,
              status: true,
            },
          },
        },
        orderBy: { createdAt: 'desc' },
      });

      return res.json(submissions);
    } catch (error: any) {
      console.error('Erro ao listar submissões:', error);
      return res.status(500).json({ error: 'Erro ao listar submissões' });
    }
  }

  /**
   * Busca uma submissão específica
   */
  async get(req: AuthRequest, res: Response) {
    try {
      const { id } = req.params;

      const submission = await prisma.submission.findFirst({
        where: { id, userId: req.userId },
        include: {
          config: {
            select: {
              id: true,
              name: true,
              chatwootUrl: true,
              accountId: true,
            },
          },
          automations: true,
        },
      });

      if (!submission) {
        return res.status(404).json({ error: 'Submissão não encontrada' });
      }

      return res.json(submission);
    } catch (error: any) {
      console.error('Erro ao buscar submissão:', error);
      return res.status(500).json({ error: 'Erro ao buscar submissão' });
    }
  }

  /**
   * Cria uma nova submissão e processa com IA
   */
  async create(req: AuthRequest, res: Response) {
    try {
      const { title, originalText, configId, aiProvider = 'groq' } = req.body;

      // Validações
      if (!title || !originalText || !configId) {
        return res.status(400).json({
          error: 'Título, texto e configuração são obrigatórios',
        });
      }

      // Verifica se configuração existe e pertence ao usuário
      const config = await prisma.chatwootConfig.findFirst({
        where: { id: configId, userId: req.userId },
      });

      if (!config) {
        return res.status(404).json({ error: 'Configuração não encontrada' });
      }

      // Cria submissão
      const submission = await prisma.submission.create({
        data: {
          userId: req.userId!,
          configId,
          title,
          originalText,
          aiProvider,
          status: 'PROCESSING',
        },
      });

      // Processa em background (não bloqueia a resposta)
      this.processSubmission(submission.id, originalText, aiProvider as AIProvider)
        .catch(error => {
          console.error('Erro ao processar submissão:', error);
        });

      return res.status(201).json(submission);
    } catch (error: any) {
      console.error('Erro ao criar submissão:', error);
      return res.status(500).json({ error: 'Erro ao criar submissão' });
    }
  }

  /**
   * Processa a submissão com IA
   */
  private async processSubmission(submissionId: string, text: string, provider: AIProvider) {
    try {
      // Analisa com IA
      const result = await this.aiService.analyzeText(text, provider);

      // Cria automações no banco
      const automations = result.automations.map(auto => ({
        submissionId,
        name: auto.name,
        description: `${auto.description}\n\nRaciocínio da IA: ${auto.reasoning}`,
        eventName: auto.eventName,
        conditions: auto.conditions as any,
        actions: auto.actions as any,
        status: 'DRAFT' as const,
        active: true,
      }));

      await prisma.automation.createMany({
        data: automations,
      });

      // Atualiza status da submissão
      await prisma.submission.update({
        where: { id: submissionId },
        data: { status: 'COMPLETED' },
      });
    } catch (error) {
      console.error('Erro ao processar com IA:', error);

      // Marca como falha
      await prisma.submission.update({
        where: { id: submissionId },
        data: { status: 'FAILED' },
      });
    }
  }

  /**
   * Deleta uma submissão
   */
  async delete(req: AuthRequest, res: Response) {
    try {
      const { id } = req.params;

      const submission = await prisma.submission.findFirst({
        where: { id, userId: req.userId },
      });

      if (!submission) {
        return res.status(404).json({ error: 'Submissão não encontrada' });
      }

      await prisma.submission.delete({ where: { id } });

      return res.status(204).send();
    } catch (error: any) {
      console.error('Erro ao deletar submissão:', error);
      return res.status(500).json({ error: 'Erro ao deletar submissão' });
    }
  }
}
