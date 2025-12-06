# Chatwoot Automation Compiler - PHP + MySQL 🚀

Sistema SaaS para compilação automática de automações do Chatwoot usando IA.

Cole um texto com informações (FAQ, horários, preços, etc.) e a IA gera automações prontas para o Chatwoot!

## ✨ Funcionalidades

- 🤖 **Análise inteligente de texto** com IA (Groq, ChatGPT, Claude)
- 📝 **Geração automática de automações** para o Chatwoot
- 🏢 **Multi-tenant (SaaS)** - gerencie múltiplas contas
- 🔐 **Autenticação segura** com JWT
- 📊 **Dashboard completo** para gerenciar automações
- 🚀 **Deploy direto** para o Chatwoot com um clique
- ⚡ **Processamento rápido** - automações em segundos

## 📋 Requisitos

- PHP 7.4+ (com extensões: pdo, pdo_mysql, curl, json, mbstring)
- MySQL 5.7+ ou MariaDB 10.2+
- Apache ou Nginx (com mod_rewrite)
- Composer (opcional, sistema não tem dependências externas)

## 🚀 Instalação

### 1. Clone o repositório

```bash
git clone <url-do-repositorio>
cd talk-automate/php-version
```

### 2. Configure o banco de dados

```bash
# Entre no MySQL
mysql -u root -p

# Importe o schema
source database/schema.sql

# Ou execute manualmente:
mysql -u root -p < database/schema.sql
```

### 3. Configure as variáveis de ambiente

```bash
cp .env.example .env
nano .env
```

Edite o `.env` e configure:

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=chatwoot_automation
DB_USER=root
DB_PASS=sua-senha

# JWT Secret (gere uma chave aleatória forte)
JWT_SECRET=sua-chave-secreta-aleatoria-aqui

# API Keys (configure pelo menos uma)
GROQ_API_KEY=sua-groq-api-key
OPENAI_API_KEY=sua-openai-api-key
ANTHROPIC_API_KEY=sua-anthropic-api-key

# Default AI Provider
DEFAULT_AI_PROVIDER=groq
```

### 4. Configure o Apache/Nginx

#### Apache

O `.htaccess` já está configurado. Certifique-se que `mod_rewrite` está ativado:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Configure o Virtual Host apontando para o diretório `public/`:

```apache
<VirtualHost *:80>
    ServerName chatwoot-automation.local
    DocumentRoot /caminho/para/php-version/public

    <Directory /caminho/para/php-version/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name chatwoot-automation.local;
    root /caminho/para/php-version/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. Acesse o sistema

```
http://localhost
ou
http://chatwoot-automation.local
```

## 🎯 Como Usar

### 1. Registre-se

Acesse o sistema e crie uma conta com email e senha.

### 2. Configure o Chatwoot

1. Faça login no Chatwoot/Profluxus Talk
2. Vá em **Perfil** → **Access Token**
3. Copie o token
4. Account ID está na URL: `/app/accounts/{ACCOUNT_ID}`
5. No sistema, vá em **Configurações** e adicione uma nova configuração

### 3. Crie uma Nova Análise

1. Clique em **Nova Análise**
2. Cole o texto com informações (FAQ, horários, preços)
3. Escolha a configuração do Chatwoot
4. Escolha o provider de IA (Groq é grátis!)
5. Clique em **Processar**

### 4. Revise e Envie

1. Aguarde a IA processar (geralmente 5-15 segundos)
2. Revise as automações geradas
3. Edite se necessário
4. Clique em **Enviar para Chatwoot** em cada automação

## 🤖 APIs de IA

### Groq (Grátis e Rápido) ⭐ Recomendado

1. Acesse: https://console.groq.com
2. Crie uma conta (grátis)
3. Gere uma API key
4. Cole no `.env`: `GROQ_API_KEY="sua-key"`

### OpenAI (Pago)

1. Acesse: https://platform.openai.com
2. Adicione créditos ($5 mínimo)
3. Gere uma API key
4. Cole no `.env`: `OPENAI_API_KEY="sua-key"`

### Anthropic Claude (Pago)

1. Acesse: https://console.anthropic.com
2. Adicione créditos ($5 mínimo)
3. Gere uma API key
4. Cole no `.env`: `ANTHROPIC_API_KEY="sua-key"`

## 📁 Estrutura do Projeto

```
php-version/
├── config/
│   ├── config.php         # Configurações gerais
│   └── database.php       # Conexão PDO
├── src/
│   ├── models/            # Models (User, Submission, etc)
│   ├── services/          # Serviços (IA, Chatwoot)
│   └── middleware/        # Auth, JWT
├── public/
│   ├── index.php          # Interface web (SPA)
│   ├── .htaccess          # Rewrite rules
│   └── api/               # APIs REST
│       ├── auth.php
│       ├── chatwoot-configs.php
│       ├── submissions.php
│       └── automations.php
├── database/
│   └── schema.sql         # Schema MySQL
├── .env.example
└── README.md
```

## 🔌 API REST

### Autenticação

```bash
# Registrar
POST /api/auth/register
Body: { "name": "...", "email": "...", "password": "..." }

# Login
POST /api/auth/login
Body: { "email": "...", "password": "..." }
Response: { "user": {...}, "token": "..." }
```

### Configurações do Chatwoot

```bash
# Listar
GET /api/chatwoot-configs
Headers: Authorization: Bearer {token}

# Criar
POST /api/chatwoot-configs
Body: {
  "name": "...",
  "chatwoot_url": "...",
  "account_id": "...",
  "api_access_token": "..."
}
```

### Submissões

```bash
# Criar e processar
POST /api/submissions
Body: {
  "config_id": "...",
  "title": "...",
  "original_text": "...",
  "ai_provider": "groq"
}

# Listar
GET /api/submissions

# Detalhes (com automações)
GET /api/submissions/{id}
```

### Automações

```bash
# Enviar para Chatwoot
POST /api/automations/{id}/send

# Atualizar
PUT /api/automations/{id}
Body: { "name": "...", "description": "..." }
```

## 📊 Banco de Dados

### Tabelas

- `users` - Usuários do sistema
- `chatwoot_configs` - Configurações do Chatwoot
- `submissions` - Textos submetidos
- `automations` - Automações geradas

## 🛠️ Desenvolvimento

### Debug

No `.env`, configure:

```env
APP_ENV=development
```

Isso habilitará mensagens de erro detalhadas.

### Logs

Erros são exibidos no navegador (modo desenvolvimento) ou no error_log do Apache/Nginx.

## 🚀 Deploy em Produção

### 1. Configurações de Segurança

```env
APP_ENV=production
JWT_SECRET=chave-super-secreta-e-longa
```

### 2. Permissões

```bash
chown -R www-data:www-data php-version/
chmod -R 755 php-version/
```

### 3. HTTPS

Configure SSL/TLS no Apache/Nginx (use Let's Encrypt).

### 4. Banco de Dados

Use credenciais seguras e considere usar um banco remoto gerenciado.

## 📝 Exemplo de Texto para Testar

```
Horário de funcionamento do clube:
Terça a Sexta: 9h00 às 18h00
Sábado, Domingo e Feriado: 8h00 às 18h00

Horário de funcionamento do escritório:
Segunda a Sexta: 8h00 às 17h30
Sábado: 8h00 às 11h30

Endereço escritório:
Rua dos Goitacazes 1596, Barro Preto, Belo Horizonte
5º andar - Sala 501

Day Use:
R$ 180,00 por pessoa (não sócio)
R$ 150,00 por pessoa (sócio em dia)
Crianças menores de 06 não pagam
```

## 🤝 Suporte

Encontrou um bug? Tem uma sugestão?
Abra uma issue no GitHub!

## 📄 Licença

MIT

---

**Desenvolvido com ❤️ em PHP puro - Sem frameworks, sem dependências!**
