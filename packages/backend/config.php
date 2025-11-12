<?php
/**
 * Simplifika Post - Backend em PHP
 * Arquivo de Configuração Principal
 * 
 * Este arquivo contém todas as configurações necessárias para o funcionamento
 * do sistema de agendamento de posts para redes sociais.
 */

// ========================================
// CONFIGURAÇÕES DE BANCO DE DADOS
// ========================================

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'seu_usuario');
define('DB_PASS', getenv('DB_PASS') ?: 'sua_senha');
define('DB_NAME', getenv('DB_NAME') ?: 'simplifika_post');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// ========================================
// CONFIGURAÇÕES DE SEGURANÇA
// ========================================

define('JWT_SECRET', getenv('JWT_SECRET') ?: 'sua_chave_secreta_muito_segura_aqui_minimo_32_caracteres');
define('JWT_EXPIRY', 86400 * 7); // 7 dias em segundos
define('SESSION_TIMEOUT', 3600); // 1 hora em segundos

// ========================================
// CONFIGURAÇÕES DE APLICAÇÃO
// ========================================

define('APP_NAME', getenv('APP_NAME') ?: 'Simplifika Post');
define('APP_URL', getenv('APP_URL') ?: 'https://seu-dominio.com');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', getenv('APP_DEBUG') ?: false);

// ========================================
// CONFIGURAÇÕES DO META (FACEBOOK/INSTAGRAM)
// ========================================

define('META_APP_ID', getenv('META_APP_ID') ?: '');
define('META_APP_SECRET', getenv('META_APP_SECRET') ?: '');
define('META_GRAPH_VERSION', 'v18.0');
define('META_OAUTH_URL', 'https://www.facebook.com/' . META_GRAPH_VERSION . '/dialog/oauth');
define('META_TOKEN_URL', 'https://graph.instagram.com/' . META_GRAPH_VERSION . '/oauth/access_token');
define('META_API_URL', 'https://graph.instagram.com/' . META_GRAPH_VERSION);

// ========================================
// CONFIGURAÇÕES DO YOUTUBE
// ========================================

define('YOUTUBE_API_KEY', getenv('YOUTUBE_API_KEY') ?: '');
define('YOUTUBE_CLIENT_ID', getenv('YOUTUBE_CLIENT_ID') ?: '');
define('YOUTUBE_CLIENT_SECRET', getenv('YOUTUBE_CLIENT_SECRET') ?: '');
define('YOUTUBE_REDIRECT_URI', APP_URL . '/api/oauth/youtube/callback');
define('YOUTUBE_API_URL', 'https://www.googleapis.com/youtube/v3');
define('YOUTUBE_OAUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('YOUTUBE_TOKEN_URL', 'https://oauth2.googleapis.com/token');

// ========================================
// CONFIGURAÇÕES DO TIKTOK
// ========================================

define('TIKTOK_CLIENT_KEY', getenv('TIKTOK_CLIENT_KEY') ?: '');
define('TIKTOK_CLIENT_SECRET', getenv('TIKTOK_CLIENT_SECRET') ?: '');
define('TIKTOK_REDIRECT_URI', APP_URL . '/api/oauth/tiktok/callback');

// ========================================
// CONFIGURAÇÕES DE EMAIL (OPCIONAL)
// ========================================

define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.seu-provedor.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: 'seu-email@seu-dominio.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@seu-dominio.com');

// ========================================
// CONFIGURAÇÕES DE LOGGING
// ========================================

define('LOG_LEVEL', getenv('LOG_LEVEL') ?: 'info');
define('LOG_DIR', __DIR__ . '/logs');

// ========================================
// CONFIGURAÇÕES DE UPLOAD
// ========================================

define('UPLOAD_DIR', __DIR__ . '/uploads');
define('MAX_UPLOAD_SIZE', 100 * 1024 * 1024); // 100MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi']);

// ========================================
// CONFIGURAÇÕES DE CORS
// ========================================

define('ALLOWED_ORIGINS', [
    'https://seu-dominio.com',
    'https://www.seu-dominio.com',
    'http://localhost:3000', // Para desenvolvimento
]);

// ========================================
// CONFIGURAÇÕES DE RATE LIMITING
// ========================================

define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hora

// ========================================
// FUNÇÕES AUXILIARES
// ========================================

/**
 * Obtém uma variável de configuração
 */
function config($key, $default = null) {
    $configs = [
        'db.host' => DB_HOST,
        'db.user' => DB_USER,
        'db.pass' => DB_PASS,
        'db.name' => DB_NAME,
        'app.name' => APP_NAME,
        'app.url' => APP_URL,
        'app.env' => APP_ENV,
    ];
    
    return $configs[$key] ?? $default;
}

/**
 * Verifica se está em modo debug
 */
function isDebug() {
    return APP_DEBUG === true || APP_ENV === 'development';
}

/**
 * Retorna a URL base da aplicação
 */
function baseUrl($path = '') {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

// ========================================
// TRATAMENTO DE ERROS
// ========================================

if (!isDebug()) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// ========================================
// TIMEZONE
// ========================================

date_default_timezone_set('America/Sao_Paulo');

?>
