import { Router } from 'express';
import { ChatwootConfigController } from '../controllers/chatwoot-config.controller';
import { authMiddleware } from '../middleware/auth.middleware';

const router = Router();
const controller = new ChatwootConfigController();

// Todas as rotas requerem autenticação
router.use(authMiddleware);

router.get('/', (req, res) => controller.list(req, res));
router.post('/', (req, res) => controller.create(req, res));
router.put('/:id', (req, res) => controller.update(req, res));
router.delete('/:id', (req, res) => controller.delete(req, res));
router.post('/:id/test', (req, res) => controller.testConnection(req, res));

export default router;
