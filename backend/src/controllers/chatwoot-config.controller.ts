import { Response } from 'express';
import { AuthRequest } from '../middleware/auth.middleware';
import { prisma } from '../utils/prisma';
import { ChatwootService } from '../services/chatwoot.service';

export class ChatwootConfigController {
  /**
   * Lista todas as configurações do usuário
   */
  async list(req: AuthRequest, res: Response) {
    try {
      const configs = await prisma.chatwootConfig.findMany({
        where: { userId: req.userId },
        select: {
          id: true,
          name: true,
          chatwootUrl: true,
          accountId: true,
          isActive: true,
          createdAt: true,
          updatedAt: true,
          // Não retornar o token por segurança
        },
      });

      return res.json(configs);
    } catch (error: any) {
      console.error('Erro ao listar configurações:', error);
      return res.status(500).json({ error: 'Erro ao listar configurações' });
    }
  }

  /**
   * Cria uma nova configuração do Chatwoot
   */
  async create(req: AuthRequest, res: Response) {
    try {
      const { name, chatwootUrl, accountId, apiAccessToken } = req.body;

      // Validações
      if (!name || !chatwootUrl || !accountId || !apiAccessToken) {
        return res.status(400).json({
          error: 'Nome, URL do Chatwoot, Account ID e Token são obrigatórios',
        });
      }

      // Testa conexão com Chatwoot
      const chatwootService = new ChatwootService({
        chatwootUrl,
        accountId,
        apiAccessToken,
      });

      const isValid = await chatwootService.testConnection();
      if (!isValid) {
        return res.status(400).json({
          error: 'Não foi possível conectar ao Chatwoot. Verifique as credenciais.',
        });
      }

      // Cria configuração
      const config = await prisma.chatwootConfig.create({
        data: {
          userId: req.userId!,
          name,
          chatwootUrl,
          accountId,
          apiAccessToken,
        },
        select: {
          id: true,
          name: true,
          chatwootUrl: true,
          accountId: true,
          isActive: true,
          createdAt: true,
          updatedAt: true,
        },
      });

      return res.status(201).json(config);
    } catch (error: any) {
      console.error('Erro ao criar configuração:', error);
      return res.status(500).json({ error: 'Erro ao criar configuração' });
    }
  }

  /**
   * Atualiza uma configuração
   */
  async update(req: AuthRequest, res: Response) {
    try {
      const { id } = req.params;
      const { name, chatwootUrl, accountId, apiAccessToken, isActive } = req.body;

      // Verifica se configuração existe e pertence ao usuário
      const existingConfig = await prisma.chatwootConfig.findFirst({
        where: { id, userId: req.userId },
      });

      if (!existingConfig) {
        return res.status(404).json({ error: 'Configuração não encontrada' });
      }

      // Se mudou credenciais, testa conexão
      if (chatwootUrl || accountId || apiAccessToken) {
        const chatwootService = new ChatwootService({
          chatwootUrl: chatwootUrl || existingConfig.chatwootUrl,
          accountId: accountId || existingConfig.accountId,
          apiAccessToken: apiAccessToken || existingConfig.apiAccessToken,
        });

        const isValid = await chatwootService.testConnection();
        if (!isValid) {
          return res.status(400).json({
            error: 'Não foi possível conectar ao Chatwoot. Verifique as credenciais.',
          });
        }
      }

      // Atualiza configuração
      const config = await prisma.chatwootConfig.update({
        where: { id },
        data: {
          ...(name && { name }),
          ...(chatwootUrl && { chatwootUrl }),
          ...(accountId && { accountId }),
          ...(apiAccessToken && { apiAccessToken }),
          ...(isActive !== undefined && { isActive }),
        },
        select: {
          id: true,
          name: true,
          chatwootUrl: true,
          accountId: true,
          isActive: true,
          createdAt: true,
          updatedAt: true,
        },
      });

      return res.json(config);
    } catch (error: any) {
      console.error('Erro ao atualizar configuração:', error);
      return res.status(500).json({ error: 'Erro ao atualizar configuração' });
    }
  }

  /**
   * Deleta uma configuração
   */
  async delete(req: AuthRequest, res: Response) {
    try {
      const { id } = req.params;

      // Verifica se configuração existe e pertence ao usuário
      const existingConfig = await prisma.chatwootConfig.findFirst({
        where: { id, userId: req.userId },
      });

      if (!existingConfig) {
        return res.status(404).json({ error: 'Configuração não encontrada' });
      }

      await prisma.chatwootConfig.delete({ where: { id } });

      return res.status(204).send();
    } catch (error: any) {
      console.error('Erro ao deletar configuração:', error);
      return res.status(500).json({ error: 'Erro ao deletar configuração' });
    }
  }

  /**
   * Testa conexão com uma configuração
   */
  async testConnection(req: AuthRequest, res: Response) {
    try {
      const { id } = req.params;

      const config = await prisma.chatwootConfig.findFirst({
        where: { id, userId: req.userId },
      });

      if (!config) {
        return res.status(404).json({ error: 'Configuração não encontrada' });
      }

      const chatwootService = new ChatwootService({
        chatwootUrl: config.chatwootUrl,
        accountId: config.accountId,
        apiAccessToken: config.apiAccessToken,
      });

      const isValid = await chatwootService.testConnection();

      return res.json({ success: isValid });
    } catch (error: any) {
      console.error('Erro ao testar conexão:', error);
      return res.status(500).json({ error: 'Erro ao testar conexão' });
    }
  }
}
