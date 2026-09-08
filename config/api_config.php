<?php
// Загружаем переменные из .env
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

return [
    'base_url' => $_ENV['API_BASE_URL'] ?? 'https://app.monkeycopilot.ru/api/v1',
    'api_key' => $_ENV['API_KEY'] ?? '',
    'timeout' => 30,
    'retry_attempts' => 3,
    'retry_delay' => 5,
];