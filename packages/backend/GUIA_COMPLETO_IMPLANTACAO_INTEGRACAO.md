# Guia Completo de Implantação e Integração (Full-Stack Deployment Guide)

**Autor:** Manus AI
**Data:** 8 de Novembro de 2025

## 1. Visão Geral do Sistema

O Simplifika Post é um sistema de agendamento de posts para redes sociais, composto por:

*   **Frontend:** Desenvolvido em **React/Next.js** (TypeScript).
*   **Backend:** Desenvolvido em **PHP** (com classes de banco de dados e autenticação).
*   **Banco de Dados:** **MySQL** ou **MariaDB**.

Este guia fornece as instruções passo a passo para implantar o sistema completo em um servidor dedicado, sem depender de plataformas como o Vercel.

## 2. Requisitos de Servidor e Hospedagem

O sistema requer um ambiente de hospedagem que suporte a pilha **LAMP** (Linux, Apache/Nginx, MySQL, PHP) ou **LEMP** (Linux, Nginx, MySQL, PHP).

| Componente | Versão Mínima Recomendada | Configuração Necessária |
| :--- | :--- | :--- |
| **Sistema Operacional** | Ubuntu 20.04+ ou CentOS 7+ | Acesso SSH (Root ou Sudo) |
| **Servidor Web** | Apache 2.4+ ou Nginx 1.18+ | Configuração de Virtual Host/Server Block |
| **PHP** | **PHP 8.0+** | Extensões: `pdo_mysql`, `json`, `mbstring`, `openssl` |
| **Banco de Dados** | MySQL 5.7+ ou MariaDB 10.2+ | Acesso de usuário com permissão de criação de DB |
| **Node.js** | Node.js 18+ | Necessário apenas para o *build* do Frontend |

## 3. Implantação do Backend (PHP)

O backend é a camada de API que gerencia a autenticação, posts e grupos de hashtags.

### 3.1. Configuração do Banco de Dados

1.  Acesse o servidor MySQL/MariaDB e crie o banco de dados:
    ```bash
    CREATE DATABASE simplifika_post CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ```
2.  Importe o esquema de tabelas usando o arquivo `schema.sql` (localizado no repositório do backend):
    ```bash
    mysql -u [seu_usuario] -p simplifika_post < /caminho/para/SimplifikaPost-Backend/schema.sql
    ```

### 3.2. Configuração do Servidor PHP

1.  Clone o repositório do backend para o diretório raiz do seu servidor web (ex: `/var/www/simplifika-post-api`):
    ```bash
    git clone https://github.com/migueisalex/SimplifikaPost-Backend.git /var/www/simplifika-post-api
    ```
2.  **Configuração do `config.php`:** Edite o arquivo `/var/www/simplifika-post-api/config.php` com as credenciais do seu banco de dados e as chaves de API.

    ```php
    // Exemplo de configuração no config.php
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'simplifika_post');
    define('DB_USER', 'seu_usuario_db');
    define('DB_PASS', 'sua_senha_db');

    // Chave secreta para JWT (MUITO IMPORTANTE: Mude para uma string longa e aleatória)
    define('JWT_SECRET', 'SUA_CHAVE_SECRETA_MUITO_LONGA_E_ALEATORIA');

    // URLs permitidas para CORS (Adicione a URL do seu frontend)
    define('ALLOWED_ORIGINS', ['http://localhost:3000', 'https://seu-dominio-frontend.com']);
    ```

3.  **Configuração do Servidor Web (Exemplo Nginx):** Configure um *Server Block* para rotear todas as requisições para o `index.php` (usando *URL Rewriting*).

    ```nginx
    server {
        listen 80;
        server_name api.seu-dominio.com;
        root /var/www/simplifika-post-api;
        index index.php;

        location / {
            try_files $uri $uri/ /index.php?$args;
        }

        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; # Ajuste a versão do PHP
        }
    }
    ```

## 4. Implantação do Frontend (Next.js)

O frontend deve ser compilado e servido estaticamente ou através de um servidor Node.js.

### 4.1. Configuração de Variáveis de Ambiente

1.  Clone o repositório do frontend:
    ```bash
    git clone https://github.com/migueisalex/SimplifikaPost-Frontend.git /var/www/simplifika-post-frontend
    cd /var/www/simplifika-post-frontend
    ```
2.  Crie o arquivo `.env.local` e configure as variáveis:

    ```bash
    # Variável crucial para a comunicação com o Backend
    NEXT_PUBLIC_API_BASE_URL=https://api.seu-dominio.com/

    # Chave de API do Gemini (necessária para as APIs do frontend)
    GEMINI_API_KEY=SUA_CHAVE_GEMINI_AQUI
    ```

### 4.2. Build e Execução

1.  Instale as dependências e compile o projeto:
    ```bash
    npm install
    npm run build
    ```
2.  **Execução:** O Next.js deve ser executado em modo de produção.
    ```bash
    npm start
    ```
3.  **Servidor Web:** Configure o servidor web (Apache/Nginx) para atuar como *proxy reverso* para a porta onde o Next.js está rodando (geralmente porta 3000).

    ```nginx
    # Exemplo de Proxy Reverso no Nginx
    server {
        listen 80;
        server_name seu-dominio-frontend.com;

        location / {
            proxy_pass http://localhost:3000;
            proxy_http_version 1.1;
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection 'upgrade';
            proxy_set_header Host $host;
            proxy_cache_bypass $http_upgrade;
        }
    }
    ```

## 5. Integração Completa

A integração é feita através da variável `NEXT_PUBLIC_API_BASE_URL` no frontend, que deve apontar para a URL do seu backend.

| Funcionalidade | Frontend (Chamada) | Backend (Endpoint) |
| :--- | :--- | :--- |
| **Login/Registro** | `POST /auth/login` | `POST /api/auth/login` |
| **Listar Posts** | `GET /posts` | `GET /api/posts` |
| **Criar Post** | `POST /posts` | `POST /api/posts` |
| **Listar Hashtags** | `GET /hashtag-groups` | `GET /api/hashtag-groups` |
| **Excluir Hashtag** | `DELETE /hashtag-groups/{id}` | `DELETE /api/hashtag-groups/{id}` |

**Observação:** O frontend espera que o backend retorne o JSON de acordo com a estrutura definida nos *handlers* do `index.php`.

## 6. Comentários no Código

Todos os arquivos principais (`index.php`, `Database.php`, `Auth.php`, e os componentes/APIs do frontend) foram revisados e receberam comentários detalhados para facilitar a manutenção e o entendimento por parte do programador.

---
*Fim do Guia*
