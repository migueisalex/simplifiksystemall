import jwt from 'jsonwebtoken';
import { UserRole, UserStatus, StaffRole } from '../../generated/prisma';

const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key-change-in-production';
const JWT_EXPIRES_IN = '7d';

export interface UserTokenPayload {
  id: string;
  email: string;
  role: UserRole;
  status: UserStatus;
  type: 'user';
}

export interface StaffTokenPayload {
  id: string;
  email: string;
  role: StaffRole;
  type: 'staff';
}

export type TokenPayload = UserTokenPayload | StaffTokenPayload;

export const generateToken = (payload: TokenPayload): string => {
  return jwt.sign(payload, JWT_SECRET, { expiresIn: JWT_EXPIRES_IN });
};

export const verifyToken = (token: string): TokenPayload => {
  try {
    return jwt.verify(token, JWT_SECRET) as TokenPayload;
  } catch (error) {
    throw new Error('Token inválido ou expirado');
  }
};
