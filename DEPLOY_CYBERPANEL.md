# Deploy no CyberPanel via Git

Guia completo para fazer deploy do sistema no seu domínio usando CyberPanel.

## 📋 Pré-requisitos

- Servidor com CyberPanel instalado
- Domínio configurado (ex: `automations.seudominio.com`)
- Node.js 18+ instalado no servidor
- PostgreSQL instalado no servidor
- Acesso SSH ao servidor

---

## 🚀 Passo a Passo

### 1. Preparar o Servidor

#### SSH no servidor:
```bash
ssh root@seu-servidor.com
```

#### Instalar Node.js 18+ (se necessário):
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
apt-get install -y nodejs
node --version  # Verificar versão
```

#### Instalar PostgreSQL (se necessário):
```bash
apt-get update
apt-get install -y postgresql postgresql-contrib
systemctl start postgresql
systemctl enable postgresql
```

#### Instalar PM2 (gerenciador de processos):
```bash
npm install -g pm2
```

---

### 2. Criar Website no CyberPanel

1. Acesse o **CyberPanel**: `https://seu-servidor.com:8090`
2. Login com suas credenciais
3. Vá em: **Website** → **Create Website**
4. Preencha:
   - **Domain Name**: `automations.seudominio.com`
   - **Email**: seu@email.com
   - **Package**: Selecione um package
   - **PHP**: Selecione uma versão (não vamos usar, mas é obrigatório)
5. Clique em **Create Website**

---

### 3. Configurar Git no CyberPanel

1. No CyberPanel, vá em: **Git** → **Git Manager**
2. Selecione seu domínio: `automations.seudominio.com`
3. Preencha:
   - **Repository URL**: URL do seu repositório
   - **Branch**: `claude/chatwoot-automation-compiler-0147Nk23Rvqp4vad8b4MBxRE`
   - **Path**: Deixe em branco (usará `/home/automations.seudominio.com/public_html`)
4. Clique em **Setup Git**

Ou via SSH (alternativa):
```bash
cd /home/automations.seudominio.com
rm -rf public_html/*  # Limpar diretório
git clone -b claude/chatwoot-automation-compiler-0147Nk23Rvqp4vad8b4MBxRE <URL_DO_REPOSITORIO> public_html
cd public_html
```

---

### 4. Configurar Banco de Dados PostgreSQL

#### Criar banco e usuário:
```bash
sudo -u postgres psql

-- No console do PostgreSQL:
CREATE DATABASE chatwoot_automation;
CREATE USER chatwoot_user WITH PASSWORD 'senha_forte_aqui';
GRANT ALL PRIVILEGES ON DATABASE chatwoot_automation TO chatwoot_user;
\q
```

#### Permitir conexões locais:
```bash
# Editar pg_hba.conf
nano /etc/postgresql/*/main/pg_hba.conf

# Adicionar esta linha (se não existir):
local   all             all                                     md5

# Reiniciar PostgreSQL
systemctl restart postgresql
```

---

### 5. Instalar Dependências

```bash
cd /home/automations.seudominio.com/public_html

# Instalar dependências do backend
cd backend
npm install --production

# Instalar dependências do frontend
cd ../frontend
npm install

# Voltar para raiz
cd ..
```

---

### 6. Configurar Variáveis de Ambiente

```bash
cd /home/automations.seudominio.com/public_html/backend

# Copiar arquivo de exemplo
cp .env.example .env

# Editar arquivo .env
nano .env
```

**Configure o `.env`:**
```env
# Database
DATABASE_URL="postgresql://chatwoot_user:senha_forte_aqui@localhost:5432/chatwoot_automation?schema=public"

# JWT Secret (gere uma chave segura)
JWT_SECRET="chave-super-secreta-gerada-aleatoriamente"

# Server
PORT=3001
NODE_ENV=production

# AI Provider (configure pelo menos um)
GROQ_API_KEY="sua-chave-groq"
# ou
OPENAI_API_KEY="sua-chave-openai"
# ou
ANTHROPIC_API_KEY="sua-chave-anthropic"

DEFAULT_AI_PROVIDER="groq"
```

**Gerar JWT Secret seguro:**
```bash
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

---

### 7. Executar Migrations do Prisma

```bash
cd /home/automations.seudominio.com/public_html/backend

# Gerar Prisma Client
npx prisma generate

# Executar migrations
npx prisma migrate deploy
```

---

### 8. Build do Frontend

```bash
cd /home/automations.seudominio.com/public_html/frontend

# Build de produção
npm run build

# Isso cria a pasta 'dist' com arquivos estáticos
```

---

### 9. Build do Backend

```bash
cd /home/automations.seudominio.com/public_html/backend

# Compilar TypeScript
npm run build

# Isso cria a pasta 'dist' com código JavaScript
```

---

### 10. Configurar PM2 para rodar o Backend

```bash
cd /home/automations.seudominio.com/public_html

# Criar arquivo ecosystem.config.js
nano ecosystem.config.js
```

**Conteúdo do `ecosystem.config.js`:**
```javascript
module.exports = {
  apps: [{
    name: 'chatwoot-automation-api',
    cwd: '/home/automations.seudominio.com/public_html/backend',
    script: 'dist/index.js',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      PORT: 3001
    },
    error_file: '/home/automations.seudominio.com/logs/api-error.log',
    out_file: '/home/automations.seudominio.com/logs/api-out.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss'
  }]
};
```

**Iniciar com PM2:**
```bash
# Criar pasta de logs
mkdir -p /home/automations.seudominio.com/logs

# Iniciar aplicação
pm2 start ecosystem.config.js

# Salvar configuração do PM2
pm2 save

# Configurar PM2 para iniciar no boot
pm2 startup
# Execute o comando que aparecer

# Verificar status
pm2 status
pm2 logs chatwoot-automation-api
```

---

### 11. Configurar OpenLiteSpeed/LiteSpeed

Agora precisamos configurar o servidor web para:
- Servir o frontend (arquivos estáticos)
- Fazer proxy reverso para o backend (API)

#### Opção 1: Via CyberPanel (Interface)

1. Vá em **Website** → **List Websites**
2. Clique em **Manage** no seu domínio
3. Vá em **Rewrite Rules**

**Adicione estas regras:**
```apache
RewriteEngine On

# Proxy para API (backend)
RewriteRule ^/api/(.*)$ http://127.0.0.1:3001/api/$1 [P,L]

# Servir frontend
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ /index.html [L]
```

4. Clique em **Save**

#### Opção 2: Via SSH (Configuração manual)

```bash
# Editar vhost config
nano /usr/local/lsws/conf/vhosts/automations.seudominio.com/vhost.conf
```

**Adicione dentro do bloco `<VirtualHost>`:**
```xml
<context uri="/api/">
  type                    proxy
  handler                 http://127.0.0.1:3001
  addDefaultCharset       off
</context>

rewrite  {
  enable                  1
  autoLoadHtaccess        1

  rules                   <<<END_rules
  RewriteEngine On

  # Proxy para API
  RewriteRule ^/api/(.*)$ http://127.0.0.1:3001/api/$1 [P,L]

  # Servir frontend
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^ /index.html [L]
  END_rules
}

<context uri="/">
  location                /home/automations.seudominio.com/public_html/frontend/dist
  allowBrowse             0
  indexFiles              index.html
</context>
```

**Reiniciar OpenLiteSpeed:**
```bash
systemctl restart lsws
```

---

### 12. Copiar Frontend Build para public_html

```bash
# Copiar arquivos do build do frontend para a raiz do public_html
cd /home/automations.seudominio.com/public_html
cp -r frontend/dist/* .

# Ou criar um link simbólico
ln -s /home/automations.seudominio.com/public_html/frontend/dist /home/automations.seudominio.com/public_html/app
```

---

### 13. Configurar SSL (HTTPS)

1. No CyberPanel, vá em: **SSL** → **Issue SSL**
2. Selecione seu domínio: `automations.seudominio.com`
3. Clique em **Issue SSL**

Ou via comando:
```bash
/root/.acme.sh/acme.sh --issue -d automations.seudominio.com -w /home/automations.seudominio.com/public_html
```

---

### 14. Configurar Firewall

```bash
# Permitir portas necessárias
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 8090/tcp  # CyberPanel
ufw reload
```

---

### 15. Testar o Sistema

1. Acesse: `https://automations.seudominio.com`
2. Você deve ver a tela de login
3. Crie uma conta
4. Configure o Chatwoot
5. Teste a criação de automações

---

## 🔄 Atualizar o Sistema (Pull de novas versões)

```bash
cd /home/automations.seudominio.com/public_html

# Pull do git
git pull origin claude/chatwoot-automation-compiler-0147Nk23Rvqp4vad8b4MBxRE

# Atualizar dependências backend
cd backend
npm install --production
npx prisma generate
npx prisma migrate deploy
npm run build

# Atualizar frontend
cd ../frontend
npm install
npm run build

# Copiar novo build
cd ..
cp -r frontend/dist/* .

# Reiniciar API
pm2 restart chatwoot-automation-api

# Verificar logs
pm2 logs chatwoot-automation-api
```

---

## 🐛 Troubleshooting

### Backend não inicia:
```bash
# Ver logs
pm2 logs chatwoot-automation-api

# Verificar porta
netstat -tlnp | grep 3001

# Testar manualmente
cd /home/automations.seudominio.com/public_html/backend
node dist/index.js
```

### Erro de conexão com banco:
```bash
# Testar conexão PostgreSQL
psql -U chatwoot_user -d chatwoot_automation -h localhost

# Verificar se PostgreSQL está rodando
systemctl status postgresql

# Ver logs do PostgreSQL
tail -f /var/log/postgresql/postgresql-*-main.log
```

### Frontend retorna 404:
```bash
# Verificar se arquivos do build existem
ls -la /home/automations.seudominio.com/public_html/frontend/dist

# Verificar permissões
chmod -R 755 /home/automations.seudominio.com/public_html
chown -R nobody:nogroup /home/automations.seudominio.com/public_html
```

### API não responde em /api:
- Verifique as rewrite rules no OpenLiteSpeed
- Verifique se PM2 está rodando: `pm2 status`
- Teste diretamente: `curl http://localhost:3001/health`

---

## 📊 Monitoramento

```bash
# Status do PM2
pm2 status

# Logs em tempo real
pm2 logs chatwoot-automation-api

# Monitorar recursos
pm2 monit

# Ver dashboard web do PM2
pm2 install pm2-server-monit
# Acesse: http://seu-servidor.com:9615
```

---

## 🔒 Segurança

1. **Firewall**: Certifique-se que apenas portas 80, 443 e 8090 estão abertas
2. **PostgreSQL**: Não permita conexões externas
3. **JWT Secret**: Use uma chave forte e única
4. **API Keys**: Nunca commite as keys no Git
5. **HTTPS**: Sempre use SSL em produção

---

## ✅ Checklist Final

- [ ] Node.js 18+ instalado
- [ ] PostgreSQL instalado e rodando
- [ ] PM2 instalado globalmente
- [ ] Repositório clonado
- [ ] Dependências instaladas (backend e frontend)
- [ ] `.env` configurado corretamente
- [ ] Migrations executadas
- [ ] Backend buildado (`npm run build`)
- [ ] Frontend buildado (`npm run build`)
- [ ] PM2 configurado e rodando
- [ ] OpenLiteSpeed configurado (proxy + rewrite rules)
- [ ] SSL instalado
- [ ] Sistema acessível via HTTPS
- [ ] Login funcionando
- [ ] Criação de automações funcionando

---

## 📞 Suporte

Se encontrar problemas:
1. Verifique os logs: `pm2 logs`
2. Teste conexão com banco: `psql -U chatwoot_user -d chatwoot_automation`
3. Verifique se backend está rodando: `curl http://localhost:3001/health`
4. Verifique configurações do OpenLiteSpeed

---

**Desenvolvido com ❤️ - Deploy facilitado!**
