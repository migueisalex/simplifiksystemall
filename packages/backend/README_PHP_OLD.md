# Simplifika Post - Backend em PHP

Sistema completo de agendamento de posts para redes sociais (Facebook, Instagram, YouTube e TikTok) desenvolvido em PHP para servidor compartilhado.

## 📋 Requisitos

- PHP 7.4+
- MySQL 5.7+ ou MariaDB 10.2+
- Extensões PHP: curl, json, pdo_mysql
- Acesso SSH (recomendado)
- Cronjob disponível

## 🚀 Instalação Rápida

### 1. Preparar o Banco de Dados

```bash
mysql -u seu_usuario -p < schema.sql
```

### 2. Fazer Upload dos Arquivos

Faça upload de todos os arquivos PHP para seu servidor via FTP/SFTP:

```
seu-dominio.com/
├── api/
│   ├── config.php
│   ├── Database.php
│   ├── Auth.php
│   ├── OAuthMeta.php
│   ├── OAuthYouTube.php
│   ├── index.php
│   ├── cron.php
│   └── .env
```

### 3. Configurar Variáveis de Ambiente

Copie `.env.example` para `.env` e preencha com suas informações:

```bash
cp .env.example .env
```

Edite o arquivo `.env` com suas credenciais:

```
DB_HOST=localhost
DB_USER=seu_usuario
DB_PASS=sua_senha
DB_NAME=simplifika_post

JWT_SECRET=sua_chave_secreta_aqui
META_APP_ID=seu_app_id
META_APP_SECRET=seu_app_secret
YOUTUBE_CLIENT_ID=seu_client_id
YOUTUBE_CLIENT_SECRET=seu_client_secret
```

### 4. Configurar Cronjob

Acesse o painel de controle do seu servidor (cPanel, Plesk, etc.) e adicione um novo Cronjob:

**Comando:**
```
* * * * * /usr/bin/php /caminho/para/cron.php
```

**Frequência:** A cada minuto

Isso fará com que posts agendados sejam publicados automaticamente no horário correto.

## 📚 Estrutura de Arquivos

```
simplifika-post-backend/
├── config.php          # Configurações principais
├── Database.php        # Classe de conexão com MySQL
├── Auth.php            # Autenticação com JWT
├── OAuthMeta.php       # OAuth com Facebook/Instagram
├── OAuthYouTube.php    # OAuth com YouTube
├── index.php           # Roteador principal da API
├── cron.php            # Script de agendamento
├── schema.sql          # Schema do banco de dados
├── .env.example        # Variáveis de ambiente (modelo)
└── README.md           # Este arquivo
```

## 🔌 Endpoints da API

### Autenticação

- `POST /api/auth/register` - Registrar novo usuário
- `POST /api/auth/login` - Fazer login
- `GET /api/auth/me` - Obter usuário autenticado
- `POST /api/auth/logout` - Fazer logout

### Perfil

- `GET /api/profile` - Obter perfil do usuário
- `PUT /api/profile` - Atualizar perfil

### Posts

- `GET /api/posts` - Listar posts do usuário
- `POST /api/posts` - Criar novo post
- `PUT /api/posts/{id}` - Atualizar post
- `DELETE /api/posts/{id}` - Deletar post

### Contas Conectadas

- `GET /api/accounts` - Listar contas conectadas
- `GET /api/oauth/meta/authorize` - Autorizar com Meta
- `GET /api/oauth/meta/callback` - Callback do Meta
- `GET /api/oauth/youtube/authorize` - Autorizar com YouTube
- `GET /api/oauth/youtube/callback` - Callback do YouTube

## 🔐 Autenticação

A API usa JWT (JSON Web Tokens) para autenticação. Inclua o token no header:

```
Authorization: Bearer seu_token_aqui
```

## 📝 Exemplo de Uso

### Registrar Usuário

```bash
curl -X POST https://seu-dominio.com/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@exemplo.com",
    "password": "senha123",
    "name": "Seu Nome"
  }'
```

### Criar Post

```bash
curl -X POST https://seu-dominio.com/api/posts \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer seu_token" \
  -d '{
    "content": "Meu primeiro post!",
    "platforms": ["facebook", "instagram"],
    "scheduled_at": "2024-12-31 14:30:00",
    "post_type": "feed"
  }'
```

## 🔄 Fluxo de OAuth

### 1. Autorizar com Meta (Facebook/Instagram)

Redirecione o usuário para:
```
https://seu-dominio.com/api/oauth/meta/authorize
```

### 2. Meta redireciona de volta com código

Seu backend troca o código por um access token e armazena no banco.

### 3. Publicar no Facebook/Instagram

Quando um post é agendado, o cron job publica automaticamente usando o token armazenado.

## 🐛 Troubleshooting

### Erro: "Cannot find module"

Verifique se todos os arquivos PHP estão no diretório correto.

### Erro: "Access denied for user"

Verifique as credenciais do MySQL no arquivo `.env`.

### Posts não estão sendo publicados

1. Verifique se o Cronjob está configurado corretamente
2. Verifique os logs: `tail -f /var/log/cron`
3. Teste o cron manualmente: `/usr/bin/php /caminho/para/cron.php`

### Erro de OAuth

1. Verifique se o App ID e Secret estão corretos
2. Verifique se a URL de callback está configurada no painel de desenvolvedor
3. Verifique se o servidor está em HTTPS

## 📊 Estrutura do Banco de Dados

### Tabela: users
- id, email, password, name, role, created_at, updated_at

### Tabela: user_profiles
- id, user_id, full_name, birth_date, cpf, cep, address, etc.

### Tabela: connected_accounts
- id, user_id, platform, account_id, account_name, access_token, refresh_token, token_expiry

### Tabela: posts
- id, user_id, content, platforms, scheduled_at, status, post_type, media_urls, error_message

### Tabela: hashtag_groups
- id, user_id, name, hashtags

### Tabela: publication_logs
- id, post_id, platform, status, response_data, error_message

## 🔒 Segurança

- Senhas são hasheadas com bcrypt
- JWT tokens expiram em 7 dias
- CORS está configurado para domínios permitidos
- Todas as queries usam prepared statements
- Validação de input em todos os endpoints

## 📈 Performance

- Índices no banco de dados para queries rápidas
- Conexão PDO com prepared statements
- Caching de sessão
- Rate limiting (opcional)

## 🚢 Deploy em Produção

1. Mude `APP_ENV` para `production`
2. Mude `APP_DEBUG` para `false`
3. Gere uma chave JWT_SECRET segura
4. Configure HTTPS/SSL
5. Configure Cronjob
6. Faça backup do banco de dados

## 📞 Suporte

Para dúvidas ou problemas, consulte:
- Documentação das APIs: [Meta Graph API](https://developers.facebook.com/docs/graph-api), [YouTube Data API](https://developers.google.com/youtube/v3)
- Logs do servidor: `/var/log/php-errors.log`

## 📄 Licença

Desenvolvido por Manus AI - 2024
