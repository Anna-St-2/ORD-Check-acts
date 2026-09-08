<?php
/**
 * Класс для работы с API MonkeyCopilot
 */
class ApiClient {
    private $baseUrl;
    private $apiKey;
    private $timeout;
    private $lastError;
    
    public function __construct() {
        $config = require __DIR__ . '/../../config/api_config.php';
        
        $this->baseUrl = $config['base_url'];
        $this->apiKey = $config['api_key'];
        $this->timeout = $config['timeout'] ?? 30;
        $this->lastError = null;
    }
    
    /**
     * Проверка токена (ping)
     */
    public function ping() {
        return $this->request('GET', '/ping');
    }
    
    /**
     * Получить список заказов с фильтрацией
     * 
     * @param string $dateFrom Начало периода (YYYY-MM-DD)
     * @param string $dateTo Конец периода (YYYY-MM-DD)
     * @param array $departmentIds Список UUID департаментов
     * @return array|null Массив заказов или null при ошибке
     */
    public function getOrders($dateFrom, $dateTo, $departmentIds = []) {
        $params = [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ];
        
        if (!empty($departmentIds)) {
            $params['departmentIds'] = implode(',', $departmentIds);
        }
        
        $result = $this->request('GET', '/orders', $params);
        
        if ($result && isset($result['items'])) {
            return $result['items'];
        }
        
        return null;
    }
    
    /**
     * Получить юрлицо по ID (для ИНН)
     * 
     * @param string $legalEntityId UUID юрлица
     * @return array|null Данные юрлица или null при ошибке
     */
    public function getLegalEntity($legalEntityId) {
        $result = $this->request('GET', '/legal-entities/' . $legalEntityId);
        return $result;
    }
    
    /**
     * Получить медиапланы заказа
     * 
     * @param string $orderId UUID заказа
     * @return array|null Массив медиапланов или null при ошибке
     */
    public function getMediaplans($orderId) {
        $result = $this->request('GET', '/mediaplans', ['orderId' => $orderId]);
        
        if ($result && isset($result['items'])) {
            return $result['items'];
        }
        
        return null;
    }
    
    /**
     * Получить все данные для периода с детализацией
     * 
     * @param string $dateFrom Начало периода
     * @param string $dateTo Конец периода
     * @param array $departmentIds Список UUID департаментов
     * @return array Массив с данными
     */
    public function getFullData($dateFrom, $dateTo, $departmentIds = []) {
        $result = [
            'orders' => [],
            'errors' => []
        ];
        
        // 1. Получаем список заказов
        $orders = $this->getOrders($dateFrom, $dateTo, $departmentIds);
        
        if ($orders === null) {
            $result['errors'][] = 'Не удалось получить список заказов: ' . $this->getLastError();
            return $result;
        }
        
        if (empty($orders)) {
            $result['errors'][] = 'Заказов за указанный период не найдено';
            return $result;
        }
        
        // 2. Для каждого заказа получаем детали
        foreach ($orders as $order) {
            $orderId = $order['id'] ?? null;
            
            if (!$orderId) {
                continue;
            }
            
            $orderData = [
                'order' => $order,
                'legal_entity' => null,
                'mediaplans' => []
            ];
            
            // Получаем юрлицо (для ИНН)
            $legalEntityId = $order['legalEntityId'] ?? null;
            if ($legalEntityId) {
                $legalEntity = $this->getLegalEntity($legalEntityId);
                if ($legalEntity) {
                    $orderData['legal_entity'] = $legalEntity;
                } else {
                    $result['errors'][] = "Не удалось получить юрлицо для заказа {$orderId}";
                }
            }
            
            // Получаем медиапланы
            $mediaplans = $this->getMediaplans($orderId);
            if ($mediaplans !== null) {
                $orderData['mediaplans'] = $mediaplans;
            } else {
                $result['errors'][] = "Не удалось получить медиапланы для заказа {$orderId}";
            }
            
            $result['orders'][] = $orderData;
        }
        
        return $result;
    }
    
    /**
     * Выполняет запрос к API
     */
    private function request($method, $endpoint, $params = []) {
        $url = $this->baseUrl . $endpoint;
        
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            $this->lastError = 'CURL Error: ' . $curlError;
            return null;
        }
        
        if ($httpCode < 200 || $httpCode >= 300) {
            $this->lastError = 'HTTP Error: ' . $httpCode;
            return null;
        }
        
        $data = json_decode($response, true);
        
        if ($data === null) {
            $this->lastError = 'JSON Parse Error';
            return null;
        }
        
        if (isset($data['error'])) {
            $this->lastError = 'API Error: ' . ($data['error']['message'] ?? 'Unknown error');
            return null;
        }
        
        return $data;
    }
    
    public function getLastError() {
        return $this->lastError;
    }
}
?>