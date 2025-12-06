-- Schema MySQL para Chatwoot Automation Compiler
-- Versão PHP + MySQL

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS chatwoot_automation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE chatwoot_automation;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de configurações do Chatwoot
CREATE TABLE IF NOT EXISTS chatwoot_configs (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    chatwoot_url VARCHAR(500) NOT NULL,
    account_id VARCHAR(100) NOT NULL,
    api_access_token VARCHAR(500) NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de submissões de texto
CREATE TABLE IF NOT EXISTS submissions (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    config_id VARCHAR(36) NOT NULL,
    original_text LONGTEXT NOT NULL,
    title VARCHAR(255) NOT NULL,
    status ENUM('PROCESSING', 'COMPLETED', 'FAILED') DEFAULT 'PROCESSING',
    ai_provider VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (config_id) REFERENCES chatwoot_configs(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_config_id (config_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de automações geradas
CREATE TABLE IF NOT EXISTS automations (
    id VARCHAR(36) PRIMARY KEY,
    submission_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    event_name VARCHAR(100) NOT NULL,
    conditions JSON NOT NULL,
    actions JSON NOT NULL,
    status ENUM('DRAFT', 'SENT', 'ERROR') DEFAULT 'DRAFT',
    active TINYINT(1) DEFAULT 1,
    chatwoot_rule_id VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
    INDEX idx_submission_id (submission_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
