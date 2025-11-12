<?php
/**
 * Simplifika Post - Cron Job para Agendamento de Posts
 * 
 * Este script deve ser executado a cada minuto via Cronjob
 * Comando: * * * * * /usr/bin/php /caminho/para/cron.php
 * 
 * Ele verifica posts agendados e os publica no horário correto
 */

// Inclui configurações
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/OAuthMeta.php';
require_once __DIR__ . '/OAuthYouTube.php';

// ========================================
// FUNÇÃO PRINCIPAL DO CRON
// ========================================

function processPendingPosts() {
    $db = Database::getInstance();
    
    // Busca posts agendados que chegaram à hora
    $posts = $db->fetchAll(
        "SELECT * FROM posts 
         WHERE status = 'scheduled' 
         AND scheduled_at <= NOW() 
         LIMIT 10"
    );
    
    foreach ($posts as $post) {
        try {
            publishPost($post);
        } catch (Exception $e) {
            logPublicationError($post['id'], $e->getMessage());
        }
    }
}

/**
 * Publica um post nas plataformas especificadas
 */
function publishPost($post) {
    $db = Database::getInstance();
    $platforms = json_decode($post['platforms'], true);
    
    // Obtém informações do usuário
    $user = $db->fetchOne('SELECT id, email FROM users WHERE id = ?', [$post['user_id']]);
    
    if (!$user) {
        throw new Exception('Usuário não encontrado');
    }
    
    $allSuccess = true;
    
    // Publica em cada plataforma
    foreach ($platforms as $platform) {
        try {
            publishToPlatform($post, $platform, $user);
            logPublication($post['id'], $platform, 'success');
        } catch (Exception $e) {
            logPublication($post['id'], $platform, 'failed', $e->getMessage());
            $allSuccess = false;
        }
    }
    
    // Atualiza status do post
    $status = $allSuccess ? 'published' : 'failed';
    $db->update('posts', ['status' => $status], 'id = ?', [$post['id']]);
}

/**
 * Publica um post em uma plataforma específica
 */
function publishToPlatform($post, $platform, $user) {
    $db = Database::getInstance();
    
    // Obtém a conta conectada
    $account = $db->fetchOne(
        'SELECT * FROM connected_accounts WHERE user_id = ? AND platform = ? LIMIT 1',
        [$user['id'], $platform]
    );
    
    if (!$account) {
        throw new Exception("Conta não conectada para plataforma: $platform");
    }
    
    // Verifica se o token expirou
    if ($account['token_expiry'] && strtotime($account['token_expiry']) < time()) {
        // Tenta atualizar o token se houver refresh token
        if ($account['refresh_token']) {
            $newToken = refreshToken($platform, $account['refresh_token']);
            $account['access_token'] = $newToken['access_token'];
            
            // Atualiza no banco
            $db->update(
                'connected_accounts',
                [
                    'access_token' => $newToken['access_token'],
                    'token_expiry' => date('Y-m-d H:i:s', time() + $newToken['expires_in']),
                ],
                'id = ?',
                [$account['id']]
            );
        } else {
            throw new Exception("Token expirado e sem refresh token");
        }
    }
    
    // Publica baseado na plataforma
    switch ($platform) {
        case 'facebook':
            publishToFacebook($post, $account);
            break;
        case 'instagram':
            publishToInstagram($post, $account);
            break;
        case 'youtube':
            publishToYouTube($post, $account);
            break;
        case 'tiktok':
            publishToTikTok($post, $account);
            break;
        default:
            throw new Exception("Plataforma desconhecida: $platform");
    }
}

/**
 * Publica no Facebook
 */
function publishToFacebook($post, $account) {
    $response = OAuthMeta::publishToFacebook(
        $account['account_id'],
        $account['access_token'],
        $post['content'],
        isset(json_decode($post['media_urls'], true)[0]) ? json_decode($post['media_urls'], true)[0] : null
    );
    
    return $response;
}

/**
 * Publica no Instagram
 */
function publishToInstagram($post, $account) {
    $mediaUrls = json_decode($post['media_urls'], true);
    
    if (empty($mediaUrls)) {
        throw new Exception('Instagram requer mídia para publicação');
    }
    
    $response = OAuthMeta::publishToInstagram(
        $account['account_id'],
        $account['access_token'],
        $post['content'],
        $mediaUrls[0]
    );
    
    return $response;
}

/**
 * Publica no YouTube
 */
function publishToYouTube($post, $account) {
    $mediaUrls = json_decode($post['media_urls'], true);
    
    if (empty($mediaUrls)) {
        throw new Exception('YouTube requer vídeo para publicação');
    }
    
    // Extrai o título e descrição do conteúdo
    $lines = explode("\n", $post['content']);
    $title = $lines[0] ?? 'Novo Vídeo';
    $description = implode("\n", array_slice($lines, 1));
    
    $response = OAuthYouTube::uploadVideo(
        $account['access_token'],
        $mediaUrls[0],
        $title,
        $description,
        [],
        $post['scheduled_at']
    );
    
    return $response;
}

/**
 * Publica no TikTok
 */
function publishToTikTok($post, $account) {
    // TikTok API é mais complexa, implementar conforme necessário
    throw new Exception('Publicação no TikTok ainda não implementada');
}

/**
 * Atualiza um token expirado
 */
function refreshToken($platform, $refreshToken) {
    switch ($platform) {
        case 'youtube':
            return OAuthYouTube::refreshAccessToken($refreshToken);
        default:
            throw new Exception("Refresh token não suportado para: $platform");
    }
}

/**
 * Registra uma publicação bem-sucedida
 */
function logPublication($postId, $platform, $status, $errorMessage = null) {
    $db = Database::getInstance();
    
    $db->insert('publication_logs', [
        'post_id' => $postId,
        'platform' => $platform,
        'status' => $status,
        'error_message' => $errorMessage,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

/**
 * Registra um erro de publicação
 */
function logPublicationError($postId, $errorMessage) {
    $db = Database::getInstance();
    
    $db->update(
        'posts',
        [
            'status' => 'failed',
            'error_message' => $errorMessage,
        ],
        'id = ?',
        [$postId]
    );
}

// ========================================
// EXECUÇÃO
// ========================================

try {
    processPendingPosts();
    echo "Cron job executado com sucesso\n";
} catch (Exception $e) {
    error_log('Erro no cron job: ' . $e->getMessage());
    echo "Erro: " . $e->getMessage() . "\n";
}

?>
