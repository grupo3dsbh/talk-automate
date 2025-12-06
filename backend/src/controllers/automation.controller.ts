import { Response } from 'express';
import { AuthRequest } from '../middleware/auth.middleware';
import { prisma } from '../utils/prisma';
import { ChatwootService } from '../services/chatwoot.service';
import { ChatwootAutomationRule } from '../types';

export class AutomationController {
  /**
   * Lista automações de uma submissão
   */
  async list(req: AuthRequest, res: Response) {
    try {
      const { submissionId } = req.params;

      // Verifica se submissão existe e pertence ao usuário
      const submission = await prisma.submission.findFirst({
        where: { id: submissionId, userId: req.userId },
      });

      if (!submission) {
        return res.status(404).json({ error: 'Submissão não encontrada' });
      }

      const automations = await prisma.automation.findMany({
        where: { submissionId },
        orderBy: { createdAt: 'asc' },
      });

      return res.json(automations);
    } catch (error: any) {
      console.error('Erro ao listar automações:', error);
      return res.status(500).json({ error: 'Erro ao listar automações' });
    }
  }

  /**
   * Busca uma automação específica
   */
  async get(req: AuthRequest, res: Response) {
    try {
      const { id } = req.params;

      const automation = await prisma.automation.findFirst({
        where: {
          id,
          submission: {
            userId: req.userId,
          },
        },
        include: {
          submission: {
            include: {
              config: true,
            },
          },
        },
      });

      if (!automation) {
        return res.status(404).json({ error: 'Automação não encontrada' });
      }

      return res.json(automation);
    } catch (error: any) {
      console.error('Erro ao buscar automação:', error);
      return res.status(500).json({ error: 'Erro ao buscar automação' });
    }
  }

  /**
   * Atualiza uma automação
   */
  async update(req: AuthRequest, res: Response) {
    try {
      const { id } = req.params;
      const { name, description, eventName, conditions, actions, active } = req.body;

      const automation = await prisma.automation.findFirst({
        where: {
          id,
          submission: {
            userId: req.userId,
          },
        },
      });

      if (!automation) {
        return res.status(404).json({ error: 'Automação não encontrada' });
      }

      const updated = await prisma.automation.update({
        where: { id },
        data: {
          ...(name && { name }),
          ...(description && { description }),
          ...(eventName && { eventName }),
          ...(conditions && { conditions }),
          ...(actions && { actions }),
          ...(active !== undefined && { active }),
        },
      });

      return res.json(updated);
    } catch (error: any) {
      console.error('Erro ao atualizar automação:', error);
      return res.status(500).json({ error: 'Erro ao atualizar automação' });
    }
  }

  /**
   * Deleta uma automação
   */
  async delete(req: AuthRequest, res: Response) {
    try {
      const { id } = req.params;

      const automation = await prisma.automation.findFirst({
        where: {
          id,
          submission: {
            userId: req.userId,
          },
        },
      });

      if (!automation) {
        return res.status(404).json({ error: 'Automação não encontrada' });
      }

      await prisma.automation.delete({ where: { id } });

      return res.status(204).send();
    } catch (error: any) {
      console.error('Erro ao deletar automação:', error);
      return res.status(500).json({ error: 'Erro ao deletar automação' });
    }
  }

  /**
   * Envia uma automação para o Chatwoot
   */
  async sendToChatwoot(req: AuthRequest, res: Response) {
    try {
      const { id } = req.params;

      const automation = await prisma.automation.findFirst({
        where: {
          id,
          submission: {
            userId: req.userId,
          },
        },
        include: {
          submission: {
            include: {
              config: true,
            },
          },
        },
      });

      if (!automation) {
        return res.status(404).json({ error: 'Automação não encontrada' });
      }

      // Cria serviço do Chatwoot
      const chatwootService = new ChatwootService({
        chatwootUrl: automation.submission.config.chatwootUrl,
        accountId: automation.submission.config.accountId,
        apiAccessToken: automation.submission.config.apiAccessToken,
      });

      // Prepara dados da automação
      const rule: ChatwootAutomationRule = {
        name: automation.name,
        description: automation.description,
        event_name: automation.eventName as any,
        active: automation.active,
        conditions: automation.conditions as any,
        actions: automation.actions as any,
      };

      // Envia para o Chatwoot
      const chatwootResponse = await chatwootService.createAutomationRule(rule);

      // Atualiza automação no banco
      const updated = await prisma.automation.update({
        where: { id },
        data: {
          status: 'SENT',
          chatwootRuleId: chatwootResponse.id.toString(),
          sentAt: new Date(),
        },
      });

      return res.json({
        automation: updated,
        chatwootResponse,
      });
    } catch (error: any) {
      console.error('Erro ao enviar automação:', error);

      // Marca como erro
      await prisma.automation.update({
        where: { id: req.params.id },
        data: { status: 'ERROR' },
      });

      return res.status(500).json({
        error: 'Erro ao enviar automação para o Chatwoot',
        details: error.message,
      });
    }
  }

  /**
   * Envia todas as automações de uma submissão para o Chatwoot
   */
  async sendAllToChatwoot(req: AuthRequest, res: Response) {
    try {
      const { submissionId } = req.params;

      const submission = await prisma.submission.findFirst({
        where: { id: submissionId, userId: req.userId },
        include: {
          config: true,
          automations: {
            where: { status: 'DRAFT' },
          },
        },
      });

      if (!submission) {
        return res.status(404).json({ error: 'Submissão não encontrada' });
      }

      const chatwootService = new ChatwootService({
        chatwootUrl: submission.config.chatwootUrl,
        accountId: submission.config.accountId,
        apiAccessToken: submission.config.apiAccessToken,
      });

      const results = [];
      const errors = [];

      for (const automation of submission.automations) {
        try {
          const rule: ChatwootAutomationRule = {
            name: automation.name,
            description: automation.description,
            event_name: automation.eventName as any,
            active: automation.active,
            conditions: automation.conditions as any,
            actions: automation.actions as any,
          };

          const chatwootResponse = await chatwootService.createAutomationRule(rule);

          await prisma.automation.update({
            where: { id: automation.id },
            data: {
              status: 'SENT',
              chatwootRuleId: chatwootResponse.id.toString(),
              sentAt: new Date(),
            },
          });

          results.push({
            automationId: automation.id,
            success: true,
            chatwootRuleId: chatwootResponse.id,
          });
        } catch (error: any) {
          await prisma.automation.update({
            where: { id: automation.id },
            data: { status: 'ERROR' },
          });

          errors.push({
            automationId: automation.id,
            error: error.message,
          });
        }
      }

      return res.json({
        success: results.length,
        failed: errors.length,
        results,
        errors,
      });
    } catch (error: any) {
      console.error('Erro ao enviar automações:', error);
      return res.status(500).json({ error: 'Erro ao enviar automações' });
    }
  }
}
