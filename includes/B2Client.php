<?php

/** 
* B2Client - Integração com Backblaze B2 (Object Storage)
* 
* @package A Fenda
* @author DeepSeek (Marretador) / Revisado por Djê / Ondina
* @version 3.8 - Timeout ajustável + logs detalhados
* 
* CARACTERÍSTICAS:
* - Padrão Singleton: autentica apenas UMA vez por requisição
* - Zero dependências (apenas cURL nativo)
* - Configuração 100% via variáveis de ambiente (getenv)
* - ✅ NATIVO: https://f005.backblazeb2.com/file/BUCKET/arquivo?Authorization=TOKEN
* - CACHE DE URLs: evita múltiplas chamadas à API para o mesmo arquivo
* - Upload, Delete e Download com logs estruturados
* - Timeout ajustável (padrão 30s)
*/

class B2Client
{
    /**
     * Instância única da classe (Singleton)
     * @var B2Client|null
     */
    private static $instance = null;

    /**
     * @var string|null ID da chave (Key ID)
     */
    private $keyId;

    /**
     * @var string|null Chave de aplicação
     */
    private $applicationKey;

    /**
     * @var string|null Nome do bucket
     */
    private $bucketName;

    /**
     * @var string|null ID do bucket (injetado via getenv)
     */
    private $bucketId;

    /**
     * @var string|null URL da API do B2 (fixa)
     */
    private $apiUrl = 'https://api.backblazeb2.com';

    /**
     * @var string|null Token de autorização (válido por 24h)
     */
    private $authorizationToken;

    /**
     * @var string|null URL do bucket (ex: https://f000.backblazeb2.com)
     */
    private $downloadUrl;

    /**
     * @var array Cache de tokens de download autorizado (para Signed URLs)
     */
    private $downloadAuthCache = [];

    /**
     * @var array Cache de URLs assinadas já geradas (evita N+1)
     * Indexado pelo nome do arquivo
     */
    private $urlCache = [];

    /**
     * @var int Timeout padrão para requisições cURL (segundos)
     */
    private $timeout = 30;

    /**
     * Construtor privado (Singleton) – carrega credenciais e autentica
     * 
     * @throws Exception Se alguma credencial estiver faltando ou autenticação falhar
     */
    private function __construct()
    {
        $this->keyId          = getenv('B2_KEY_ID');
        $this->applicationKey = getenv('B2_APPLICATION_KEY');
        $this->bucketName     = getenv('B2_BUCKET_NAME');
        $this->bucketId       = getenv('B2_BUCKET_ID');

        // Validação rigorosa: se faltar qualquer credencial, trava com exceção
        if (empty($this->keyId) || empty($this->applicationKey) || empty($this->bucketId) || empty($this->bucketName)) {
            throw new Exception(
                '[B2Client] Credenciais incompletas. Verifique as variáveis de ambiente:' . PHP_EOL .
                    '  - B2_KEY_ID: '          . (empty($this->keyId) ? '❌ NÃO DEFINIDA' : '✅ DEFINIDA') . PHP_EOL .
                    '  - B2_APPLICATION_KEY: ' . (empty($this->applicationKey) ? '❌ NÃO DEFINIDA' : '✅ DEFINIDA') . PHP_EOL .
                    '  - B2_BUCKET_ID: '       . (empty($this->bucketId) ? '❌ NÃO DEFINIDA' : '✅ DEFINIDA') . PHP_EOL .
                    '  - B2_BUCKET_NAME: '     . (empty($this->bucketName) ? '❌ NÃO DEFINIDA' : '✅ DEFINIDA')
            );
        }

        error_log('[B2Client] Credenciais carregadas. Bucket: ' . $this->bucketName);

        // Realiza autenticação (uma única vez)
        $this->authorize();
    }

    /**
     * Define o timeout para as requisições cURL.
     * 
     * @param int $seconds Timeout em segundos
     */
    public function setTimeout($seconds)
    {
        $this->timeout = max(5, (int)$seconds);
        error_log("[B2Client] Timeout ajustado para {$this->timeout}s");
    }

    /**
     * Retorna a instância única da classe (Singleton)
     * 
     * @return B2Client
     * @throws Exception
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Autentica com a API do Backblaze B2.
     * 
     * O token é armazenado em memória (na instância) para evitar
     * múltiplas autenticações durante a mesma requisição.
     * 
     * 🔥 NOTA: Não lista buckets (já temos o B2_BUCKET_ID via env).
     * 
     * @throws Exception Se a autenticação falhar
     */
    private function authorize()
    {
        error_log('[B2Client] Iniciando autenticação...');

        // 1. Obter a URL da API e o token de autorização
        $ch = curl_init($this->apiUrl . '/b2api/v2/b2_authorize_account');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->keyId . ':' . $this->applicationKey);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        // 🔥 curl_close removido – PHP 8.5+ gerencia automaticamente

        if ($httpCode !== 200) {
            error_log("[B2Client] Falha na autenticação (HTTP $httpCode): " . ($response ?: 'sem resposta'));
            throw new Exception("[B2Client] Falha na autenticação (HTTP $httpCode): $response" . ($error ? " - cURL: $error" : ""));
        }

        $data = json_decode($response, true);
        if (!isset($data['apiUrl'], $data['authorizationToken'], $data['downloadUrl'])) {
            error_log('[B2Client] Resposta de autenticação inválida: ' . substr($response, 0, 200));
            throw new Exception('[B2Client] Resposta de autenticação inválida: ' . substr($response, 0, 200));
        }

        $this->apiUrl = $data['apiUrl'];
        $this->authorizationToken = $data['authorizationToken'];
        $this->downloadUrl = $data['downloadUrl'];

        error_log('[B2Client] Autenticação bem-sucedida. Download URL: ' . $this->downloadUrl);
    }

    /**
     * Obtém uma URL de upload para o bucket.
     * 
     * @return array ['uploadUrl' => string, 'authorizationToken' => string]
     * @throws Exception Se falhar ao obter URL
     */
    private function getUploadUrl()
    {
        error_log('[B2Client] Obtendo URL de upload...');

        $ch = curl_init($this->apiUrl . '/b2api/v2/b2_get_upload_url');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $this->authorizationToken,
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['bucketId' => $this->bucketId]));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // 🔥 curl_close removido

        if ($httpCode !== 200) {
            error_log("[B2Client] Falha ao obter URL de upload (HTTP $httpCode): " . ($response ?: 'sem resposta'));
            throw new Exception("[B2Client] Falha ao obter URL de upload (HTTP $httpCode): $response");
        }

        $data = json_decode($response, true);
        if (!isset($data['uploadUrl'], $data['authorizationToken'])) {
            error_log('[B2Client] Resposta de upload URL inválida: ' . substr($response, 0, 200));
            throw new Exception('[B2Client] Resposta de upload URL inválida: ' . substr($response, 0, 200));
        }

        error_log('[B2Client] URL de upload obtida com sucesso.');
        return [
            'uploadUrl' => $data['uploadUrl'],
            'authorizationToken' => $data['authorizationToken']
        ];
    }

    /**
     * Obtém um token de autorização para download (b2_get_download_authorization).
     * 
     * PÚBLICO: chamado externamente por proxy.php. Se ficar 'private', o PHP lança
     * um Fatal Error (violação de escopo) que NÃO é capturado por catch(Exception),
     * derrubando toda requisição de imagem antes mesmo de chegar ao B2.
     * 
     * @param string $fileName Nome do arquivo no bucket
     * @param int    $duration Duração em segundos (máximo: 86400 = 24h)
     * @return string Token de autorização
     * @throws Exception
     */
    public function getDownloadAuthorizationToken($fileName, $duration = 3600)
    {
        $cacheKey = $fileName . ':' . $duration;
        if (isset($this->downloadAuthCache[$cacheKey]) && $this->downloadAuthCache[$cacheKey]['expires'] > time()) {
            return $this->downloadAuthCache[$cacheKey]['token'];
        }

        // 🔧 CORREÇÃO: dirname() de um arquivo sem pasta (ex: 'post_abc_123.webp')
        // retorna '.', gerando fileNamePrefix = './' — que não bate com NENHUM
        // arquivo real. Como todos os arquivos ficam na raiz do bucket (flat),
        // usamos prefixo vazio, que autoriza o bucket inteiro (documentação oficial
        // do B2: prefixo vazio "matches all files in the bucket").
        $dir = dirname($fileName);
        $fileNamePrefix = ($dir === '.' || $dir === '/' || $dir === '') ? '' : $dir . '/';

        $ch = curl_init($this->apiUrl . '/b2api/v2/b2_get_download_authorization');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $this->authorizationToken,
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'bucketId' => $this->bucketId,
            'fileNamePrefix' => $fileNamePrefix,
            'validDurationInSeconds' => $duration
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // 🔥 curl_close removido

        if ($httpCode !== 200) {
            error_log("[B2Client] Falha ao obter token de download autorizado (HTTP $httpCode): " . ($response ?: 'sem resposta'));
            throw new Exception("[B2Client] Falha ao obter token de download autorizado (HTTP $httpCode): $response");
        }

        $data = json_decode($response, true);
        if (!isset($data['authorizationToken'])) {
            error_log('[B2Client] Resposta de download authorization inválida: ' . substr($response, 0, 200));
            throw new Exception('[B2Client] Resposta de download authorization inválida: ' . substr($response, 0, 200));
        }

        $this->downloadAuthCache[$cacheKey] = [
            'token' => $data['authorizationToken'],
            'expires' => time() + $duration - 60
        ];

        error_log("[B2Client] Token de download autorizado obtido para: $fileName (expira em $duration s)");
        return $data['authorizationToken'];
    }

    /**
     * Faz upload de um arquivo para o Backblaze B2.
     * 
     * @param string $filePath Caminho local do arquivo (ex: '/tmp/foto.jpg')
     * @param string $fileName Nome do arquivo no bucket (ex: 'posts/foto.jpg')
     * @param string $contentType MIME type (ex: 'image/jpeg', 'image/webp', 'image/gif')
     * @param array  $metadata  Metadados opcionais (ex: ['author' => 'user123'])
     * 
     * @return string Caminho do arquivo no bucket (para salvar no banco)
     * @throws Exception Se o upload falhar
     */
    public function uploadFile($filePath, $fileName, $contentType = 'application/octet-stream', $metadata = [])
    {
        if (!file_exists($filePath)) {
            throw new Exception("[B2Client] Arquivo não encontrado: $filePath");
        }

        error_log("[B2Client] Iniciando upload de '$fileName' (" . filesize($filePath) . " bytes)");

        $uploadData = $this->getUploadUrl();
        $uploadUrl = $uploadData['uploadUrl'];
        $uploadAuthToken = $uploadData['authorizationToken'];

        $fileContent = file_get_contents($filePath);
        $fileSize = filesize($filePath);

        $headers = [
            'Authorization: ' . $uploadAuthToken,
            'Content-Type: ' . $contentType,
            'Content-Length: ' . $fileSize,
            'X-Bz-File-Name: ' . $this->urlSafeBase64Encode($fileName)
        ];

        foreach ($metadata as $key => $value) {
            $headers[] = 'X-Bz-Info-' . $key . ': ' . $value;
        }

        $sha1 = sha1_file($filePath);
        $headers[] = 'X-Bz-Content-Sha1: ' . $sha1;

        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // 🔥 curl_close removido

        if ($httpCode !== 200) {
            error_log("[B2Client] Upload falhou (HTTP $httpCode) para '$fileName': " . substr($response, 0, 500));
            throw new Exception("[B2Client] Upload falhou (HTTP $httpCode): " . substr($response, 0, 500));
        }

        $data = json_decode($response, true);
        if (!isset($data['fileId'])) {
            error_log('[B2Client] Resposta de upload inválida: ' . substr($response, 0, 200));
            throw new Exception('[B2Client] Resposta de upload inválida: ' . substr($response, 0, 200));
        }

        error_log("[B2Client] Upload bem-sucedido: $fileName (fileId: {$data['fileId']})");
        return $fileName;
    }

    /**
     * Retorna a URL base de download do bucket
     */
    public function getDownloadUrl($fileName)
    {
        // Garante que o bucketName e a URL foram carregados
        if (empty($this->bucketName) || empty($this->downloadUrl)) {
            throw new Exception("B2Client não inicializado corretamente: bucket ou URL vazios.");
        }
        
        return $this->downloadUrl . '/file/' . $this->bucketName . '/' . ltrim($fileName, '/');
    }

    /**
     * 🔥 Gera uma URL assinada usando o formato NATIVO do Backblaze B2.
     * 
     * Formato: https://f005.backblazeb2.com/file/BUCKET/arquivo?Authorization=TOKEN
     * 
     * @param string $fileName Nome do arquivo no bucket
     * @param int    $duration Duração em segundos (padrão: 300 = 5min)
     * 
     * @return string URL assinada no formato nativo
     * @throws Exception Se não for possível obter o token
     */
    public function getSignedUrl($fileName, $duration = 300)
    {
        if (empty($fileName)) {
            throw new Exception('[B2Client] Nome do arquivo não pode ser vazio para Signed URL');
        }

        $duration = min($duration, 86400);

        // Obtém o token de autorização da API Nativa
        $authToken = $this->getDownloadAuthorizationToken($fileName, $duration);

        // 🔥 CONSTRÓI A URL NATIVA DO B2 (NÃO S3)
        // O $this->downloadUrl já é a URL base da API (ex: https://f005.backblazeb2.com)
        $baseUrl = $this->downloadUrl . '/file/' . $this->bucketName . '/' . ltrim($fileName, '/');
        $signedUrl = $baseUrl . '?Authorization=' . urlencode($authToken);

        // Armazena em cache
        $this->urlCache[$fileName . ':' . $duration] = $signedUrl;

        return $signedUrl;
    }

    public function fileExists($fileName)
    {
        $ch = curl_init($this->apiUrl . '/b2api/v2/b2_list_file_names');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $this->authorizationToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'bucketId' => $this->bucketId,
            'startFileName' => $fileName,
            'maxFileCount' => 1
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // 🔥 curl_close removido

        if ($httpCode !== 200) return false;
        $data = json_decode($response, true);
        return !empty($data['files']) && $data['files'][0]['fileName'] === $fileName;
    }

    /**
     * Deleta um arquivo do Backblaze B2 pelo nome.
     * 
     * @param string $fileName Nome do arquivo no bucket
     * @return bool True se deletado com sucesso, false se arquivo não encontrado
     * @throws Exception Se a exclusão falhar por outro motivo
     */
    public function deleteFile($fileName)
    {
        $ch = curl_init($this->apiUrl . '/b2api/v2/b2_list_file_names');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $this->authorizationToken,
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'bucketId' => $this->bucketId,
            'startFileName' => $fileName,
            'maxFileCount' => 1
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // 🔥 curl_close removido

        if ($httpCode !== 200) {
            throw new Exception("[B2Client] Falha ao listar arquivos (HTTP $httpCode): $response");
        }

        $data = json_decode($response, true);
        if (empty($data['files'])) {
            return false;
        }

        $file = $data['files'][0];
        if ($file['fileName'] !== $fileName) {
            return false;
        }

        $fileId = $file['fileId'];

        $ch = curl_init($this->apiUrl . '/b2api/v2/b2_delete_file_version');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $this->authorizationToken,
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'fileId' => $fileId,
            'fileName' => $fileName
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // 🔥 curl_close removido

        if ($httpCode !== 200) {
            throw new Exception("[B2Client] Falha ao deletar arquivo (HTTP $httpCode): $response");
        }

        error_log("[B2Client] Arquivo deletado: $fileName (fileId: $fileId)");
        return true;
    }

    /**
     * Codifica uma string para Base64 URL-safe (conforme exigido pelo B2).
     * 
     * @param string $data
     * @return string
     */
    private function urlSafeBase64Encode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * 🔒 Impede clonagem da instância (Singleton)
     */
    private function __clone() {}

    /**
     * 🔒 Impede desserialização da instância (Singleton)
     */
    public function __wakeup() {}
}