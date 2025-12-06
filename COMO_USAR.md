# Como Usar - Chatwoot Automation Compiler

## Visão Geral

Este sistema permite que você cole um texto contendo informações (FAQ, horários, preços, etc.) e a IA automaticamente gera automações prontas para o Chatwoot.

## Fluxo de Uso

### 1. Cadastro e Login

1. Acesse o sistema
2. Clique em "Cadastre-se"
3. Preencha: nome, email e senha
4. Faça login

### 2. Configurar Chatwoot

Antes de criar automações, você precisa conectar sua conta do Chatwoot:

1. Vá em **Configurações** (menu superior)
2. Clique em **Nova Configuração**
3. Preencha:
   - **Nome**: Nome descritivo (ex: "Clube Esmeraldas")
   - **URL do Chatwoot**: URL completa (ex: `https://app.chatwoot.com`)
   - **Account ID**: Encontre na URL do Chatwoot
   - **API Access Token**: Token da sua conta
4. Clique em **Salvar**

O sistema testará automaticamente a conexão.

### 3. Criar uma Análise

1. Vá em **Nova Análise** (menu superior)
2. Preencha:
   - **Título**: Nome descritivo da análise
   - **Configuração**: Selecione a configuração do Chatwoot
   - **Provider de IA**: Escolha entre Groq, OpenAI ou Claude
   - **Texto**: Cole o texto com as informações

#### Exemplo de texto para análise:

```
- Horário de funcionamento:
Segunda a Sexta: 9h às 18h
Sábado: 9h às 13h

- Endereço:
Rua dos Goitacazes 1596, Barro Preto, Belo Horizonte

- Valores Day Use:
R$ 180,00 por pessoa (não sócio)
R$ 150,00 por pessoa (sócio)
```

3. Clique em **Analisar com IA**
4. Aguarde o processamento (alguns segundos)

### 4. Revisar Automações

Após o processamento, você verá:

- Lista de automações geradas
- Nome e descrição de cada automação
- Condições (quando será acionada)
- Ações (o que será executado)
- Status: Rascunho/Enviado/Erro

**Exemplo de automação gerada:**

- **Nome**: "Responder sobre horário de funcionamento"
- **Condição**: Mensagem recebida contém "horário", "funciona" ou "aberto"
- **Ação**: Enviar mensagem com os horários

### 5. Enviar para o Chatwoot

Você tem duas opções:

#### Opção 1: Enviar uma automação por vez
1. Clique no botão **Enviar** ao lado da automação
2. Aguarde confirmação
3. A automação estará ativa no Chatwoot

#### Opção 2: Enviar todas de uma vez
1. Clique no botão **Enviar Todas** (topo da página)
2. Confirme
3. Todas as automações serão criadas no Chatwoot

### 6. Verificar no Chatwoot

1. Acesse seu Chatwoot
2. Vá em **Configurações** → **Automações**
3. Você verá as automações criadas
4. Elas já estão ativas e funcionando!

## Tipos de Automações Geradas

O sistema detecta e cria automações para:

### 1. Informações de Horário
- Detecta: "horário", "funciona", "aberto", "fecha"
- Responde: Horários de funcionamento

### 2. Informações de Endereço
- Detecta: "endereço", "localização", "onde fica", "como chegar"
- Responde: Endereço completo

### 3. Informações de Preços
- Detecta: "preço", "valor", "quanto custa", "day use"
- Responde: Tabela de preços

### 4. Informações de Serviços
- Detecta: palavras relacionadas ao serviço
- Responde: Detalhes do serviço

### 5. Hospedagem/Reservas
- Detecta: "hospedagem", "reserva", "hotel", "chalé"
- Responde: Informações e como reservar

## Dicas para Melhores Resultados

### ✅ Texto Bem Estruturado

```
- Horário de atendimento:
Segunda a Sexta: 8h às 18h

- Telefone de contato:
(31) 3295-6565
```

### ❌ Texto Desorganizado

```
ligamos segunda ate sexta das 8 as 18 telefone 31 32956565
```

### Dicas:

1. **Use tópicos claros** com títulos
2. **Separe informações** por categoria
3. **Seja específico** nos valores e horários
4. **Inclua variações** de termos (ex: "day use" e "ingresso")
5. **Quanto mais informação**, mais automações úteis

## Gerenciamento

### Ver todas as análises
- Vá no **Dashboard**
- Veja histórico completo
- Status de cada análise

### Editar configurações
- Vá em **Configurações**
- Edite credenciais do Chatwoot
- Adicione múltiplas configurações (multi-tenant)

### Excluir análises
- Entre na análise
- Exclua se não precisar mais

## Multi-tenant (Múltiplas Contas)

Você pode gerenciar múltiplas instalações do Chatwoot:

1. Adicione várias configurações em **Configurações**
2. Ao criar análise, escolha qual configuração usar
3. Cada cliente/projeto pode ter sua própria configuração

## Troubleshooting

### IA não gerou automações
- Verifique se o texto tem informações claras
- Tente adicionar mais detalhes
- Tente outro provider de IA

### Erro ao enviar para Chatwoot
- Verifique as credenciais
- Teste a conexão em Configurações
- Verifique se o token não expirou

### Automações não funcionam no Chatwoot
- Verifique se estão ativas
- Teste enviando mensagens
- Revise as condições criadas

## Próximos Passos

1. Teste com diferentes tipos de texto
2. Ajuste automações no Chatwoot se necessário
3. Monitore performance das automações
4. Crie novas análises quando adicionar informações

## Suporte

Em caso de dúvidas ou problemas, consulte a documentação completa ou abra uma issue no GitHub.
