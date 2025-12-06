#!/bin/bash

# Script de Deploy - Chatwoot Automation Compiler
# Para usar no servidor CyberPanel

set -e  # Parar em caso de erro

echo "🚀 Iniciando deploy do Chatwoot Automation Compiler..."

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configurações (EDITE ESTAS VARIÁVEIS)
DOMAIN="automations.seudominio.com"
APP_DIR="/home/${DOMAIN}/public_html"
BRANCH="claude/chatwoot-automation-compiler-0147Nk23Rvqp4vad8b4MBxRE"

echo -e "${YELLOW}Domínio: ${DOMAIN}${NC}"
echo -e "${YELLOW}Diretório: ${APP_DIR}${NC}"
echo -e "${YELLOW}Branch: ${BRANCH}${NC}"

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then
  echo -e "${RED}❌ Execute como root: sudo bash deploy.sh${NC}"
  exit 1
fi

# 1. Verificar Node.js
echo -e "\n${YELLOW}📦 Verificando Node.js...${NC}"
if ! command -v node &> /dev/null; then
    echo -e "${RED}❌ Node.js não encontrado. Instalando...${NC}"
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
    apt-get install -y nodejs
fi
echo -e "${GREEN}✅ Node.js $(node --version) instalado${NC}"

# 2. Verificar PostgreSQL
echo -e "\n${YELLOW}🐘 Verificando PostgreSQL...${NC}"
if ! command -v psql &> /dev/null; then
    echo -e "${RED}❌ PostgreSQL não encontrado. Instalando...${NC}"
    apt-get update
    apt-get install -y postgresql postgresql-contrib
    systemctl start postgresql
    systemctl enable postgresql
fi
echo -e "${GREEN}✅ PostgreSQL instalado${NC}"

# 3. Verificar PM2
echo -e "\n${YELLOW}⚙️  Verificando PM2...${NC}"
if ! command -v pm2 &> /dev/null; then
    echo -e "${RED}❌ PM2 não encontrado. Instalando...${NC}"
    npm install -g pm2
fi
echo -e "${GREEN}✅ PM2 instalado${NC}"

# 4. Navegar para diretório
echo -e "\n${YELLOW}📁 Navegando para ${APP_DIR}...${NC}"
cd ${APP_DIR}

# 5. Pull do Git
echo -e "\n${YELLOW}🔄 Atualizando código do Git...${NC}"
git pull origin ${BRANCH}
echo -e "${GREEN}✅ Código atualizado${NC}"

# 6. Instalar dependências do Backend
echo -e "\n${YELLOW}📦 Instalando dependências do Backend...${NC}"
cd ${APP_DIR}/backend
npm install --production
echo -e "${GREEN}✅ Dependências do Backend instaladas${NC}"

# 7. Verificar se .env existe
if [ ! -f "${APP_DIR}/backend/.env" ]; then
    echo -e "${RED}❌ Arquivo .env não encontrado!${NC}"
    echo -e "${YELLOW}📝 Criando .env a partir do .env.example...${NC}"
    cp .env.example .env
    echo -e "${YELLOW}⚠️  IMPORTANTE: Edite ${APP_DIR}/backend/.env com suas configurações!${NC}"
    echo -e "${YELLOW}⚠️  Execute: nano ${APP_DIR}/backend/.env${NC}"
    read -p "Pressione ENTER após configurar o .env..."
fi

# 8. Gerar Prisma Client
echo -e "\n${YELLOW}🔧 Gerando Prisma Client...${NC}"
npx prisma generate
echo -e "${GREEN}✅ Prisma Client gerado${NC}"

# 9. Executar Migrations
echo -e "\n${YELLOW}🗄️  Executando migrations do banco de dados...${NC}"
npx prisma migrate deploy
echo -e "${GREEN}✅ Migrations executadas${NC}"

# 10. Build do Backend
echo -e "\n${YELLOW}🏗️  Compilando Backend (TypeScript → JavaScript)...${NC}"
npm run build
echo -e "${GREEN}✅ Backend compilado${NC}"

# 11. Instalar dependências do Frontend
echo -e "\n${YELLOW}📦 Instalando dependências do Frontend...${NC}"
cd ${APP_DIR}/frontend
npm install
echo -e "${GREEN}✅ Dependências do Frontend instaladas${NC}"

# 12. Build do Frontend
echo -e "\n${YELLOW}🏗️  Compilando Frontend (React → HTML/CSS/JS)...${NC}"
npm run build
echo -e "${GREEN}✅ Frontend compilado${NC}"

# 13. Copiar build do frontend para raiz
echo -e "\n${YELLOW}📋 Copiando arquivos do Frontend...${NC}"
cd ${APP_DIR}
cp -rf frontend/dist/* .
echo -e "${GREEN}✅ Frontend copiado para ${APP_DIR}${NC}"

# 14. Criar arquivo ecosystem.config.js para PM2
echo -e "\n${YELLOW}⚙️  Criando configuração do PM2...${NC}"
cat > ${APP_DIR}/ecosystem.config.js << 'EOF'
module.exports = {
  apps: [{
    name: 'chatwoot-automation-api',
    cwd: '${APP_DIR}/backend',
    script: 'dist/index.js',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      PORT: 3001
    },
    error_file: '${APP_DIR}/logs/api-error.log',
    out_file: '${APP_DIR}/logs/api-out.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss'
  }]
};
EOF

# Substituir variáveis
sed -i "s|\${APP_DIR}|${APP_DIR}|g" ${APP_DIR}/ecosystem.config.js
echo -e "${GREEN}✅ Configuração do PM2 criada${NC}"

# 15. Criar pasta de logs
mkdir -p ${APP_DIR}/logs

# 16. Parar processo PM2 se existir
echo -e "\n${YELLOW}🛑 Parando processo PM2 anterior (se existir)...${NC}"
pm2 delete chatwoot-automation-api 2>/dev/null || true

# 17. Iniciar com PM2
echo -e "\n${YELLOW}🚀 Iniciando aplicação com PM2...${NC}"
cd ${APP_DIR}
pm2 start ecosystem.config.js
echo -e "${GREEN}✅ Aplicação iniciada${NC}"

# 18. Salvar configuração PM2
echo -e "\n${YELLOW}💾 Salvando configuração do PM2...${NC}"
pm2 save
echo -e "${GREEN}✅ Configuração salva${NC}"

# 19. Configurar PM2 para iniciar no boot (se ainda não estiver)
if ! pm2 startup | grep -q "already"; then
    echo -e "\n${YELLOW}🔧 Configurando PM2 para iniciar no boot...${NC}"
    pm2 startup systemd -u root --hp /root
    echo -e "${GREEN}✅ PM2 configurado para iniciar no boot${NC}"
fi

# 20. Ajustar permissões
echo -e "\n${YELLOW}🔒 Ajustando permissões...${NC}"
chown -R nobody:nogroup ${APP_DIR}
chmod -R 755 ${APP_DIR}
echo -e "${GREEN}✅ Permissões ajustadas${NC}"

# 21. Mostrar status
echo -e "\n${YELLOW}📊 Status da aplicação:${NC}"
pm2 status

echo -e "\n${GREEN}✅ Deploy concluído com sucesso!${NC}"
echo -e "\n${YELLOW}📝 Próximos passos:${NC}"
echo -e "1. Configure SSL no CyberPanel (SSL → Issue SSL)"
echo -e "2. Configure rewrite rules no OpenLiteSpeed (veja DEPLOY_CYBERPANEL.md)"
echo -e "3. Acesse: https://${DOMAIN}"
echo -e "\n${YELLOW}📋 Comandos úteis:${NC}"
echo -e "pm2 status                    - Ver status"
echo -e "pm2 logs chatwoot-automation-api  - Ver logs"
echo -e "pm2 restart chatwoot-automation-api - Reiniciar"
echo -e "pm2 monit                     - Monitorar recursos"

echo -e "\n${GREEN}🎉 Tudo pronto!${NC}"
