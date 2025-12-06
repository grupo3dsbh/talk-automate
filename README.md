# Chatwoot Automation Compiler 🤖

Sistema SaaS para compilação automática de automações do Chatwoot usando IA.

Cole um texto com informações (FAQ, horários, preços, etc.) e a IA gera automações prontas para o Chatwoot!

## ✨ Funcionalidades

- 🤖 **Análise inteligente de texto** com IA (Groq, ChatGPT, Claude)
- 📝 **Geração automática de automações** para o Chatwoot
- 🏢 **Multi-tenant (SaaS)** - gerencie múltiplas contas
- 🔐 **Autenticação segura** e gerenciamento de usuários
- 📊 **Dashboard completo** para gerenciar automações
- 🚀 **Deploy direto** para o Chatwoot com um clique
- ⚡ **Processamento rápido** - automações em segundos

## 🎯 Como Funciona

1. **Login** → Faça login ou crie sua conta
2. **Configurar** → Adicione suas credenciais do Chatwoot
3. **Colar texto** → Cole informações (FAQ, horários, preços)
4. **IA processa** → Análise automática e geração de automações
5. **Revisar** → Veja as automações sugeridas
6. **Enviar** → Deploy direto para o Chatwoot!

### Exemplo de Entrada:
```
Horário de funcionamento:
Segunda a Sexta: 9h às 18h

Endereço:
Rua dos Goitacazes 1596, Belo Horizonte

Preços Day Use:
R$ 180,00 (não sócio)
R$ 150,00 (sócio)
```

### Automações Geradas:
- ✅ Responder sobre horário quando cliente perguntar
- ✅ Informar endereço automaticamente
- ✅ Enviar tabela de preços quando solicitado

## 📋 Pré-requisitos

- Node.js 18+
- PostgreSQL 14+
- API Key de pelo menos uma IA (Groq, OpenAI ou Claude)
- Conta no Chatwoot com API Access Token

## 🚀 Início Rápido

### 1. Clone o repositório
```bash
git clone <url-do-repositorio>
cd talk-automate
```

### 2. Suba o banco de dados com Docker
```bash
docker-compose up -d
```

### 3. Instale as dependências
```bash
npm install
cd backend && npm install
cd ../frontend && npm install
cd ..
```

### 4. Configure as variáveis de ambiente
```bash
cd backend
cp .env.example .env
# Edite o .env com suas API keys
```

### 5. Execute as migrations
```bash
cd backend
npx prisma generate
npx prisma migrate dev
```

### 6. Inicie o sistema
```bash
# Na raiz do projeto
npm run dev
```

Acesse: **http://localhost:3000**

## 📚 Documentação Completa

- [📖 Guia de Instalação Completo](INSTALACAO.md)
- [📘 Como Usar o Sistema](COMO_USAR.md)
- [📝 Exemplo de Texto para Testar](EXEMPLO_TEXTO.md)

## 🏗️ Stack Tecnológica

### Backend
- **Node.js** + Express + TypeScript
- **Prisma ORM** + PostgreSQL
- **JWT** Authentication
- **Groq / OpenAI / Anthropic** (IA)
- **Chatwoot API** Integration

### Frontend
- **React** + Vite + TypeScript
- **Tailwind CSS**
- React Router
- Axios

## 📁 Estrutura do Projeto

```
talk-automate/
├── backend/                 # API Backend
│   ├── src/
│   │   ├── controllers/     # Controllers de rotas
│   │   ├── services/        # Serviços (IA, Chatwoot)
│   │   ├── routes/          # Definição de rotas
│   │   ├── middleware/      # Middlewares (auth)
│   │   ├── types/           # TypeScript types
│   │   └── index.ts         # Entry point
│   └── prisma/
│       └── schema.prisma    # Schema do banco
├── frontend/                # Interface React
│   └── src/
│       ├── components/      # Componentes React
│       ├── pages/           # Páginas
│       ├── contexts/        # Context API
│       └── services/        # API client
├── docker-compose.yml       # PostgreSQL setup
└── README.md
```

## 🔑 Configuração

### API Keys necessárias:

#### Groq (Recomendado - Grátis) 🎁
1. Acesse: https://console.groq.com
2. Crie uma conta
3. Gere uma API key
4. Cole no `.env`: `GROQ_API_KEY="sua-key"`

#### OpenAI (Pago)
1. Acesse: https://platform.openai.com
2. Adicione créditos
3. Gere uma API key
4. Cole no `.env`: `OPENAI_API_KEY="sua-key"`

#### Anthropic Claude (Pago)
1. Acesse: https://console.anthropic.com
2. Adicione créditos
3. Gere uma API key
4. Cole no `.env`: `ANTHROPIC_API_KEY="sua-key"`

### Credenciais do Chatwoot:

1. Faça login no Chatwoot/Profluxus Talk
2. Vá em **Perfil** → **Access Token**
3. Copie o token
4. Account ID está na URL: `/app/accounts/{ACCOUNT_ID}`

## 🎨 Screenshots

### Dashboard
Lista todas as análises criadas com status e número de automações.

### Nova Análise
Interface para colar texto e escolher provider de IA.

### Detalhes da Submissão
Veja todas as automações geradas, edite e envie para o Chatwoot.

### Configurações
Gerencie múltiplas contas do Chatwoot (multi-tenant).

## 🚀 Deploy em Produção

```bash
# Build
npm run build

# Inicie com PM2
cd backend
npm install -g pm2
pm2 start dist/index.js --name chatwoot-automation
```

## 🤝 Contribuindo

Contribuições são bem-vindas! Sinta-se à vontade para:

1. Fazer fork do projeto
2. Criar uma branch para sua feature
3. Commitar suas mudanças
4. Abrir um Pull Request

## 📄 Licença

MIT

## 💡 Suporte

Encontrou um bug ou tem uma sugestão?
Abra uma issue no GitHub!

---

**Desenvolvido com ❤️ para facilitar automações do Chatwoot**
