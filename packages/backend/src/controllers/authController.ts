import { Response } from 'express';
import bcrypt from 'bcrypt';
import { AuthRequest } from '../types';
import prisma from '../config/database';
import { generateToken } from '../utils/jwt';
import { encrypt } from '../utils/encryption';
import emailService from '../services/emailService';
import { UserStatus, SubscriptionStatus } from '../../generated/prisma';

// Armazena códigos de verificação temporariamente (em produção, usar Redis)
const verificationCodes = new Map<string, { code: string; expiresAt: Date }>();

export class AuthController {
  async register(req: AuthRequest, res: Response): Promise<void> {
    try {
      const { email, password, fullName, birthDate, subscriptionData, paymentData } = req.body;

      // Valida se o email já existe
      const existingUser = await prisma.user.findUnique({ where: { email } });
      if (existingUser) {
        res.status(400).json({ error: 'Email já cadastrado' });
        return;
      }

      // Hash da senha
      const passwordHash = await bcrypt.hash(password, 10);

      // Cria o usuário
      const user = await prisma.user.create({
        data: {
          email,
          passwordHash,
          fullName,
          birthDate: new Date(birthDate),
          status: UserStatus.Ativo,
        },
      });

      // Cria a assinatura
      const subscription = await prisma.subscription.create({
        data: {
          userId: user.id,
          packageTier: subscriptionData?.packageTier || 0,
          hasAiAddon: subscriptionData?.hasAiAddon || false,
          status: SubscriptionStatus.Active,
          currentPeriodEnd: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000), // 30 dias
          paymentGatewaySubscriptionId: paymentData?.subscriptionId,
        },
      });

      // Gera código de verificação
      const code = '123456'; // Em produção, gerar código aleatório
      const expiresAt = new Date(Date.now() + 15 * 60 * 1000); // 15 minutos
      verificationCodes.set(email, { code, expiresAt });

      // Envia email de verificação
      await emailService.sendVerificationCode(email, code);

      res.status(201).json({
        message: 'Verification code sent.',
        userId: user.id,
      });
    } catch (error: any) {
      console.error('Erro no registro:', error);
      res.status(500).json({ error: 'Erro ao registrar usuário' });
    }
  }

  async verifyEmail(req: AuthRequest, res: Response): Promise<void> {
    try {
      const { email, code } = req.body;

      const storedData = verificationCodes.get(email);
      
      if (!storedData) {
        res.status(400).json({ error: 'Código não encontrado ou expirado' });
        return;
      }

      if (new Date() > storedData.expiresAt) {
        verificationCodes.delete(email);
        res.status(400).json({ error: 'Código expirado' });
        return;
      }

      if (storedData.code !== code) {
        res.status(400).json({ error: 'Código inválido' });
        return;
      }

      // Ativa o usuário
      const user = await prisma.user.findUnique({
        where: { email },
        include: { subscriptions: { orderBy: { createdAt: 'desc' }, take: 1 } },
      });

      if (!user) {
        res.status(404).json({ error: 'Usuário não encontrado' });
        return;
      }

      // Remove o código usado
      verificationCodes.delete(email);

      // Gera token
      const token = generateToken({
        id: user.id,
        email: user.email,
        role: user.role,
        status: user.status,
        type: 'user',
      });

      res.status(200).json({
        user: {
          id: user.id,
          email: user.email,
          fullName: user.fullName,
          role: user.role,
          status: user.status,
        },
        token,
        subscription: user.subscriptions[0] || null,
      });
    } catch (error: any) {
      console.error('Erro na verificação:', error);
      res.status(500).json({ error: 'Erro ao verificar email' });
    }
  }

  async login(req: AuthRequest, res: Response): Promise<void> {
    try {
      const { email, password } = req.body;

      // Busca usuário
      let user = await prisma.user.findUnique({
        where: { email },
        include: { subscriptions: { orderBy: { createdAt: 'desc' }, take: 1 } },
      });

      // Se não encontrou como usuário, busca como staff
      if (!user) {
        const staff = await prisma.staffMember.findUnique({
          where: { email },
        });

        if (!staff) {
          res.status(401).json({ error: 'Credenciais inválidas' });
          return;
        }

        // Verifica senha do staff
        const isValidPassword = await bcrypt.compare(password, staff.passwordHash);
        if (!isValidPassword) {
          res.status(401).json({ error: 'Credenciais inválidas' });
          return;
        }

        // Gera token para staff
        const token = generateToken({
          id: staff.id,
          email: staff.email,
          role: staff.role,
          type: 'staff',
        });

        res.status(200).json({
          staff: {
            id: staff.id,
            email: staff.email,
            role: staff.role,
          },
          token,
        });
        return;
      }

      // Verifica senha do usuário
      const isValidPassword = await bcrypt.compare(password, user.passwordHash);
      if (!isValidPassword) {
        res.status(401).json({ error: 'Credenciais inválidas' });
        return;
      }

      // Verifica status do usuário
      if (user.status === UserStatus.Inadimplente) {
        // Retorna token mas indica status
        const token = generateToken({
          id: user.id,
          email: user.email,
          role: user.role,
          status: user.status,
          type: 'user',
        });

        res.status(200).json({
          user: {
            id: user.id,
            email: user.email,
            fullName: user.fullName,
            role: user.role,
            status: user.status,
          },
          token,
          subscription: user.subscriptions[0] || null,
          warning: 'Sua conta está inadimplente. Regularize seu pagamento.',
        });
        return;
      }

      // Login normal
      const token = generateToken({
        id: user.id,
        email: user.email,
        role: user.role,
        status: user.status,
        type: 'user',
      });

      res.status(200).json({
        user: {
          id: user.id,
          email: user.email,
          fullName: user.fullName,
          role: user.role,
          status: user.status,
        },
        token,
        subscription: user.subscriptions[0] || null,
      });
    } catch (error: any) {
      console.error('Erro no login:', error);
      res.status(500).json({ error: 'Erro ao fazer login' });
    }
  }
}

export default new AuthController();
