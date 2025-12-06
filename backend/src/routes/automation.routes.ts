import { Router } from 'express';
import { AutomationController } from '../controllers/automation.controller';
import { authMiddleware } from '../middleware/auth.middleware';

const router = Router();
const controller = new AutomationController();

// Todas as rotas requerem autenticação
router.use(authMiddleware);

router.get('/submission/:submissionId', (req, res) => controller.list(req, res));
router.get('/:id', (req, res) => controller.get(req, res));
router.put('/:id', (req, res) => controller.update(req, res));
router.delete('/:id', (req, res) => controller.delete(req, res));
router.post('/:id/send', (req, res) => controller.sendToChatwoot(req, res));
router.post('/submission/:submissionId/send-all', (req, res) => controller.sendAllToChatwoot(req, res));

export default router;
