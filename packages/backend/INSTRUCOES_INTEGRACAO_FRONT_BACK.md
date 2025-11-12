# Instruções de Integração Frontend e Backend

**Autor:** Manus AI
**Data:** 8 de Novembro de 2025

## 1. Visão Geral

Este documento detalha as instruções de integração entre o frontend (React/Next.js) e o backend (PHP) do projeto Simplifika Post, com foco nas novas funcionalidades implementadas e na configuração de variáveis de ambiente.

## 2. Configuração de Variáveis de Ambiente

Para que o frontend se comunique corretamente com o backend, a URL base da API deve ser configurada.

| Variável | Descrição | Exemplo de Valor | Local de Configuração |
| :--- | :--- | :--- | :--- |
| `NEXT_PUBLIC_API_BASE_URL` | URL base do backend onde a API está hospedada. | `https://api.seuservidor.com/api/` | Frontend (`.env.local` ou Vercel) |
| `GEMINI_API_KEY` | Chave de API do Google Gemini para funcionalidades de IA. | `AIzaSy...` | Frontend (Vercel) |

**Nota:** A variável `GEMINI_API_KEY` deve ser configurada no ambiente de *deploy* do Vercel para garantir a segurança e o funcionamento das sugestões de texto, hashtags e geração de imagens.

## 3. Endpoints de API Atualizados

O backend foi atualizado para incluir os endpoints necessários para a funcionalidade de gerenciamento de Grupos de Hashtags.

### 3.1. Gerenciamento de Grupos de Hashtags

| Funcionalidade | Método | Endpoint | Descrição |
| :--- | :--- | :--- | :--- |
| Listar Grupos | `GET` | `/hashtag-groups` | Retorna todos os grupos de hashtags do usuário. |
| Criar Grupo | `POST` | `/hashtag-groups` | Cria um novo grupo de hashtags. |
| Excluir Grupo | `DELETE` | `/hashtag-groups/{id}` | Exclui um grupo de hashtags específico. |

**Detalhes do Endpoint `DELETE /hashtag-groups/{id}`:**

*   **URL:** `/hashtag-groups/{id}` (onde `{id}` é o ID numérico do grupo de hashtag).
*   **Autenticação:** Requer token de autenticação no cabeçalho `Authorization`.
*   **Resposta de Sucesso (200 OK):** `{"success": true}`
*   **Resposta de Erro (404 Not Found):** `{"error": "Grupo de hashtag não encontrado"}`

## 4. Estrutura de Dados do Frontend

O frontend deve garantir que as chamadas de API sigam o formato esperado pelo backend.

### 4.1. Criação de Grupo de Hashtags (`POST /hashtag-groups`)

O corpo da requisição deve ser um objeto JSON com os seguintes campos:

```json
{
  "name": "Nome do Grupo",
  "hashtags": "#hashtag1 #hashtag2 #hashtag3"
}
```

### 4.2. Dados de Posts

O backend espera que os dados de `platforms` sejam enviados como um array de strings, que será automaticamente codificado em JSON no lado do servidor.

## 5. Próximos Passos

1.  **Deploy do Backend:** O backend atualizado deve ser implantado no servidor.
2.  **Configuração do Frontend:** A variável `NEXT_PUBLIC_API_BASE_URL` deve ser configurada no frontend para apontar para a URL do backend implantado.
3.  **Testes:** Realizar testes de ponta a ponta para as novas funcionalidades de Grupos de Hashtags.

---
*Fim do Documento*
