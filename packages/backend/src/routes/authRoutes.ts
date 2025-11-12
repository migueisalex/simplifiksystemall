import { Router } from 'express';
import authController from '../controllers/authController';
import { auditStaffLogin } from '../middleware/audit';

const router = Router();

router.post('/register', authController.register.bind(authController));
router.post('/verify-email', authController.verifyEmail.bind(authController));
router.post('/login', authController.login.bind(authController), auditStaffLogin);

export default router;
