<?php
/**
 * Simplifika Post - API Backend
 * 
 * Arquivo principal que roteador todas as requisições da API
 */

// Inicia sessão
session_start();

// Inclui configurações
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/OAuthMeta.php';
require_once __DIR__ . '/OAuthYouTube.php';

// ========================================
// CONFIGURAÇÃO DE CORS
// ========================================

header('Content-Type: application/json; charset=utf-8');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, ALLOWED_ORIGINS)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responde a requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ========================================
// ROTEAMENTO DE REQUISIÇÕES
// ========================================

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/', '', $path);

try {
    // Rotas de Autenticação
    if ($path === 'auth/register' && $method === 'POST') {
        handleRegister();
    } elseif ($path === 'auth/login' && $method === 'POST') {
        handleLogin();
    } elseif ($path === 'auth/me' && $method === 'GET') {
        handleGetMe();
    } elseif ($path === 'auth/logout' && $method === 'POST') {
        handleLogout();
    }
    
    // Rotas de Perfil
    elseif ($path === 'profile' && $method === 'GET') {
        handleGetProfile();
    } elseif ($path === 'profile' && $method === 'PUT') {
        handleUpdateProfile();
    }
    
    // Rotas de Posts
    elseif ($path === 'posts' && $method === 'GET') {
        handleListPosts();
    } elseif ($path === 'posts' && $method === 'POST') {
        handleCreatePost();
    } elseif (preg_match('/^posts\/(\d+)$/', $path, $matches) && $method === 'PUT') {
        handleUpdatePost($matches[1]);
    } elseif (preg_match('/^posts\/(\d+)$/', $path, $matches) && $method === 'DELETE') {
        handleDeletePost($matches[1]);
    }
    
        // Rotas de Grupos de Hashtags (Implementadas para o frontend)
    elseif ($path === 'hashtag-groups' && $method === 'GET') {
        // Lista todos os grupos de hashtags do usuário autenticado.
        handleListHashtagGroups();
    } elseif ($path === 'hashtag-groups' && $method === 'POST') {
        // Cria um novo grupo de hashtags.
        handleCreateHashtagGroup();
    } elseif (preg_match('/^hashtag-groups\/(\d+)$/', $path, $matches) && $method === 'DELETE') {
        // Exclui um grupo de hashtags específico pelo ID.
        handleDeleteHashtagGroup($matches[1]);
    }
    
    // Rotas de Contas Conectadas
    elseif ($path === 'accounts' && $method === 'GET') {
        handleListAccounts();
    } elseif ($path === 'oauth/meta/authorize' && $method === 'GET') {
        handleMetaAuthorize();
    } elseif ($path === 'oauth/meta/callback' && $method === 'GET') {
        handleMetaCallback();
    } elseif ($path === 'oauth/youtube/authorize' && $method === 'GET') {
        handleYouTubeAuthorize();
    } elseif ($path === 'oauth/youtube/callback' && $method === 'GET') {
        handleYouTubeCallback();
    }
    
    // Rota não encontrada
    else {
        http_response_code(404);
        echo json_encode(['error' => 'Rota não encontrada']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// ========================================
// HANDLERS DE AUTENTICAÇÃO
// ========================================

function handleRegister() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email, password e name são obrigatórios']);
        return;
    }
    
    $db = Database::getInstance();
    
    // Verifica se usuário já existe
    $existing = $db->fetchOne('SELECT id FROM users WHERE email = ?', [$data['email']]);
    if ($existing) {
        http_response_code(400);
        echo json_encode(['error' => 'Email já registrado']);
        return;
    }
    
    // Cria novo usuário
    $userId = $db->insert('users', [
        'email' => $data['email'],
        'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        'name' => $data['name'],
        'role' => 'user',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    
    $token = Auth::generateToken($userId, $data['email'], 'user');
    
    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $userId,
            'email' => $data['email'],
            'name' => $data['name'],
        ]
    ]);
}

function handleLogin() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email e password são obrigatórios']);
        return;
    }
    
    $db = Database::getInstance();
    
    $user = $db->fetchOne('SELECT id, email, password, name, role FROM users WHERE email = ?', [$data['email']]);
    
    if (!$user || !password_verify($data['password'], $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Email ou senha inválidos']);
        return;
    }
    
    $token = Auth::generateToken($user['id'], $user['email'], $user['role']);
    
    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
        ]
    ]);
}

function handleGetMe() {
    Auth::requireAuth();
    
    $user = Auth::getAuthenticatedUser();
    
    echo json_encode([
        'success' => true,
        'user' => $user,
    ]);
}

function handleLogout() {
    echo json_encode(['success' => true]);
}

// ========================================
// HANDLERS DE PERFIL
// ========================================

function handleGetProfile() {
    Auth::requireAuth();
    
    $user = Auth::getAuthenticatedUser();
    $db = Database::getInstance();
    
    $profile = $db->fetchOne('SELECT * FROM user_profiles WHERE user_id = ?', [$user['sub']]);
    
    echo json_encode([
        'success' => true,
        'profile' => $profile,
    ]);
}

function handleUpdateProfile() {
    Auth::requireAuth();
    
    $user = Auth::getAuthenticatedUser();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $db = Database::getInstance();
    
    // Verifica se perfil existe
    $existing = $db->fetchOne('SELECT id FROM user_profiles WHERE user_id = ?', [$user['sub']]);
    
    if ($existing) {
        $db->update('user_profiles', $data, 'user_id = ?', [$user['sub']]);
    } else {
        $data['user_id'] = $user['sub'];
        $db->insert('user_profiles', $data);
    }
    
    echo json_encode(['success' => true]);
}

// ========================================
// HANDLERS DE POSTS
// ========================================

function handleListPosts() {
    Auth::requireAuth();
    
    $user = Auth::getAuthenticatedUser();
    $db = Database::getInstance();
    
    $posts = $db->fetchAll(
        'SELECT * FROM posts WHERE user_id = ? ORDER BY scheduled_at DESC',
        [$user['sub']]
    );
    
    echo json_encode([
        'success' => true,
        'posts' => $posts,
    ]);
}

function handleCreatePost() {
    Auth::requireAuth();
    
    $user = Auth::getAuthenticatedUser();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $db = Database::getInstance();
    
    $postId = $db->insert('posts', [
        'user_id' => $user['sub'],
        'content' => $data['content'],
        'platforms' => json_encode($data['platforms']),
        'scheduled_at' => $data['scheduled_at'],
        'status' => 'scheduled',
        'post_type' => $data['post_type'] ?? 'feed',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    
    echo json_encode([
        'success' => true,
        'post_id' => $postId,
    ]);
}

function handleUpdatePost($postId) {
    Auth::requireAuth();
    
    $user = Auth::getAuthenticatedUser();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $db = Database::getInstance();
    
    // Verifica se post pertence ao usuário
    $post = $db->fetchOne('SELECT id FROM posts WHERE id = ? AND user_id = ?', [$postId, $user['sub']]);
    
    if (!$post) {
        http_response_code(404);
        echo json_encode(['error' => 'Post não encontrado']);
        return;
    }
    
    $db->update('posts', $data, 'id = ?', [$postId]);
    
    echo json_encode(['success' => true]);
}

function handleDeletePost($postId) {
    Auth::requireAuth();
    
    $user = Auth::getAuthenticatedUser();
    $db = Database::getInstance();
    
    // Verifica se post pertence ao usuário
    $post = $db->fetchOne('SELECT id FROM posts WHERE id = ? AND user_id = ?', [$postId, $user['sub']]);
    
    if (!$post) {
        http_response_code(404);
        echo json_encode(['error' => 'Post não encontrado']);
        return;
    }
    
    $db->delete('posts', 'id = ?', [$postId]);
    
    echo json_encode(['success' => true]);
}

// ========================================
// HANDLERS DE GRUPOS DE HASHTAGS
// ========================================

/**
 * Lista todos os grupos de hashtags do usuário autenticado.
 * Rota: GET /hashtag-groups
 */
function handleListHashtagGroups() {
    // Requer autenticação do usuário
    Auth::requireAuth();
    
    $user = Auth::getAuthenticatedUser();
    $db = Database::getInstance();
    
    // Busca todos os grupos de hashtags pertencentes ao usuário
    $groups = $db->fetchAll(
        'SELECT id, name, hashtags FROM hashtag_groups WHERE user_id = ? ORDER BY name ASC',
        [$user['sub']]
    );
    
    // Retorna a lista de grupos
    echo json_encode([
        'success' => true,
        'hashtagGroups' => $groups,
    ]);
}

/**
 * Cria um novo grupo de hashtags para o usuário autenticado.
 * Rota: POST /hashtag-groups
 */
function handleCreateHashtagGroup() {
    // Requer autenticação do usuário
    Auth::requireAuth();
    
    $user = Auth::getAuthenticatedUser();
    // Decodifica o corpo da requisição JSON
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validação de dados
    if (!isset($data['name']) || !isset($data['hashtags'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nome e hashtags são obrigatórios']);
        return;
    }
    
    $db = Database::getInstance();
    
    // Insere o novo grupo no banco de dados
    $groupId = $db->insert('hashtag_groups', [
        'user_id' => $user['sub'],
        'name' => $data['name'],
        'hashtags' => $data['hashtags'], // As hashtags são armazenadas como uma string
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    
    // Retorna o ID do grupo criado
    echo json_encode([
        'success' => true,
        'group_id' => $groupId,
    ]);
}

/**
 * Exclui um grupo de hashtags específico.
 * Rota: DELETE /hashtag-groups/{id}
 */
function handleDeleteHashtagGroup($groupId) {
    // Requer autenticação do usuário
    Auth::requireAuth();
    
    $user = Auth::getAuthenticatedUser();
    $db = Database::getInstance();
    
    // Verifica se o grupo pertence ao usuário antes de deletar
    $group = $db->fetchOne('SELECT id FROM hashtag_groups WHERE id = ? AND user_id = ?', [$groupId, $user['sub']]);
    
    if (!$group) {
        http_response_code(404);
        echo json_encode(['error' => 'Grupo de hashtag não encontrado']);
        return;
    }
    
    // Deleta o grupo
    $db->delete('hashtag_groups', 'id = ?', [$groupId]);
    
    // Retorna sucesso
    echo json_encode(['success' => true]);
}


// ========================================
// HANDLERS DE OAUTH
// ========================================

function handleMetaAuthorize() {
    $url = OAuthMeta::getAuthorizationUrl();
    header('Location: ' . $url);
}

function handleMetaCallback() {
    if (!isset($_GET['code']) || !isset($_GET['state'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Código ou state não fornecido']);
        return;
    }
    
    try {
        OAuthMeta::validateState($_GET['state']);
        
        $tokenData = OAuthMeta::getAccessToken($_GET['code']);
        
        // Aqui você armazenaria o token no banco de dados
        // Por enquanto, apenas retorna sucesso
        
        echo json_encode([
            'success' => true,
            'message' => 'Conta Meta conectada com sucesso',
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleYouTubeAuthorize() {
    $url = OAuthYouTube::getAuthorizationUrl();
    header('Location: ' . $url);
}

function handleYouTubeCallback() {
    if (!isset($_GET['code']) || !isset($_GET['state'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Código ou state não fornecido']);
        return;
    }
    
    try {
        OAuthYouTube::validateState($_GET['state']);
        
        $tokenData = OAuthYouTube::getAccessToken($_GET['code']);
        
        // Aqui você armazenaria o token no banco de dados
        // Por enquanto, apenas retorna sucesso
        
        echo json_encode([
            'success' => true,
            'message' => 'Conta YouTube conectada com sucesso',
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleListAccounts() {
    Auth::requireAuth();
    
    $user = Auth::getAuthenticatedUser();
    $db = Database::getInstance();
    
    $accounts = $db->fetchAll(
        'SELECT id, platform, account_name FROM connected_accounts WHERE user_id = ?',
        [$user['sub']]
    );
    
    echo json_encode([
        'success' => true,
        'accounts' => $accounts,
    ]);
}

?>
