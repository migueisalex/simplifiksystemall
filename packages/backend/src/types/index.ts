import { Request } from 'express';
import { UserRole, UserStatus, StaffRole } from '../../generated/prisma';

export interface AuthRequest extends Request {
  user?: {
    id: string;
    email: string;
    role: UserRole;
    status: UserStatus;
  };
  staff?: {
    id: string;
    email: string;
    role: StaffRole;
  };
}

export interface UsageLimits {
  postCount: number;
  aiTextCount: number;
  aiImageCount: number;
}

export interface PackageLimits {
  [key: number]: UsageLimits;
}

export const PACKAGE_LIMITS: PackageLimits = {
  0: { // Freemium
    postCount: 10,
    aiTextCount: 5,
    aiImageCount: 3,
  },
  1: { // Pacote 1
    postCount: 50,
    aiTextCount: 30,
    aiImageCount: 20,
  },
  2: { // Pacote 2
    postCount: 150,
    aiTextCount: 100,
    aiImageCount: 75,
  },
  3: { // Pacote 3
    postCount: -1, // Ilimitado
    aiTextCount: -1,
    aiImageCount: -1,
  },
};

export interface MediaUploadResult {
  url: string;
  mimeType: string;
}
