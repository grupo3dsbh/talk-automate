<?php
/**
 * Model Automation
 * Gerencia automações geradas pela IA
 */

require_once __DIR__ . '/../../config/database.php';

class Automation {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Cria uma nova automação
     */
    public function create($submissionId, $data) {
        $id = Database::generateUuid();

        $stmt = $this->db->prepare(
            "INSERT INTO automations (id, submission_id, name, description, event_name, conditions, actions, status, active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $id,
            $submissionId,
            $data['name'],
            $data['description'],
            $data['event_name'] ?? 'message_created',
            json_encode($data['conditions']),
            json_encode($data['actions']),
            $data['status'] ?? 'DRAFT',
            $data['active'] ?? 1
        ]);

        return $this->findById($id);
    }

    /**
     * Busca automação por ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM automations WHERE id = ?");
        $stmt->execute([$id]);
        $automation = $stmt->fetch();

        if ($automation) {
            $automation['conditions'] = json_decode($automation['conditions'], true);
            $automation['actions'] = json_decode($automation['actions'], true);
        }

        return $automation;
    }

    /**
     * Lista automações de uma submissão
     */
    public function findBySubmissionId($submissionId) {
        $stmt = $this->db->prepare("SELECT * FROM automations WHERE submission_id = ? ORDER BY created_at ASC");
        $stmt->execute([$submissionId]);
        $automations = $stmt->fetchAll();

        foreach ($automations as &$automation) {
            $automation['conditions'] = json_decode($automation['conditions'], true);
            $automation['actions'] = json_decode($automation['actions'], true);
        }

        return $automations;
    }

    /**
     * Atualiza uma automação
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];

        $allowedFields = ['name', 'description', 'event_name', 'active', 'status'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (isset($data['conditions'])) {
            $fields[] = "conditions = ?";
            $values[] = json_encode($data['conditions']);
        }

        if (isset($data['actions'])) {
            $fields[] = "actions = ?";
            $values[] = json_encode($data['actions']);
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $values[] = $id;
        $sql = "UPDATE automations SET " . implode(', ', $fields) . " WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        return $this->findById($id);
    }

    /**
     * Marca automação como enviada ao Chatwoot
     */
    public function markAsSent($id, $chatwootRuleId) {
        $stmt = $this->db->prepare(
            "UPDATE automations SET status = 'SENT', chatwoot_rule_id = ?, sent_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$chatwootRuleId, $id]);
    }

    /**
     * Marca automação com erro
     */
    public function markAsError($id) {
        $stmt = $this->db->prepare("UPDATE automations SET status = 'ERROR' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Deleta uma automação
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM automations WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
