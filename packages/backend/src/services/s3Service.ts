import { S3Client, PutObjectCommand, DeleteObjectCommand } from '@aws-sdk/client-s3';
import { v4 as uuidv4 } from 'uuid';
import path from 'path';

const s3Client = new S3Client({
  region: process.env.S3_REGION || 'us-east-1',
  endpoint: process.env.S3_ENDPOINT_URL,
  credentials: {
    accessKeyId: process.env.S3_ACCESS_KEY_ID || '',
    secretAccessKey: process.env.S3_SECRET_ACCESS_KEY || '',
  },
  forcePathStyle: true, // Necessário para alguns provedores S3-compatíveis
});

const BUCKET_NAME = process.env.S3_BUCKET_NAME || 'simplifikapost';

export class S3Service {
  async uploadFile(file: Express.Multer.File): Promise<string> {
    const fileExtension = path.extname(file.originalname);
    const fileName = `${uuidv4()}${fileExtension}`;
    const key = `media/${fileName}`;

    const command = new PutObjectCommand({
      Bucket: BUCKET_NAME,
      Key: key,
      Body: file.buffer,
      ContentType: file.mimetype,
      ACL: 'public-read',
    });

    await s3Client.send(command);

    // Retorna a URL pública do arquivo
    const baseUrl = process.env.S3_ENDPOINT_URL || `https://${BUCKET_NAME}.s3.amazonaws.com`;
    return `${baseUrl}/${key}`;
  }

  async deleteFile(url: string): Promise<void> {
    try {
      // Extrai a chave do arquivo da URL
      const urlObj = new URL(url);
      const key = urlObj.pathname.substring(1); // Remove a barra inicial

      const command = new DeleteObjectCommand({
        Bucket: BUCKET_NAME,
        Key: key,
      });

      await s3Client.send(command);
    } catch (error) {
      console.error('Erro ao deletar arquivo do S3:', error);
      throw error;
    }
  }

  async deleteMultipleFiles(urls: string[]): Promise<void> {
    const deletePromises = urls.map(url => this.deleteFile(url));
    await Promise.allSettled(deletePromises);
  }
}

export default new S3Service();
