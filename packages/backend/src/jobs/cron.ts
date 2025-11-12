import cron from 'node-cron';
import prisma from '../config/database';
import emailService from '../services/emailService';
import s3Service from '../services/s3Service';
import { UserStatus, PostStatus } from '../../generated/prisma';

class CronJobs {
  // Verifica usuários inadimplentes há mais de 30 dias e bloqueia
  async checkAndBlockDefaultedUsers() {
    console.log('[CRON] Verificando usuários inadimplentes...');
    
    try {
      const thirtyDaysAgo = new Date();
      thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);

      // Busca usuários inadimplentes há mais de 30 dias
      const users = await prisma.user.findMany({
        where: {
          status: UserStatus.Inadimplente,
          updatedAt: {
            lte: thirtyDaysAgo,
          },
        },
      });

      for (const user of users) {
        // Bloqueia o usuário
        await prisma.user.update({
          where: { id: user.id },
          data: { status: UserStatus.Bloqueado },
        });

        // Envia email de notificação
        await emailService.sendAccountBlockedNotification(user.email);
        
        console.log(`[CRON] Usuário ${user.email} bloqueado por inadimplência`);
      }

      console.log(`[CRON] ${users.length} usuários bloqueados`);
    } catch (error) {
      console.error('[CRON] Erro ao bloquear usuários inadimplentes:', error);
    }
  }

  // Limpa posts antigos publicados há mais de 90 dias
  async cleanOldPublishedPosts() {
    console.log('[CRON] Limpando posts antigos...');
    
    try {
      const ninetyDaysAgo = new Date();
      ninetyDaysAgo.setDate(ninetyDaysAgo.getDate() - 90);

      // Busca posts antigos
      const oldPosts = await prisma.post.findMany({
        where: {
          status: PostStatus.published,
          scheduledAt: {
            lte: ninetyDaysAgo,
          },
        },
        include: {
          mediaItems: true,
        },
      });

      for (const post of oldPosts) {
        // Deleta mídia do S3
        if (post.mediaItems.length > 0) {
          const urls = post.mediaItems.map((item: any) => item.storageUrl);
          await s3Service.deleteMultipleFiles(urls);
        }

        // Deleta o post
        await prisma.post.delete({
          where: { id: post.id },
        });
      }

      console.log(`[CRON] ${oldPosts.length} posts antigos deletados`);
    } catch (error) {
      console.error('[CRON] Erro ao limpar posts antigos:', error);
    }
  }

  // Limpa posts inativos por downgrade há mais de 30 dias
  async cleanInactiveDowngradedPosts() {
    console.log('[CRON] Limpando posts inativos por downgrade...');
    
    try {
      const thirtyDaysAgo = new Date();
      thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);

      // Busca posts inativos há mais de 30 dias
      const inactivePosts = await prisma.post.findMany({
        where: {
          status: PostStatus.inativo_por_downgrade,
          updatedAt: {
            lte: thirtyDaysAgo,
          },
        },
        include: {
          mediaItems: true,
        },
      });

      for (const post of inactivePosts) {
        // Deleta mídia do S3
        if (post.mediaItems.length > 0) {
          const urls = post.mediaItems.map((item: any) => item.storageUrl);
          await s3Service.deleteMultipleFiles(urls);
        }

        // Deleta o post
        await prisma.post.delete({
          where: { id: post.id },
        });
      }

      console.log(`[CRON] ${inactivePosts.length} posts inativos deletados`);
    } catch (error) {
      console.error('[CRON] Erro ao limpar posts inativos:', error);
    }
  }

  // Envia avisos de exclusão 7 dias antes
  async sendDeletionWarnings() {
    console.log('[CRON] Enviando avisos de exclusão...');
    
    try {
      const twentyThreeDaysAgo = new Date();
      twentyThreeDaysAgo.setDate(twentyThreeDaysAgo.getDate() - 23);

      const twentyFourDaysAgo = new Date();
      twentyFourDaysAgo.setDate(twentyFourDaysAgo.getDate() - 24);

      // Busca posts que serão deletados em 7 dias
      const posts = await prisma.post.findMany({
        where: {
          status: PostStatus.inativo_por_downgrade,
          updatedAt: {
            gte: twentyFourDaysAgo,
            lte: twentyThreeDaysAgo,
          },
        },
        include: {
          user: true,
        },
      });

      // Agrupa por usuário
      const userPosts = new Map<string, number>();
      posts.forEach((post: any) => {
        const count = userPosts.get(post.userId) || 0;
        userPosts.set(post.userId, count + 1);
      });

      // Envia emails
      for (const [userId, count] of userPosts) {
        const user = posts.find((p: any) => p.userId === userId)?.user;
        if (user) {
          await emailService.sendPostDeletionWarning(user.email, 7);
        }
      }

      console.log(`[CRON] Avisos enviados para ${userPosts.size} usuários`);
    } catch (error) {
      console.error('[CRON] Erro ao enviar avisos:', error);
    }
  }

  // Inicia todos os cron jobs
  start() {
    console.log('[CRON] Iniciando jobs agendados...');

    // Diariamente às 2h da manhã
    cron.schedule('0 2 * * *', async () => {
      console.log('[CRON] Executando jobs diários...');
      await this.checkAndBlockDefaultedUsers();
      await this.cleanOldPublishedPosts();
      await this.cleanInactiveDowngradedPosts();
      await this.sendDeletionWarnings();
    });

    console.log('[CRON] Jobs agendados iniciados com sucesso');
  }
}

export default new CronJobs();
