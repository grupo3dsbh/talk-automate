<?php
/**
 * Model ChatwootConfig
 * Gerencia configurações do Chatwoot por usuário
 */

require_once __DIR__ . '/../../config/database.php';

class ChatwootConfig {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Cria uma nova configuração
     */
    public function create($userId, $data) {
        $id = Database::generateUuid();

        $stmt = $this->db->prepare(
            "INSERT INTO chatwoot_configs (id, user_id, chatwoot_url, account_id, api_access_token, name, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $id,
            $userId,
            $data['chatwoot_url'],
            $data['account_id'],
            $data['api_access_token'],
            $data['name'],
            $data['is_active'] ?? 1
        ]);

        return $this->findById($id);
    }

    /**
     * Busca configuração por ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM chatwoot_configs WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Lista todas as configurações de um usuário
     */
    public function findByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM chatwoot_configs WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Atualiza uma configuração
     */
    public function update($id, $userId, $data) {
        $fields = [];
        $values = [];

        $allowedFields = ['chatwoot_url', 'account_id', 'api_access_token', 'name', 'is_active'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $values[] = $id;
        $values[] = $userId;

        $sql = "UPDATE chatwoot_configs SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        return $this->findById($id);
    }

    /**
     * Deleta uma configuração
     */
    public function delete($id, $userId) {
        $stmt = $this->db->prepare("DELETE FROM chatwoot_configs WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}
