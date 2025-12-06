# Configuração do OpenLiteSpeed para CyberPanel

Guia para configurar o proxy reverso e rewrite rules no OpenLiteSpeed.

## 📋 Contexto

O sistema tem duas partes:
- **Frontend**: Arquivos estáticos (HTML, CSS, JS) - servidos diretamente
- **Backend API**: Servidor Node.js na porta 3001 - precisa de proxy reverso

## 🔧 Configuração via CyberPanel (Recomendado)

### Passo 1: Acessar Rewrite Rules

1. Login no CyberPanel: `https://seu-servidor.com:8090`
2. Vá em: **Website** → **List Websites**
3. Clique em **Manage** no domínio (ex: `automations.seudominio.com`)
4. No menu lateral, clique em **Rewrite Rules**

### Passo 2: Adicionar Regras

Cole estas regras na caixa de texto:

```apache
RewriteEngine On

# Proxy para API (todas as requisições /api/* vão para localhost:3001)
RewriteRule ^api/(.*)$ http://127.0.0.1:3001/api/$1 [P,L]

# Para frontend - servir index.html para rotas do React Router
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ /index.html [L]
```

### Passo 3: Salvar

Clique em **Save Rewrite Rules**

---

## 🔧 Configuração Manual (Avançado)

Se preferir configurar manualmente via arquivo:

### 1. Localizar arquivo de configuração

```bash
nano /usr/local/lsws/conf/vhosts/automations.seudominio.com/vhost.conf
```

### 2. Adicionar Proxy Context

Encontre o bloco `<VirtualHost>` e adicione:

```xml
<context uri="/api/">
  type                    proxy
  handler                 http://127.0.0.1:3001
  addDefaultCharset       off
</context>
```

### 3. Configurar Document Root para Frontend

Dentro do mesmo `<VirtualHost>`:

```xml
<context uri="/">
  location                $VH_ROOT/public_html/
  allowBrowse             0
  indexFiles              index.html

  rewrite  {
    enable                1
    rules                 <<<END_rules
RewriteEngine On

# Proxy para API
RewriteRule ^api/(.*)$ http://127.0.0.1:3001/api/$1 [P,L]

# Frontend SPA
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ /index.html [L]
    END_rules
  }
</context>
```

### 4. Reiniciar OpenLiteSpeed

```bash
systemctl restart lsws
```

---

## 🧪 Testar Configuração

### Teste 1: API Health Check

```bash
curl http://seu-dominio.com/api/health
# ou
curl https://seu-dominio.com/api/health
```

Esperado: `{"status":"ok","timestamp":"..."}`

### Teste 2: Frontend

Acesse no navegador:
```
https://seu-dominio.com
```

Esperado: Tela de login do sistema

### Teste 3: React Router

Acesse diretamente uma rota:
```
https://seu-dominio.com/configs
```

Esperado: Deve carregar a página (não retornar 404)

---

## 🐛 Problemas Comuns

### Problema 1: API retorna 404

**Sintoma**: `https://seu-dominio.com/api/health` retorna 404

**Solução**:
```bash
# Verificar se backend está rodando
pm2 status

# Testar diretamente a API
curl http://localhost:3001/api/health

# Se funcionar, problema está no proxy
# Revisar rewrite rules
```

### Problema 2: Frontend retorna código fonte do index.html

**Sintoma**: Ao acessar, baixa o arquivo index.html ao invés de renderizar

**Solução**:
```bash
# Verificar MIME types
# Adicionar ao vhost.conf:

<context uri="/">
  extraHeaders            <<<END_extraHeaders
X-Content-Type-Options: nosniff
  END_extraHeaders
</context>
```

### Problema 3: Rotas do React retornam 404

**Sintoma**: `https://seu-dominio.com/configs` retorna 404

**Solução**: Certifique-se que a rewrite rule para SPA está correta:
```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ /index.html [L]
```

### Problema 4: CORS errors

**Sintoma**: Erro de CORS no console do navegador

**Solução**: Backend já tem CORS habilitado. Verifique se está acessando pela mesma URL (não misture http/https ou com/sem www)

---

## 📝 Configuração Completa de Exemplo

Arquivo completo: `/usr/local/lsws/conf/vhosts/automations.seudominio.com/vhost.conf`

```xml
docRoot                   $VH_ROOT/public_html

<context uri="/api/">
  type                    proxy
  handler                 http://127.0.0.1:3001
  addDefaultCharset       off
</context>

<context uri="/">
  location                $VH_ROOT/public_html/
  allowBrowse             0
  indexFiles              index.html

  rewrite  {
    enable                1
    autoLoadHtaccess      1

    rules                 <<<END_rules
RewriteEngine On

# API Proxy
RewriteRule ^api/(.*)$ http://127.0.0.1:3001/api/$1 [P,L]

# Frontend SPA
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ /index.html [L]
    END_rules
  }
</context>

# SSL Configuration (gerado automaticamente pelo CyberPanel)
vhssl  {
  keyFile                 /etc/letsencrypt/live/automations.seudominio.com/privkey.pem
  certFile                /etc/letsencrypt/live/automations.seudominio.com/fullchain.pem
  certChain               1
}
```

---

## 🔒 Configurar HTTPS

### Via CyberPanel

1. Vá em: **SSL** → **Issue SSL**
2. Selecione o domínio: `automations.seudominio.com`
3. Clique em **Issue SSL**
4. Aguarde a emissão (Let's Encrypt)

### Via Comando

```bash
/root/.acme.sh/acme.sh --issue \
  -d automations.seudominio.com \
  -w /home/automations.seudominio.com/public_html

# Instalar certificado
/root/.acme.sh/acme.sh --installcert \
  -d automations.seudominio.com \
  --certpath /etc/letsencrypt/live/automations.seudominio.com/cert.pem \
  --keypath /etc/letsencrypt/live/automations.seudominio.com/privkey.pem \
  --fullchainpath /etc/letsencrypt/live/automations.seudominio.com/fullchain.pem
```

---

## 🎯 Checklist de Verificação

- [ ] Rewrite rules configuradas (via CyberPanel ou manualmente)
- [ ] Backend rodando (PM2): `pm2 status`
- [ ] API respondendo: `curl http://localhost:3001/api/health`
- [ ] Proxy funcionando: `curl https://seu-dominio.com/api/health`
- [ ] Frontend carregando: acesse `https://seu-dominio.com`
- [ ] React Router funcionando: acesse `https://seu-dominio.com/configs`
- [ ] SSL instalado e funcionando
- [ ] Redirect HTTP → HTTPS funcionando

---

## 📊 Logs Úteis

### OpenLiteSpeed Logs
```bash
# Error log
tail -f /usr/local/lsws/logs/error.log

# Access log
tail -f /home/automations.seudominio.com/logs/access.log
```

### Backend (PM2) Logs
```bash
pm2 logs chatwoot-automation-api
```

### Verificar se porta 3001 está em uso
```bash
netstat -tlnp | grep 3001
lsof -i :3001
```

---

## 🚀 Performance

### Cache de Assets Estáticos

Adicione ao vhost.conf para melhor performance:

```xml
<context uri="/*.{js,css,png,jpg,jpeg,gif,ico,svg,woff,woff2,ttf}">
  enableExpires           1
  expiresDefault          A604800
  extraHeaders            <<<END_extraHeaders
Cache-Control: public, max-age=604800
  END_extraHeaders
</context>
```

### Compressão Gzip

OpenLiteSpeed já tem gzip habilitado por padrão. Verifique em:
- **Server Configuration** → **Tuning**
- `Enable Compression: Yes`

---

**Pronto! Seu sistema está configurado e rodando! 🎉**
