<?php
/**
 * Model Submission
 * Gerencia submissões de texto para análise
 */

require_once __DIR__ . '/../../config/database.php';

class Submission {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Cria uma nova submissão
     */
    public function create($userId, $configId, $data) {
        $id = Database::generateUuid();

        $stmt = $this->db->prepare(
            "INSERT INTO submissions (id, user_id, config_id, original_text, title, status, ai_provider)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $id,
            $userId,
            $configId,
            $data['original_text'],
            $data['title'],
            $data['status'] ?? 'PROCESSING',
            $data['ai_provider']
        ]);

        return $this->findById($id);
    }

    /**
     * Busca submissão por ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM submissions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Lista submissões de um usuário
     */
    public function findByUserId($userId, $limit = 50, $offset = 0) {
        $stmt = $this->db->prepare(
            "SELECT s.*, c.name as config_name,
                    (SELECT COUNT(*) FROM automations WHERE submission_id = s.id) as automation_count
             FROM submissions s
             LEFT JOIN chatwoot_configs c ON s.config_id = c.id
             WHERE s.user_id = ?
             ORDER BY s.created_at DESC
             LIMIT ? OFFSET ?"
        );

        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Atualiza status da submissão
     */
    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE submissions SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Deleta uma submissão
     */
    public function delete($id, $userId) {
        $stmt = $this->db->prepare("DELETE FROM submissions WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}
