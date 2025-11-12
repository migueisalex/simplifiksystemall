import { Response, NextFunction } from 'express';
import { AuthRequest } from '../types';
import { verifyToken, TokenPayload } from '../utils/jwt';
import { UserStatus } from '../../generated/prisma';

export const authenticate = (req: AuthRequest, res: Response, next: NextFunction): void => {
  try {
    const authHeader = req.headers.authorization;
    
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      res.status(401).json({ error: 'Token não fornecido' });
      return;
    }

    const token = authHeader.substring(7);
    const payload: TokenPayload = verifyToken(token);

    if (payload.type === 'user') {
      req.user = {
        id: payload.id,
        email: payload.email,
        role: payload.role,
        status: payload.status,
      };
    } else if (payload.type === 'staff') {
      req.staff = {
        id: payload.id,
        email: payload.email,
        role: payload.role,
      };
    }

    next();
  } catch (error) {
    res.status(401).json({ error: 'Token inválido ou expirado' });
  }
};

export const requireUser = (req: AuthRequest, res: Response, next: NextFunction): void => {
  if (!req.user) {
    res.status(403).json({ error: 'Acesso negado. Usuário requerido.' });
    return;
  }

  // Verifica se o usuário está bloqueado
  if (req.user.status === UserStatus.Bloqueado) {
    // Permite apenas endpoints de pagamento
    if (!req.path.includes('/payment') && !req.path.includes('/subscription')) {
      res.status(403).json({ 
        error: 'Conta bloqueada. Por favor, regularize seu pagamento.',
        status: 'Bloqueado'
      });
      return;
    }
  }

  next();
};

export const requireStaff = (req: AuthRequest, res: Response, next: NextFunction): void => {
  if (!req.staff) {
    res.status(403).json({ error: 'Acesso negado. Permissão de equipe requerida.' });
    return;
  }
  next();
};

export const requireAdmin = (req: AuthRequest, res: Response, next: NextFunction): void => {
  if (!req.staff || req.staff.role !== 'admin') {
    res.status(403).json({ error: 'Acesso negado. Permissão de administrador requerida.' });
    return;
  }
  next();
};
