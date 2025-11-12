# SimplifikaPost Backend

Backend API para o SimplifikaPost - Plataforma de gerenciamento e agendamento de posts para redes sociais.

## 📋 Versão

**1.1.0** - Implementação completa conforme especificação técnica de 19/07/2024

## 🚀 Tecnologias

- **Node.js** com **TypeScript**
- **Express.js** - Framework web
- **Prisma** - ORM para PostgreSQL
- **PostgreSQL** - Banco de dados relacional
- **JWT** - Autenticação
- **bcrypt** - Hashing de senhas
- **AWS S3** - Armazenamento de mídia
- **Google Gemini AI** - Geração de conteúdo
- **Nodemailer** - Envio de emails
- **node-cron** - Tarefas agendadas

## 📦 Instalação

### Pré-requisitos

- Node.js 18+ 
- PostgreSQL 14+
- pnpm (gerenciador de pacotes)

### Passos

1. Clone o repositório:
```bash
git clone https://github.com/migueisalex/SimplifikaPost-Backend.git
cd SimplifikaPost-Backend
```

2. Instale as dependências:
```bash
pnpm install
```

3. Configure as variáveis de ambiente:
```bash
cp .env.example .env
# Edite o arquivo .env com suas configurações
```

4. Execute as migrações do banco de dados:
```bash
pnpm prisma:migrate
```

5. Gere o Prisma Client:
```bash
pnpm prisma:generate
```

6. Inicie o servidor em modo de desenvolvimento:
```bash
pnpm dev
```

## 🗄️ Estrutura do Banco de Dados

### Tabelas Principais

- **users** - Usuários do sistema
- **subscriptions** - Assinaturas e planos
- **usage_trackers** - Rastreamento de uso mensal
- **posts** - Posts agendados
- **media_items** - Arquivos de mídia dos posts
- **hashtag_groups** - Grupos de hashtags salvos
- **staff_members** - Membros da equipe (admin/financeiro)
- **audit_logs** - Logs de auditoria

## 🔐 Autenticação

A API usa **JWT (JSON Web Tokens)** para autenticação. Todos os endpoints (exceto `/auth/*`) requerem o header:

```
Authorization: Bearer <token>
```

## 📡 Endpoints Principais

### Autenticação
- `POST /api/auth/register` - Registrar novo usuário
- `POST /api/auth/verify-email` - Verificar código de email
- `POST /api/auth/login` - Login (usuário ou staff)

### Posts
- `GET /api/posts` - Listar posts do usuário
- `POST /api/posts` - Criar novo post
- `PUT /api/posts/:id` - Atualizar post
- `DELETE /api/posts/:id` - Deletar post
- `POST /api/posts/:id/clone` - Clonar post

### Mídia
- `POST /api/media/upload` - Upload de arquivo (multipart/form-data)

### IA (Gemini)
- `POST /api/ai/generate-text` - Gerar texto com IA
- `POST /api/ai/generate-image` - Gerar imagem com IA

### Assinaturas
- `GET /api/subscriptions` - Obter assinatura atual
- `PUT /api/subscriptions` - Atualizar assinatura
- `POST /api/subscriptions/downgrade` - Downgrade para Freemium

## 🎯 Lógica de Negócio

### Sistema de Limites (Usage Tracker)

O sistema controla o uso mensal baseado no plano:

| Plano | Posts | IA Texto | IA Imagem |
|-------|-------|----------|-----------|
| Freemium (0) | 10 | 5 | 3 |
| Pacote 1 | 50 | 30 | 20 |
| Pacote 2 | 150 | 100 | 75 |
| Pacote 3 | Ilimitado | Ilimitado | Ilimitado |

### Inadimplência

1. Gateway de pagamento notifica falha via webhook
2. Status do usuário → `Inadimplente`
3. Plano tratado como Freemium por 30 dias
4. Após 30 dias → Status `Bloqueado`
5. Bloqueado: apenas endpoints de pagamento funcionam

### Downgrade Voluntário

1. Usuário solicita downgrade
2. Mantém 5 primeiros posts agendados
3. Restantes → status `inativo_por_downgrade`
4. Email de notificação enviado
5. Posts inativos deletados após 30 dias

### Cron Jobs (Diários às 2h)

- Bloqueia usuários inadimplentes há mais de 30 dias
- Deleta posts publicados há mais de 90 dias
- Deleta posts inativos por downgrade há mais de 30 dias
- Envia avisos de exclusão 7 dias antes

## 🔧 Scripts Disponíveis

```bash
pnpm dev              # Inicia servidor em modo desenvolvimento
pnpm build            # Compila TypeScript para JavaScript
pnpm start            # Inicia servidor em produção
pnpm prisma:generate  # Gera Prisma Client
pnpm prisma:migrate   # Executa migrações
pnpm prisma:deploy    # Deploy de migrações em produção
```

## 🛡️ Segurança

- Senhas hasheadas com **bcrypt**
- Chaves de API criptografadas com **AES-256-CBC**
- Autenticação via **JWT**
- Validação de entrada em todos os endpoints
- Logs de auditoria para ações críticas
- CORS configurado

## 📝 Variáveis de Ambiente

Veja `.env.example` para a lista completa de variáveis necessárias.

## 🤝 Contribuindo

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença ISC.

## 👥 Equipe

SimplifikaPost Team

## 📞 Suporte

Para suporte, entre em contato através do email: suporte@simplifikapost.com

---

**Nota:** O README anterior em PHP foi movido para `README_PHP_OLD.md` para referência.
