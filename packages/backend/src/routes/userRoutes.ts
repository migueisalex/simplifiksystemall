import { Router, Response } from 'express';
import { authenticate, requireUser } from '../middleware/auth';
import { AuthRequest } from '../types';

const router = Router();

// Aplica o middleware de autenticação para todas as rotas de usuário
router.use(authenticate);
router.use(requireUser);

// Rota de teste protegida para obter informações do usuário logado
router.get('/me', (req: AuthRequest, res: Response) => {
  // O req.user é populado pelo middleware 'authenticate'
  if (req.user) {
    res.status(200).json({
      message: 'Autenticação bem-sucedida. Informações do usuário:',
      user: req.user,
    });
  } else {
    // Caso o requireUser falhe por algum motivo (embora não deva acontecer aqui)
    res.status(401).json({ error: 'Usuário não autenticado.' });
  }
});

export default router;
