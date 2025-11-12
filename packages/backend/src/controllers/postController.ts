import { Response } from 'express';
import { AuthRequest } from '../types';
import prisma from '../config/database';
import usageService from '../services/usageService';
import s3Service from '../services/s3Service';
import { PostStatus, PostType } from '../../generated/prisma';

export class PostController {
  async getPosts(req: AuthRequest, res: Response): Promise<void> {
    try {
      const userId = req.user!.id;
      const { status, limit, offset } = req.query;

      const where: any = { userId };
      if (status) {
        where.status = status as PostStatus;
      }

      const posts = await prisma.post.findMany({
        where,
        include: {
          mediaItems: true,
        },
        orderBy: { scheduledAt: 'desc' },
        take: limit ? parseInt(limit as string) : undefined,
        skip: offset ? parseInt(offset as string) : undefined,
      });

      res.status(200).json(posts);
    } catch (error: any) {
      console.error('Erro ao buscar posts:', error);
      res.status(500).json({ error: 'Erro ao buscar posts' });
    }
  }

  async createPost(req: AuthRequest, res: Response): Promise<void> {
    try {
      const userId = req.user!.id;
      const { content, platforms, scheduledAt, postType, media } = req.body;

      // Verifica limite de posts
      const limitCheck = await usageService.checkPostLimit(userId);
      if (!limitCheck.allowed) {
        res.status(402).json({ error: limitCheck.message });
        return;
      }

      // Cria o post
      const post = await prisma.post.create({
        data: {
          userId,
          content,
          platforms,
          scheduledAt: new Date(scheduledAt),
          postType: postType as PostType,
          status: PostStatus.scheduled,
        },
      });

      // Adiciona itens de mídia se fornecidos
      if (media && media.length > 0) {
        await prisma.mediaItem.createMany({
          data: media.map((item: any) => ({
            postId: post.id,
            storageUrl: item.storageUrl,
            mimeType: item.mimeType,
            aspectRatio: item.aspectRatio,
            edits: item.edits,
          })),
        });
      }

      // Incrementa contador de uso
      await usageService.incrementPostCount(userId);

      // Busca o post completo com mídia
      const fullPost = await prisma.post.findUnique({
        where: { id: post.id },
        include: { mediaItems: true },
      });

      res.status(201).json(fullPost);
    } catch (error: any) {
      console.error('Erro ao criar post:', error);
      res.status(500).json({ error: 'Erro ao criar post' });
    }
  }

  async updatePost(req: AuthRequest, res: Response): Promise<void> {
    try {
      const userId = req.user!.id;
      const { id } = req.params;
      const { content, platforms, scheduledAt, postType, media } = req.body;

      // Verifica se o post pertence ao usuário
      const existingPost = await prisma.post.findFirst({
        where: { id, userId },
      });

      if (!existingPost) {
        res.status(404).json({ error: 'Post não encontrado' });
        return;
      }

      // Atualiza o post
      const post = await prisma.post.update({
        where: { id },
        data: {
          content,
          platforms,
          scheduledAt: scheduledAt ? new Date(scheduledAt) : undefined,
          postType: postType as PostType,
        },
      });

      // Atualiza mídia se fornecida
      if (media) {
        // Remove mídia antiga
        await prisma.mediaItem.deleteMany({
          where: { postId: id },
        });

        // Adiciona nova mídia
        if (media.length > 0) {
          await prisma.mediaItem.createMany({
            data: media.map((item: any) => ({
              postId: post.id,
              storageUrl: item.storageUrl,
              mimeType: item.mimeType,
              aspectRatio: item.aspectRatio,
              edits: item.edits,
            })),
          });
        }
      }

      // Busca o post completo
      const fullPost = await prisma.post.findUnique({
        where: { id },
        include: { mediaItems: true },
      });

      res.status(200).json(fullPost);
    } catch (error: any) {
      console.error('Erro ao atualizar post:', error);
      res.status(500).json({ error: 'Erro ao atualizar post' });
    }
  }

  async deletePost(req: AuthRequest, res: Response): Promise<void> {
    try {
      const userId = req.user!.id;
      const { id } = req.params;

      // Busca o post com mídia
      const post = await prisma.post.findFirst({
        where: { id, userId },
        include: { mediaItems: true },
      });

      if (!post) {
        res.status(404).json({ error: 'Post não encontrado' });
        return;
      }

      // Deleta arquivos de mídia do S3
      if (post.mediaItems.length > 0) {
        const urls = post.mediaItems.map(item => item.storageUrl);
        await s3Service.deleteMultipleFiles(urls);
      }

      // Deleta o post (cascade deleta os mediaItems)
      await prisma.post.delete({
        where: { id },
      });

      res.status(204).send();
    } catch (error: any) {
      console.error('Erro ao deletar post:', error);
      res.status(500).json({ error: 'Erro ao deletar post' });
    }
  }

  async clonePost(req: AuthRequest, res: Response): Promise<void> {
    try {
      const userId = req.user!.id;
      const { id } = req.params;

      // Verifica limite de posts
      const limitCheck = await usageService.checkPostLimit(userId);
      if (!limitCheck.allowed) {
        res.status(402).json({ error: limitCheck.message });
        return;
      }

      // Busca o post original
      const originalPost = await prisma.post.findFirst({
        where: { id, userId },
        include: { mediaItems: true },
      });

      if (!originalPost) {
        res.status(404).json({ error: 'Post não encontrado' });
        return;
      }

      // Cria o clone
      const newPost = await prisma.post.create({
        data: {
          userId,
          content: originalPost.content,
          platforms: originalPost.platforms as any,
          scheduledAt: new Date(), // Agenda para agora
          postType: originalPost.postType,
          status: PostStatus.scheduled,
        },
      });

      // Clona os itens de mídia
      if (originalPost.mediaItems.length > 0) {
        await prisma.mediaItem.createMany({
          data: originalPost.mediaItems.map((item: any) => ({
            postId: newPost.id,
            storageUrl: item.storageUrl,
            mimeType: item.mimeType,
            aspectRatio: item.aspectRatio,
            edits: item.edits as any,
          })),
        });
      }

      // Incrementa contador de uso
      await usageService.incrementPostCount(userId);

      // Busca o post completo
      const fullPost = await prisma.post.findUnique({
        where: { id: newPost.id },
        include: { mediaItems: true },
      });

      res.status(201).json({ newPost: fullPost });
    } catch (error: any) {
      console.error('Erro ao clonar post:', error);
      res.status(500).json({ error: 'Erro ao clonar post' });
    }
  }
}

export default new PostController();
