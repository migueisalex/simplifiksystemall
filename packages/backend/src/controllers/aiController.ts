import { Response } from 'express';
import { AuthRequest } from '../types';
import aiService from '../services/aiService';
import usageService from '../services/usageService';

export class AIController {
  async generateText(req: AuthRequest, res: Response): Promise<void> {
    try {
      const userId = req.user!.id;
      const { prompt, taskType } = req.body;

      if (!prompt) {
        res.status(400).json({ error: 'Prompt é obrigatório' });
        return;
      }

      // Verifica limite de uso de IA
      const limitCheck = await usageService.checkAiTextLimit(userId);
      if (!limitCheck.allowed) {
        res.status(402).json({ error: limitCheck.message });
        return;
      }

      // Gera o texto
      const text = await aiService.generateText(userId, prompt, taskType);

      // Incrementa contador de uso
      await usageService.incrementAiTextCount(userId);

      res.status(200).json({ text });
    } catch (error: any) {
      console.error('Erro ao gerar texto:', error);
      res.status(500).json({ error: error.message || 'Erro ao gerar texto' });
    }
  }

  async generateImage(req: AuthRequest, res: Response): Promise<void> {
    try {
      const userId = req.user!.id;
      const { prompt } = req.body;

      if (!prompt) {
        res.status(400).json({ error: 'Prompt é obrigatório' });
        return;
      }

      // Verifica limite de uso de IA
      const limitCheck = await usageService.checkAiImageLimit(userId);
      if (!limitCheck.allowed) {
        res.status(402).json({ error: limitCheck.message });
        return;
      }

      // Gera a imagem
      const imageUrl = await aiService.generateImage(userId, prompt);

      // Incrementa contador de uso
      await usageService.incrementAiImageCount(userId);

      res.status(200).json({ imageUrl });
    } catch (error: any) {
      console.error('Erro ao gerar imagem:', error);
      res.status(500).json({ error: error.message || 'Erro ao gerar imagem' });
    }
  }
}

export default new AIController();
