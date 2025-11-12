import { Router } from 'express';
import postController from '../controllers/postController';
import { authenticate, requireUser } from '../middleware/auth';

const router = Router();

router.use(authenticate);
router.use(requireUser);

router.get('/', postController.getPosts.bind(postController));
router.post('/', postController.createPost.bind(postController));
router.put('/:id', postController.updatePost.bind(postController));
router.delete('/:id', postController.deletePost.bind(postController));
router.post('/:id/clone', postController.clonePost.bind(postController));

export default router;
