<?php
/**
 * Класс для работы с API MonkeyCopilot
 */
class ApiClient {
    private $baseUrl;
    private $apiKey;
    private $timeout;
    private $lastError;
    private $debug = false;
    
    // Кэш для текущего запуска (в памяти)
    private $cache = [
        'contracts' => [],
        'legal_entities' => [],
        'mediaplans' => [],
    ];
    
    public function __construct() {
        $config = require __DIR__ . '/../../config/api_config.php';
        
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->apiKey = $config['api_key'];
        $this->timeout = $config['timeout'] ?? 120;
        $this->lastError = null;
    }
    
    /**
     * Проверка токена
     */
    public function ping() {
        return $this->request('GET', '/ping');
    }
    
    /**
     * Получить список заказов за период
     */
    public function getOrders($dateFrom, $dateTo) {
        // dateTo — exclusive, добавляем 1 день
        $exclusiveDateTo = date('Y-m-d', strtotime($dateTo . ' +1 day'));
        
        $result = $this->request('GET', '/orders', [
            'dateFrom' => $dateFrom,
            'dateTo' => $exclusiveDateTo,
            'scope' => 'all'
        ]);
        
        if ($result && isset($result['items'])) {
            return $result['items'];
        }
        
        return null;
    }
    
    /**
     * Получить договор по ID (с кэшированием)
     */
    public function getContract($contractId) {
        if (empty($contractId)) return null;
        
        if (isset($this->cache['contracts'][$contractId])) {
            return $this->cache['contracts'][$contractId];
        }
        
        $result = $this->request('GET', '/contracts/' . $contractId);
        
        if ($result) {
            $this->cache['contracts'][$contractId] = $result;
        }
        
        return $result;
    }
    
    /**
     * Получить юрлицо по ID (с кэшированием)
     */
    public function getLegalEntity($legalEntityId) {
        if (empty($legalEntityId)) return null;
        
        if (isset($this->cache['legal_entities'][$legalEntityId])) {
            return $this->cache['legal_entities'][$legalEntityId];
        }
        
        $result = $this->request('GET', '/legal-entities/' . $legalEntityId);
        
        if ($result) {
            $this->cache['legal_entities'][$legalEntityId] = $result;
        }
        
        return $result;
    }
    
    /**
     * Получить медиапланы заказа (с кэшированием)
     */
    public function getMediaplans($orderId) {
        if (empty($orderId)) return null;
        
        if (isset($this->cache['mediaplans'][$orderId])) {
            return $this->cache['mediaplans'][$orderId];
        }
        
        $result = $this->request('GET', '/mediaplans', ['orderId' => $orderId]);
        
        $items = null;
        if ($result && isset($result['items'])) {
            $items = $result['items'];
            $this->cache['mediaplans'][$orderId] = $items;
        }
        
        return $items;
    }
    
    /**
     * Выполнить запрос к API
     * 
     * @param string $method 'GET' или 'POST'
     * @param string $endpoint Эндпоинт (например, '/orders')
     * @param array $params Параметры
     * @return array|null
     */
    private function request($method, $endpoint, $params = []) {
        $url = $this->baseUrl . $endpoint;
        
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        if ($this->debug) {
            error_log("API Request: $method $url");
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_ENCODING, '');  // gzip
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
            'Accept-Encoding: gzip, deflate',
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            $this->lastError = 'CURL Error: ' . $curlError;
            error_log("API Error: " . $this->lastError);
            return null;
        }
        
        if ($httpCode < 200 || $httpCode >= 300) {
            $this->lastError = "HTTP $httpCode: " . substr($response, 0, 500);
            error_log("API Error: " . $this->lastError);
            return null;
        }
        
        $data = json_decode($response, true);
        
        if ($data === null) {
            $this->lastError = 'JSON Parse Error';
            return null;
        }
        
        if (isset($data['error'])) {
            $this->lastError = 'API Error: ' . ($data['error']['message'] ?? 'Unknown');
            return null;
        }
        
        return $data;
    }
    
    public function getLastError() {
        return $this->lastError;
    }
}