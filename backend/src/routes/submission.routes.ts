import { Router } from 'express';
import { SubmissionController } from '../controllers/submission.controller';
import { authMiddleware } from '../middleware/auth.middleware';

const router = Router();
const controller = new SubmissionController();

// Todas as rotas requerem autenticação
router.use(authMiddleware);

router.get('/', (req, res) => controller.list(req, res));
router.get('/:id', (req, res) => controller.get(req, res));
router.post('/', (req, res) => controller.create(req, res));
router.delete('/:id', (req, res) => controller.delete(req, res));

export default router;
