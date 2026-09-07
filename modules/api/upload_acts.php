<?php
// Отключаем вывод ошибок в ответ
error_reporting(0);
ini_set('display_errors', 0);

// Начинаем сессию
session_start();

// Устанавливаем заголовок JSON
header('Content-Type: application/json; charset=utf-8');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

// Проверяем, что файл загружен
if (!isset($_FILES['actsFile']) || $_FILES['actsFile']['error'] !== UPLOAD_ERR_OK) {
    $error_msg = isset($_FILES['actsFile']) ? 'Ошибка загрузки: ' . $_FILES['actsFile']['error'] : 'Файл не передан';
    echo json_encode(['success' => false, 'error' => $error_msg]);
    exit;
}

// Проверяем наличие автозагрузчика
$autoloadPaths = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php'
];

$autoloadFound = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloadFound = true;
        break;
    }
}

if (!$autoloadFound) {
    echo json_encode(['success' => false, 'error' => 'Библиотека PhpSpreadsheet не установлена']);
    exit;
}

if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    echo json_encode(['success' => false, 'error' => 'Класс PhpSpreadsheet не найден']);
    exit;
}

$file = $_FILES['actsFile'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

$allowed_extensions = ['xlsx', 'xls'];
if (!in_array($file_ext, $allowed_extensions)) {
    echo json_encode(['success' => false, 'error' => 'Неподдерживаемый формат. Поддерживаются: .xlsx, .xls']);
    exit;
}

try {
    $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file['tmp_name']);
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
    $spreadsheet = $reader->load($file['tmp_name']);
    
    $sheet = $spreadsheet->getSheetByName('Пункты актов');
    if (!$sheet) {
        $sheet = $spreadsheet->getActiveSheet();
    }
    
    $rows = $sheet->toArray();
    
    if (count($rows) < 2) {
        echo json_encode(['success' => false, 'error' => 'Файл не содержит данных']);
        exit;
    }
    
    $headers = array_map('trim', $rows[0]);
    
    // Список нужных столбцов с приоритетами
    $requiredColumns = [
        'Номер акта' => null,
        'Дата акта' => null,
        'Период оказания услуг по акту' => null,
        'Id конечного договора' => null,
        'Номер конечного договора' => null,
        'Дата конечного договора' => null,
        'ИНН заказчика КД' => null,
        'Сумма без НДС' => null,
        'Ставка НДС' => null,
        'Сумма НДС' => null,
        'Сумма с НДС' => null,
        'ID пункта акта' => null,
        'Общее число фактических показов' => null,
        'Общее число показов по акту' => null,
        'Общая сумма без НДС статистики' => null,
        'Общая сумма НДС статистики' => null,
        'Общая сумма с НДС статистики' => null
    ];
    
    // Ищем соответствие заголовков с учётом приоритетов
    // Сначала ищем точные совпадения
    foreach ($headers as $columnIndex => $header) {
        $header = trim($header);
        if (empty($header)) continue;
        
        foreach ($requiredColumns as $key => &$value) {
            if ($value === null && $header === $key) {
                $value = (int)$columnIndex;
                break;
            }
        }
        unset($value);
    }
    
    // Затем ищем частичные совпадения, но с учётом приоритетов
    foreach ($headers as $columnIndex => $header) {
        $header = trim($header);
        if (empty($header)) continue;
        
        $headerLower = mb_strtolower($header);
        
        foreach ($requiredColumns as $key => &$value) {
            if ($value !== null) continue; // Уже найдено точное совпадение
            
            $keyLower = mb_strtolower($key);
            
            // Проверяем частичное совпадение
            if (strpos($headerLower, $keyLower) !== false || strpos($keyLower, $headerLower) !== false) {
                // Дополнительная проверка для "Сумма НДС" и "Сумма с НДС"
                // Они не должны совпадать с "Общая сумма НДС статистики" и "Общая сумма с НДС статистики"
                if (($key === 'Сумма НДС' && strpos($headerLower, 'общая') !== false) ||
                    ($key === 'Сумма с НДС' && strpos($headerLower, 'общая') !== false)) {
                    continue;
                }
                
                // Для "Общая сумма НДС статистики" и "Общая сумма с НДС статистики" ищем точнее
                if (($key === 'Общая сумма НДС статистики' && strpos($headerLower, 'общая') === false) ||
                    ($key === 'Общая сумма с НДС статистики' && strpos($headerLower, 'общая') === false)) {
                    continue;
                }
                
                $value = (int)$columnIndex;
                break;
            }
        }
        unset($value);
    }
    
    // Проверяем, какие столбцы найдены
    $foundColumns = array_filter($requiredColumns, function($v) { return $v !== null; });
    
    if (empty($foundColumns)) {
        echo json_encode([
            'success' => false,
            'error' => 'Не найдено ни одного подходящего столбца. Проверьте названия столбцов в файле.',
            'headers' => $headers
        ]);
        exit;
    }
    
    // Функция для проверки, является ли значение датой Excel
    function isExcelDate($value) {
        if (!is_numeric($value) || $value <= 0) return false;
        return $value >= 1 && $value <= 2958465;
    }
    
    // Парсим данные
    $data = [];
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        $rowData = [];
        
        foreach ($requiredColumns as $key => $columnIndex) {
            if (is_int($columnIndex) && $columnIndex >= 0 && isset($row[$columnIndex])) {
                $value = $row[$columnIndex];
                
                // Проверяем, нужно ли обрабатывать как дату
                $isDateColumn = strpos($key, 'Дата') !== false || strpos($key, 'Период') !== false;
                
                // Проверяем, является ли значение датой Excel
                if (is_numeric($value) && isExcelDate($value) && $isDateColumn) {
                    try {
                        $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                        if ($dateTime) {
                            $value = $dateTime->format('d.m.Y');
                        }
                    } catch (Exception $e) {
                        // Не дата, оставляем как есть
                    }
                }
                
                // Если это числовое значение и НЕ дата
                if (is_numeric($value) && !$isDateColumn) {
                    // Проверяем, является ли это ID, номером или ИНН
                    $isIdColumn = strpos($key, 'ID') !== false || 
                                  strpos($key, 'Id') !== false || 
                                  strpos($key, 'Номер') !== false || 
                                  strpos($key, 'ИНН') !== false ||
                                  strpos($key, 'показов') !== false;
                    
                    if ($isIdColumn) {
                        $value = number_format($value, 0, '.', '');
                    } else {
                        $value = number_format($value, 2, '.', ' ');
                    }
                }
                
                $rowData[$key] = $value;
            } else {
                $rowData[$key] = '';
            }
        }
        
        if (!empty($rowData['Номер акта'])) {
            $data[] = $rowData;
        }
    }
    
    if (empty($data)) {
        echo json_encode([
            'success' => false,
            'error' => 'Не найдено данных. Проверьте, что в файле есть строки с заполненным столбцом "Номер акта"',
            'found_columns' => array_keys($foundColumns),
            'total_rows' => count($rows)
        ]);
        exit;
    }
    
    $_SESSION['acts_data'] = $data;
    $_SESSION['uploaded_file_name'] = $file['name'];
    
    echo json_encode([
        'success' => true,
        'count' => count($data),
        'found_columns' => array_keys($foundColumns),
        'message' => 'Файл успешно обработан. Найдено записей: ' . count($data)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка при обработке файла: ' . $e->getMessage()
    ]);
}
?>