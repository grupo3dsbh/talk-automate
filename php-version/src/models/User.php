<?php
/**
 * Model User
 * Gerencia usuários do sistema
 */

require_once __DIR__ . '/../../config/database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Cria um novo usuário
     */
    public function create($email, $password, $name) {
        // Verifica se email já existe
        if ($this->findByEmail($email)) {
            throw new Exception('Email já cadastrado');
        }

        $id = Database::generateUuid();
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            "INSERT INTO users (id, email, password, name) VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([$id, $email, $hashedPassword, $name]);

        return $this->findById($id);
    }

    /**
     * Busca usuário por ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT id, email, name, created_at, updated_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Busca usuário por email
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT id, email, name, password, created_at, updated_at FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Verifica credenciais de login
     */
    public function verifyCredentials($email, $password) {
        $user = $this->findByEmail($email);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        // Remove o password do retorno
        unset($user['password']);
        return $user;
    }

    /**
     * Atualiza dados do usuário
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $values[] = $data['name'];
        }

        if (isset($data['password'])) {
            $fields[] = "password = ?";
            $values[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $values[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        return $this->findById($id);
    }

    /**
     * Deleta um usuário
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
