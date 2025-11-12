# Instruções de Deploy e Configuração do Backend PHP

As alterações para implementar o fluxo de cadastro com validação por e-mail foram concluídas e enviadas para o repositório do backend: [https://github.com/migueisalex/SimplifikaPost-Backend](https://github.com/migueisalex/SimplifikaPost-Backend).

## 1. Configuração do Banco de Dados MySQL

Você forneceu as seguintes credenciais:
- **Usuário**: `simplifikabase`
- **Servidor**: `simplifikabase.mysql.dbaas.com.br`
- **Senha**: `RNV6g#n!LxeFBq`
- **Nome do Banco**: (Assumimos `simplifika_post` conforme `config.php` e `schema.sql`)

### 1.1. Executar o Script SQL

Você deve executar o script `schema.sql` no seu banco de dados para criar as tabelas necessárias, incluindo as novas colunas de verificação.

1.  Conecte-se ao seu banco de dados (`simplifikabase.mysql.dbaas.com.br`) usando uma ferramenta como phpMyAdmin, MySQL Workbench ou o terminal.
2.  Execute o conteúdo do arquivo **`schema.sql`** (que está no repositório do backend).

**Atenção**: Se você já tem usuários cadastrados, precisará adicionar manualmente as colunas `email_verified`, `verification_code` e `code_expiry` à tabela `users` e definir `email_verified` como `1` para os usuários existentes.

```sql
-- Adicionar colunas para verificação de e-mail
ALTER TABLE users
ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER role,
ADD COLUMN verification_code VARCHAR(6) NULL AFTER email_verified,
ADD COLUMN code_expiry DATETIME NULL AFTER verification_code;

-- Marcar usuários existentes como verificados
UPDATE users SET email_verified = 1;
```

## 2. Configuração das Variáveis de Ambiente (Hosting)

O backend PHP lê as configurações do arquivo `config.php`, que prioriza as variáveis de ambiente. Você deve configurar as seguintes variáveis no seu ambiente de hospedagem (ex: Vercel, Hostinger, etc.):

| Variável | Valor | Descrição |
|---|---|---|
| `DB_HOST` | `simplifikabase.mysql.dbaas.com.br` | Servidor do MySQL |
| `DB_USER` | `simplifikabase` | Usuário do MySQL |
| `DB_PASS` | `RNV6g#n!LxeFBq` | Senha do MySQL |
| `DB_NAME` | `simplifika_post` | Nome do Banco de Dados |
| `JWT_SECRET` | `SUA_CHAVE_SECRETA` | Chave secreta para JWT (mínimo 32 caracteres) |
| `APP_URL` | `https://seu-dominio-backend.com` | URL base da sua API (necessário para JWT e e-mail) |
| `SMTP_HOST` | `smtp.seu-provedor.com` | Servidor SMTP para envio de e-mail |
| `SMTP_USER` | `seu-email@seu-dominio.com` | Usuário do SMTP |
| `SMTP_PASS` | `sua_senha_smtp` | Senha do SMTP |
| `SMTP_FROM` | `noreply@seu-dominio.com` | E-mail de remetente |

**IMPORTANTE SOBRE E-MAIL:**
A classe `Email.php` foi criada para usar as configurações SMTP. Para que o e-mail de verificação seja realmente enviado, você precisa configurar as variáveis `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS` e `SMTP_FROM` com as credenciais de um serviço de e-mail real.

## 3. Deploy do Backend

1.  **Conecte o repositório** `SimplifikaPost-Backend` ao seu serviço de hospedagem (seja Vercel, Hostinger, ou outro que suporte PHP).
2.  **Faça o Deploy** do código.

## 4. Atualização do Frontend (Já Feita)

O repositório do frontend (`migueisalex/SimplifikaPost`) já foi atualizado com o commit:
`feat: Implementar fluxo de cadastro com validação por e-mail`

O frontend agora usa as novas rotas:
- `POST /api/auth/register`
- `POST /api/auth/verify`

**Próximo Passo no Frontend:**
O Vercel deve ter iniciado o deploy automaticamente. Certifique-se de que o deploy do frontend foi concluído para que ele use as novas rotas do backend.

## 5. Teste Final

Após o deploy do backend e a configuração do banco de dados e SMTP:
1.  Acesse a tela de login/cadastro do seu frontend.
2.  Clique em **"Criar uma conta"**.
3.  Preencha E-mail, Senha e Nome.
4.  O sistema deve dizer que enviou um código para o e-mail.
5.  Verifique o e-mail, insira o código na tela de verificação e finalize o cadastro.

Qualquer dúvida sobre o deploy do backend, me avise!
