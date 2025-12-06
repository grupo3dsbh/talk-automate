-- Schema SQL para Chatwoot Automation Compiler
-- Baseado no schema.prisma

-- Enum types
CREATE TYPE "SubmissionStatus" AS ENUM ('PROCESSING', 'COMPLETED', 'FAILED');
CREATE TYPE "AutomationStatus" AS ENUM ('DRAFT', 'SENT', 'ERROR');

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS "users" (
  "id" TEXT PRIMARY KEY,
  "email" TEXT UNIQUE NOT NULL,
  "password" TEXT NOT NULL,
  "name" TEXT NOT NULL,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de configurações do Chatwoot
CREATE TABLE IF NOT EXISTS "chatwoot_configs" (
  "id" TEXT PRIMARY KEY,
  "userId" TEXT NOT NULL,
  "chatwootUrl" TEXT NOT NULL,
  "accountId" TEXT NOT NULL,
  "apiAccessToken" TEXT NOT NULL,
  "name" TEXT NOT NULL,
  "isActive" BOOLEAN NOT NULL DEFAULT true,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY ("userId") REFERENCES "users"("id") ON DELETE CASCADE
);

-- Tabela de submissões de texto
CREATE TABLE IF NOT EXISTS "submissions" (
  "id" TEXT PRIMARY KEY,
  "userId" TEXT NOT NULL,
  "configId" TEXT NOT NULL,
  "originalText" TEXT NOT NULL,
  "title" TEXT NOT NULL,
  "status" "SubmissionStatus" NOT NULL DEFAULT 'PROCESSING',
  "aiProvider" TEXT NOT NULL,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY ("userId") REFERENCES "users"("id") ON DELETE CASCADE,
  FOREIGN KEY ("configId") REFERENCES "chatwoot_configs"("id") ON DELETE CASCADE
);

-- Tabela de automações geradas
CREATE TABLE IF NOT EXISTS "automations" (
  "id" TEXT PRIMARY KEY,
  "submissionId" TEXT NOT NULL,
  "name" TEXT NOT NULL,
  "description" TEXT NOT NULL,
  "eventName" TEXT NOT NULL,
  "conditions" JSONB NOT NULL,
  "actions" JSONB NOT NULL,
  "status" "AutomationStatus" NOT NULL DEFAULT 'DRAFT',
  "active" BOOLEAN NOT NULL DEFAULT true,
  "chatwootRuleId" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "sentAt" TIMESTAMP(3),
  FOREIGN KEY ("submissionId") REFERENCES "submissions"("id") ON DELETE CASCADE
);

-- Índices para melhorar performance
CREATE INDEX IF NOT EXISTS "idx_chatwoot_configs_userId" ON "chatwoot_configs"("userId");
CREATE INDEX IF NOT EXISTS "idx_submissions_userId" ON "submissions"("userId");
CREATE INDEX IF NOT EXISTS "idx_submissions_configId" ON "submissions"("configId");
CREATE INDEX IF NOT EXISTS "idx_automations_submissionId" ON "automations"("submissionId");
