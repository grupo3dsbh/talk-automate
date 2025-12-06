# 🚀 Quick Start - Deploy no CyberPanel

Guia ultra-rápido para subir o sistema em 10 minutos.

## ⚡ Versão Rápida (Copy & Paste)

### 1️⃣ No CyberPanel - Criar Website

1. Login: `https://seu-servidor.com:8090`
2. **Website** → **Create Website**
3. Domain: `automations.seudominio.com`
4. Criar

### 2️⃣ No Servidor SSH

```bash
# Login SSH
ssh root@seu-servidor.com

# Variáveis (EDITE AQUI)
DOMAIN="automations.seudominio.com"
GIT_URL="https://github.com/seu-usuario/talk-automate.git"
BRANCH="claude/chatwoot-automation-compiler-0147Nk23Rvqp4vad8b4MBxRE"

# Clonar repositório
cd /home/${DOMAIN}
rm -rf public_html/*
git clone -b ${BRANCH} ${GIT_URL} public_html
cd public_html

# Executar script de deploy automático
chmod +x deploy.sh
bash deploy.sh
```

O script `deploy.sh` faz automaticamente:
- ✅ Instala Node.js, PostgreSQL, PM2
- ✅ Instala todas as dependências
- ✅ Cria e executa migrations do banco
- ✅ Compila backend e frontend
- ✅ Inicia aplicação com PM2

### 3️⃣ Configurar .env

```bash
nano /home/${DOMAIN}/public_html/backend/.env
```

**Configure pelo menos:**
```env
DATABASE_URL="postgresql://postgres:senha@localhost:5432/chatwoot_automation"
JWT_SECRET="gere-uma-chave-aleatoria-segura"
GROQ_API_KEY="sua-chave-groq-aqui"
```

**Gerar JWT Secret:**
```bash
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

**Reiniciar após editar .env:**
```bash
pm2 restart chatwoot-automation-api
```

### 4️⃣ Criar Banco de Dados

```bash
sudo -u postgres psql

-- No console do PostgreSQL:
CREATE DATABASE chatwoot_automation;
\q
```

**Executar migrations:**
```bash
cd /home/${DOMAIN}/public_html/backend
npx prisma migrate deploy
```

### 5️⃣ Configurar Proxy no CyberPanel

1. **Website** → **List** → **Manage** (seu domínio)
2. **Rewrite Rules**
3. Cole isto:

```apache
RewriteEngine On
RewriteRule ^api/(.*)$ http://127.0.0.1:3001/api/$1 [P,L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ /index.html [L]
```

4. **Save**

### 6️⃣ Instalar SSL

1. **SSL** → **Issue SSL**
2. Selecione: `automations.seudominio.com`
3. **Issue SSL**

### 7️⃣ Testar

```bash
# Verificar se backend está rodando
pm2 status

# Testar API
curl https://automations.seudominio.com/api/health

# Acesse no navegador
https://automations.seudominio.com
```

---

## 🎯 Comandos Essenciais

```bash
# Ver status
pm2 status

# Ver logs
pm2 logs chatwoot-automation-api

# Reiniciar
pm2 restart chatwoot-automation-api

# Parar
pm2 stop chatwoot-automation-api

# Atualizar código
cd /home/automations.seudominio.com/public_html
git pull
bash deploy.sh
```

---

## 📋 Checklist Mínimo

- [x] Website criado no CyberPanel
- [x] Código clonado do Git
- [x] Script deploy.sh executado
- [x] .env configurado com API key
- [x] Banco de dados criado
- [x] Rewrite rules configuradas
- [x] SSL instalado
- [x] Sistema acessível via HTTPS

---

## 🐛 Problema? Veja isso:

**Backend não sobe:**
```bash
pm2 logs chatwoot-automation-api
```

**API retorna 404:**
```bash
# Verificar rewrite rules
# Testar diretamente: curl http://localhost:3001/api/health
```

**Banco de dados:**
```bash
# Testar conexão
cd /home/automations.seudominio.com/public_html/backend
npx prisma studio
# Abre interface web em localhost:5555
```

---

## 📚 Documentação Completa

- [DEPLOY_CYBERPANEL.md](DEPLOY_CYBERPANEL.md) - Guia detalhado
- [OPENLITESPEED_CONFIG.md](OPENLITESPEED_CONFIG.md) - Configuração do servidor
- [INSTALACAO.md](INSTALACAO.md) - Instalação local
- [COMO_USAR.md](COMO_USAR.md) - Como usar o sistema

---

## 🎉 Pronto!

Acesse: `https://automations.seudominio.com`

1. Crie uma conta
2. Configure o Chatwoot
3. Cole um texto
4. Veja a mágica acontecer! ✨
