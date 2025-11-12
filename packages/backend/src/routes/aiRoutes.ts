import { Router } from 'express';
import aiController from '../controllers/aiController';
import { authenticate, requireUser } from '../middleware/auth';

const router = Router();

router.use(authenticate);
router.use(requireUser);

router.post('/generate-text', aiController.generateText.bind(aiController));
router.post('/generate-image', aiController.generateImage.bind(aiController));

export default router;
