<?php
/**
 * Classe de OAuth com Meta (Facebook/Instagram)
 * 
 * Gerencia o fluxo de autenticação OAuth com Meta Graph API
 */

class OAuthMeta {
    
    /**
     * Gera a URL de autorização do Meta
     */
    public static function getAuthorizationUrl($state = null) {
        if (!$state) {
            $state = bin2hex(random_bytes(16));
        }
        
        $params = [
            'client_id' => META_APP_ID,
            'redirect_uri' => baseUrl('/api/oauth/meta/callback'),
            'scope' => 'pages_manage_posts,instagram_content_publish,pages_read_engagement',
            'response_type' => 'code',
            'state' => $state,
            'auth_type' => 'rerequest',
        ];
        
        // Armazena o state na sessão para validação
        $_SESSION['oauth_state'] = $state;
        
        return META_OAUTH_URL . '?' . http_build_query($params);
    }
    
    /**
     * Troca o código de autorização por um access token
     */
    public static function getAccessToken($code) {
        $params = [
            'client_id' => META_APP_ID,
            'client_secret' => META_APP_SECRET,
            'redirect_uri' => baseUrl('/api/oauth/meta/callback'),
            'code' => $code,
        ];
        
        $url = META_TOKEN_URL . '?' . http_build_query($params);
        
        $response = self::makeRequest($url, 'GET');
        
        if (isset($response['access_token'])) {
            return $response;
        }
        
        throw new Exception('Erro ao obter access token: ' . json_encode($response));
    }
    
    /**
     * Obtém as páginas do usuário
     */
    public static function getUserPages($accessToken) {
        $url = META_API_URL . '/me/accounts?access_token=' . urlencode($accessToken);
        
        $response = self::makeRequest($url, 'GET');
        
        if (isset($response['data'])) {
            return $response['data'];
        }
        
        throw new Exception('Erro ao obter páginas: ' . json_encode($response));
    }
    
    /**
     * Publica um post no Facebook
     */
    public static function publishToFacebook($pageId, $accessToken, $message, $imageUrl = null) {
        $url = META_API_URL . '/' . $pageId . '/feed';
        
        $params = [
            'message' => $message,
            'access_token' => $accessToken,
        ];
        
        if ($imageUrl) {
            $params['picture'] = $imageUrl;
        }
        
        $response = self::makeRequest($url, 'POST', $params);
        
        if (isset($response['id'])) {
            return $response;
        }
        
        throw new Exception('Erro ao publicar no Facebook: ' . json_encode($response));
    }
    
    /**
     * Publica um post no Instagram
     */
    public static function publishToInstagram($igAccountId, $accessToken, $caption, $imageUrl) {
        // Primeiro, cria um container de mídia
        $containerUrl = META_API_URL . '/' . $igAccountId . '/media';
        
        $containerParams = [
            'image_url' => $imageUrl,
            'caption' => $caption,
            'access_token' => $accessToken,
        ];
        
        $containerResponse = self::makeRequest($containerUrl, 'POST', $containerParams);
        
        if (!isset($containerResponse['id'])) {
            throw new Exception('Erro ao criar container de mídia: ' . json_encode($containerResponse));
        }
        
        $containerId = $containerResponse['id'];
        
        // Depois, publica o container
        $publishUrl = META_API_URL . '/' . $igAccountId . '/media_publish';
        
        $publishParams = [
            'creation_id' => $containerId,
            'access_token' => $accessToken,
        ];
        
        $publishResponse = self::makeRequest($publishUrl, 'POST', $publishParams);
        
        if (isset($publishResponse['id'])) {
            return $publishResponse;
        }
        
        throw new Exception('Erro ao publicar no Instagram: ' . json_encode($publishResponse));
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
     * Valida o state do OAuth
     */
    public static function validateState($state) {
        if (!isset($_SESSION['oauth_state']) || $_SESSION['oauth_state'] !== $state) {
            throw new Exception('State inválido');
        }
        
        unset($_SESSION['oauth_state']);
        return true;
    }
}

?>
