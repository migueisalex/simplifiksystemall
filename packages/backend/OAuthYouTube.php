<?php
/**
 * Classe de OAuth com YouTube
 * 
 * Gerencia o fluxo de autenticação OAuth com YouTube Data API v3
 */

class OAuthYouTube {
    
    /**
     * Gera a URL de autorização do YouTube
     */
    public static function getAuthorizationUrl($state = null) {
        if (!$state) {
            $state = bin2hex(random_bytes(16));
        }
        
        $params = [
            'client_id' => YOUTUBE_CLIENT_ID,
            'redirect_uri' => baseUrl('/api/oauth/youtube/callback'),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/youtube.upload https://www.googleapis.com/auth/youtube',
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];
        
        // Armazena o state na sessão para validação
        $_SESSION['youtube_oauth_state'] = $state;
        
        return YOUTUBE_OAUTH_URL . '?' . http_build_query($params);
    }
    
    /**
     * Troca o código de autorização por um access token
     */
    public static function getAccessToken($code) {
        $params = [
            'client_id' => YOUTUBE_CLIENT_ID,
            'client_secret' => YOUTUBE_CLIENT_SECRET,
            'redirect_uri' => baseUrl('/api/oauth/youtube/callback'),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
        
        $response = self::makeRequest(YOUTUBE_TOKEN_URL, 'POST', $params);
        
        if (isset($response['access_token'])) {
            return $response;
        }
        
        throw new Exception('Erro ao obter access token: ' . json_encode($response));
    }
    
    /**
     * Atualiza o access token usando o refresh token
     */
    public static function refreshAccessToken($refreshToken) {
        $params = [
            'client_id' => YOUTUBE_CLIENT_ID,
            'client_secret' => YOUTUBE_CLIENT_SECRET,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];
        
        $response = self::makeRequest(YOUTUBE_TOKEN_URL, 'POST', $params);
        
        if (isset($response['access_token'])) {
            return $response;
        }
        
        throw new Exception('Erro ao atualizar access token: ' . json_encode($response));
    }
    
    /**
     * Obtém informações do canal do usuário
     */
    public static function getChannelInfo($accessToken) {
        $url = YOUTUBE_API_URL . '/channels?part=snippet,contentDetails&mine=true&access_token=' . urlencode($accessToken);
        
        $response = self::makeRequest($url, 'GET');
        
        if (isset($response['items'][0])) {
            return $response['items'][0];
        }
        
        throw new Exception('Erro ao obter informações do canal: ' . json_encode($response));
    }
    
    /**
     * Faz upload de um vídeo para o YouTube
     */
    public static function uploadVideo($accessToken, $videoPath, $title, $description, $tags = [], $scheduledTime = null) {
        if (!file_exists($videoPath)) {
            throw new Exception('Arquivo de vídeo não encontrado: ' . $videoPath);
        }
        
        // Metadados do vídeo
        $metadata = [
            'snippet' => [
                'title' => $title,
                'description' => $description,
                'tags' => $tags,
                'categoryId' => '22', // People & Blogs
            ],
            'status' => [
                'privacyStatus' => 'private', // Começa como privado
            ],
        ];
        
        // Se houver horário agendado, adiciona à metadata
        if ($scheduledTime) {
            $metadata['status']['publishAt'] = $scheduledTime;
            $metadata['status']['privacyStatus'] = 'private';
        }
        
        $url = YOUTUBE_API_URL . '/videos?part=snippet,status&uploadType=multipart&access_token=' . urlencode($accessToken);
        
        // Prepara o arquivo para upload
        $fileSize = filesize($videoPath);
        $fileContent = file_get_contents($videoPath);
        
        // Faz o upload
        $response = self::makeMultipartRequest(
            $url,
            'POST',
            json_encode($metadata),
            $fileContent,
            'video/mp4'
        );
        
        if (isset($response['id'])) {
            return $response;
        }
        
        throw new Exception('Erro ao fazer upload do vídeo: ' . json_encode($response));
    }
    
    /**
     * Faz uma requisição HTTP
     */
    private static function makeRequest($url, $method = 'GET', $data = null) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Erro CURL: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception('Erro HTTP ' . $httpCode . ': ' . json_encode($decoded));
        }
        
        return $decoded;
    }
    
    /**
     * Faz uma requisição multipart para upload de arquivo
     */
    private static function makeMultipartRequest($url, $method, $metadata, $fileContent, $mimeType) {
        $boundary = '===============' . md5(uniqid()) . '==';
        
        $body = '';
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= $metadata . "\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: " . $mimeType . "\r\n";
        $body .= "Content-Transfer-Encoding: binary\r\n\r\n";
        $body .= $fileContent . "\r\n";
        $body .= '--' . $boundary . '--';
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutos para upload
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/related; boundary=' . $boundary,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Erro CURL: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception('Erro HTTP ' . $httpCode . ': ' . json_encode($decoded));
        }
        
        return $decoded;
    }
    
    /**
     * Valida o state do OAuth
     */
    public static function validateState($state) {
        if (!isset($_SESSION['youtube_oauth_state']) || $_SESSION['youtube_oauth_state'] !== $state) {
            throw new Exception('State inválido');
        }
        
        unset($_SESSION['youtube_oauth_state']);
        return true;
    }
}

?>
