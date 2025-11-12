import { Router } from 'express';
import subscriptionController from '../controllers/subscriptionController';
import { authenticate, requireUser } from '../middleware/auth';

const router = Router();

router.use(authenticate);
router.use(requireUser);

router.get('/', subscriptionController.getSubscription.bind(subscriptionController));
router.put('/', subscriptionController.updateSubscription.bind(subscriptionController));
router.post('/downgrade', subscriptionController.downgradeToFreemium.bind(subscriptionController));

export default router;
