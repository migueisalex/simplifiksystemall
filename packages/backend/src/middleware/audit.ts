import { Response, NextFunction } from 'express';
import { AuthRequest } from '../types';
import prisma from '../config/database';

export const auditStaffLogin = async (
  req: AuthRequest,
  res: Response,
  next: NextFunction
): Promise<void> => {
  if (req.staff) {
    try {
      const ipAddress = req.ip || req.socket.remoteAddress || 'unknown';
      
      await prisma.auditLog.create({
        data: {
          staffMemberId: req.staff.id,
          actionType: 'STAFF_LOGIN',
          ipAddress,
          details: {
            email: req.staff.email,
            role: req.staff.role,
            timestamp: new Date().toISOString(),
          },
        },
      });
    } catch (error) {
      console.error('Erro ao registrar auditoria de login:', error);
    }
  }
  next();
};

export const auditCriticalAction = async (
  staffMemberId: string,
  actionType: string,
  targetUserId: string | null,
  before: any,
  after: any,
  ipAddress?: string
): Promise<void> => {
  try {
    await prisma.auditLog.create({
      data: {
        staffMemberId,
        actionType,
        targetUserId,
        ipAddress,
        details: {
          before,
          after,
          timestamp: new Date().toISOString(),
        },
      },
    });
  } catch (error) {
    console.error('Erro ao registrar auditoria de ação crítica:', error);
  }
};
