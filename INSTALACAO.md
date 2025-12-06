# Guia de Instalação - Chatwoot Automation Compiler

## Pré-requisitos

- Node.js 18+ e npm
- PostgreSQL 14+
- Git

## Passo 1: Clonar o repositório

```bash
git clone <url-do-repositorio>
cd talk-automate
```

## Passo 2: Instalar dependências

### Raiz do projeto
```bash
npm install
```

### Backend
```bash
cd backend
npm install
```

### Frontend
```bash
cd ../frontend
npm install
```

## Passo 3: Configurar banco de dados PostgreSQL

### Criar banco de dados

```bash
# Entre no PostgreSQL
psql -U postgres

# Crie o banco de dados
CREATE DATABASE chatwoot_automation;

# Saia do PostgreSQL
\q
```

## Passo 4: Configurar variáveis de ambiente

### Backend (.env)

Copie o arquivo de exemplo e edite:

```bash
cd backend
cp .env.example .env
```

Edite o arquivo `.env`:

```env
# Database
DATABASE_URL="postgresql://postgres:sua_senha@localhost:5432/chatwoot_automation?schema=public"

# JWT Secret (gere uma chave aleatória segura)
JWT_SECRET="sua-chave-secreta-super-segura-aqui"

# Server
PORT=3001
NODE_ENV=development

# AI Providers (configure pelo menos um)
GROQ_API_KEY="sua-chave-groq"
OPENAI_API_KEY="sua-chave-openai"
ANTHROPIC_API_KEY="sua-chave-anthropic"

# Default AI Provider (groq, openai, ou anthropic)
DEFAULT_AI_PROVIDER="groq"
```

## Passo 5: Executar migrations do Prisma

```bash
cd backend
npx prisma generate
npx prisma migrate dev --name init
```

## Passo 6: Iniciar o sistema

### Modo desenvolvimento (ambos juntos)

Na raiz do projeto:
```bash
npm run dev
```

Isso iniciará:
- Backend: http://localhost:3001
- Frontend: http://localhost:3000

### Modo desenvolvimento (separado)

Terminal 1 - Backend:
```bash
cd backend
npm run dev
```

Terminal 2 - Frontend:
```bash
cd frontend
npm run dev
```

## Passo 7: Acessar o sistema

Abra o navegador em: **http://localhost:3000**

### Primeira utilização

1. Clique em "Cadastre-se"
2. Crie sua conta
3. Faça login
4. Vá em "Configurações" e adicione suas credenciais do Chatwoot
5. Vá em "Nova Análise" para começar a usar!

## Obter API Keys

### Groq (Recomendado - Grátis)
1. Acesse: https://console.groq.com
2. Crie uma conta
3. Vá em "API Keys"
4. Crie uma nova key

### OpenAI
1. Acesse: https://platform.openai.com
2. Crie uma conta
3. Vá em "API Keys"
4. Crie uma nova key (requer créditos)

### Anthropic (Claude)
1. Acesse: https://console.anthropic.com
2. Crie uma conta
3. Vá em "API Keys"
4. Crie uma new key (requer créditos)

## Obter credenciais do Chatwoot

1. Faça login no seu Chatwoot (Profluxus Talk)
2. Vá em "Perfil" → "Access Token"
3. Copie o token
4. O Account ID está na URL: `https://app.chatwoot.com/app/accounts/{ACCOUNT_ID}`

## Produção

### Build

```bash
npm run build
```

### Iniciar

```bash
cd backend
npm start
```

Configure um processo manager como PM2:

```bash
npm install -g pm2
pm2 start dist/index.js --name chatwoot-automation
```

## Troubleshooting

### Erro de conexão com banco de dados
- Verifique se o PostgreSQL está rodando
- Verifique a string de conexão no `.env`
- Verifique usuário e senha

### Erro "Cannot find module"
- Execute `npm install` novamente
- Limpe node_modules: `rm -rf node_modules && npm install`

### Prisma não encontra o banco
- Execute `npx prisma generate`
- Execute `npx prisma migrate dev`

### IA retorna erro
- Verifique se a API key está correta
- Verifique se o provider está configurado
- Tente outro provider

## Suporte

Em caso de problemas, abra uma issue no GitHub.
