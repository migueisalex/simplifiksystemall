import { Response } from 'express';
import { AuthRequest } from '../types';
import prisma from '../config/database';
import emailService from '../services/emailService';
import { PostStatus } from '../../generated/prisma';

export class SubscriptionController {
  async downgradeToFreemium(req: AuthRequest, res: Response): Promise<void> {
    try {
      const userId = req.user!.id;

      // Busca assinatura atual
      const subscription = await prisma.subscription.findFirst({
        where: { userId },
        orderBy: { createdAt: 'desc' },
      });

      if (!subscription) {
        res.status(404).json({ error: 'Assinatura não encontrada' });
        return;
      }

      // Busca posts futuros agendados
      const scheduledPosts = await prisma.post.findMany({
        where: {
          userId,
          status: PostStatus.scheduled,
        },
        orderBy: { scheduledAt: 'asc' },
      });

      // Mantém os 5 primeiros, desativa os restantes
      const postsToKeep = scheduledPosts.slice(0, 5);
      const postsToDeactivate = scheduledPosts.slice(5);

      if (postsToDeactivate.length > 0) {
        await prisma.post.updateMany({
          where: {
            id: { in: postsToDeactivate.map((p: any) => p.id) },
          },
          data: {
            status: PostStatus.inativo_por_downgrade,
          },
        });

        // Envia email de notificação
        const user = await prisma.user.findUnique({ where: { id: userId } });
        if (user) {
          await emailService.sendDowngradeNotification(user.email, postsToDeactivate.length);
        }
      }

      // Atualiza assinatura para Freemium
      await prisma.subscription.update({
        where: { id: subscription.id },
        data: {
          packageTier: 0,
          hasAiAddon: false,
        },
      });

      res.status(200).json({
        message: 'Downgrade realizado com sucesso',
        keptPosts: postsToKeep.length,
        deactivatedPosts: postsToDeactivate.length,
      });
    } catch (error: any) {
      console.error('Erro ao fazer downgrade:', error);
      res.status(500).json({ error: 'Erro ao fazer downgrade' });
    }
  }

  async getSubscription(req: AuthRequest, res: Response): Promise<void> {
    try {
      const userId = req.user!.id;

      const subscription = await prisma.subscription.findFirst({
        where: { userId },
        orderBy: { createdAt: 'desc' },
      });

      if (!subscription) {
        res.status(404).json({ error: 'Assinatura não encontrada' });
        return;
      }

      res.status(200).json(subscription);
    } catch (error: any) {
      console.error('Erro ao buscar assinatura:', error);
      res.status(500).json({ error: 'Erro ao buscar assinatura' });
    }
  }

  async updateSubscription(req: AuthRequest, res: Response): Promise<void> {
    try {
      const userId = req.user!.id;
      const { packageTier, hasAiAddon } = req.body;

      const subscription = await prisma.subscription.findFirst({
        where: { userId },
        orderBy: { createdAt: 'desc' },
      });

      if (!subscription) {
        res.status(404).json({ error: 'Assinatura não encontrada' });
        return;
      }

      const updated = await prisma.subscription.update({
        where: { id: subscription.id },
        data: {
          packageTier,
          hasAiAddon,
        },
      });

      res.status(200).json(updated);
    } catch (error: any) {
      console.error('Erro ao atualizar assinatura:', error);
      res.status(500).json({ error: 'Erro ao atualizar assinatura' });
    }
  }
}

export default new SubscriptionController();
