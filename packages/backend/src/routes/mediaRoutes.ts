import { Router } from 'express';
import multer from 'multer';
import mediaController from '../controllers/mediaController';
import { authenticate, requireUser } from '../middleware/auth';

const router = Router();
const upload = multer({
  storage: multer.memoryStorage(),
  limits: {
    fileSize: 50 * 1024 * 1024, // 50MB
  },
});

router.use(authenticate);
router.use(requireUser);

router.post('/upload', upload.single('file'), mediaController.uploadMedia.bind(mediaController));

export default router;
