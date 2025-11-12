import { GoogleGenerativeAI } from '@google/generative-ai';
import prisma from '../config/database';
import { decrypt } from '../utils/encryption';

export class AIService {
  private getGeminiClient(apiKey: string): GoogleGenerativeAI {
    return new GoogleGenerativeAI(apiKey);
  }

  async generateText(userId: string, prompt: string, taskType?: string): Promise<string> {
    try {
      // Busca o usuário e sua assinatura
      const user = await prisma.user.findUnique({
        where: { id: userId },
        include: { subscriptions: { orderBy: { createdAt: 'desc' }, take: 1 } },
      });

      if (!user) {
        throw new Error('Usuário não encontrado');
      }

      // Determina qual chave de API usar
      let apiKey: string;
      
      // Se o usuário tem plano Pro (packageTier >= 2) e tem chave própria cadastrada
      const subscription = user.subscriptions[0];
      if (subscription && subscription.packageTier >= 2 && user.geminiApiKey) {
        // Usa a chave do usuário (descriptografada)
        apiKey = decrypt(user.geminiApiKey);
      } else {
        // Usa a chave do sistema
        apiKey = process.env.GEMINI_API_KEY || '';
        if (!apiKey) {
          throw new Error('Chave de API do Gemini não configurada');
        }
      }

      // Inicializa o cliente Gemini
      const genAI = this.getGeminiClient(apiKey);
      const model = genAI.getGenerativeModel({ model: 'gemini-pro' });

      // Adiciona contexto ao prompt baseado no tipo de tarefa
      let enhancedPrompt = prompt;
      if (taskType === 'social_post') {
        enhancedPrompt = `Como especialista em marketing de redes sociais, ${prompt}`;
      } else if (taskType === 'hashtags') {
        enhancedPrompt = `Gere hashtags relevantes e populares para: ${prompt}`;
      }

      // Gera o texto
      const result = await model.generateContent(enhancedPrompt);
      const response = await result.response;
      const text = response.text();

      return text;
    } catch (error: any) {
      console.error('Erro ao gerar texto com IA:', error);
      throw new Error(`Erro ao gerar texto: ${error.message}`);
    }
  }

  async generateImage(userId: string, prompt: string): Promise<string> {
    // Nota: O Gemini não gera imagens diretamente
    // Esta função seria implementada com outra API (DALL-E, Stable Diffusion, etc.)
    throw new Error('Geração de imagens não implementada ainda');
  }
}

export default new AIService();
