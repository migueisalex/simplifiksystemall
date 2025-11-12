import { Response } from 'express';
import { AuthRequest } from '../types';
import s3Service from '../services/s3Service';

export class MediaController {
  async uploadMedia(req: AuthRequest, res: Response): Promise<void> {
    try {
      if (!req.file) {
        res.status(400).json({ error: 'Nenhum arquivo fornecido' });
        return;
      }

      // Faz upload para o S3
      const url = await s3Service.uploadFile(req.file);

      res.status(201).json({
        url,
        mimeType: req.file.mimetype,
      });
    } catch (error: any) {
      console.error('Erro ao fazer upload de mídia:', error);
      res.status(500).json({ error: 'Erro ao fazer upload de mídia' });
    }
  }
}

export default new MediaController();
