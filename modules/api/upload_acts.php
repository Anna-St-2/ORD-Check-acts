<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

if (!isset($_FILES['actsFile']) || $_FILES['actsFile']['error'] !== UPLOAD_ERR_OK) {
    $error_msg = isset($_FILES['actsFile']) ? 'Ошибка загрузки: ' . $_FILES['actsFile']['error'] : 'Файл не передан';
    echo json_encode(['success' => false, 'error' => $error_msg]);
    exit;
}

if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    echo json_encode(['success' => false, 'error' => 'Библиотека PhpSpreadsheet не установлена']);
    exit;
}

require_once __DIR__ . '/../../vendor/autoload.php';

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
    
    // ---- 1. Находим ВСЕ индексы всех нужных столбцов ----
    // Сначала ищем все возможные совпадения для каждого поля
    $allMatches = [];
    $searchFields = [
        'Номер акта',
        'Дата акта',
        'Период оказания услуг по акту',
        'Номер конечного договора',
        'Дата конечного договора',
        'ИНН заказчика КД',
        'Сумма без НДС',
        'Ставка НДС',
        'Сумма НДС',
        'Сумма с НДС',
        'ID пункта акта',
        'Общее число фактических показов',
        'Общее число показов по акту',
        'Номер изначального договора',
        'Дата заключения ИД',
    ];
    
    foreach ($headers as $idx => $header) {
        $header = trim($header);
        if (empty($header)) continue;
        $headerLower = mb_strtolower($header);
        
        foreach ($searchFields as $field) {
            $fieldLower = mb_strtolower($field);
            // Проверяем точное или частичное совпадение
            if ($header === $field || strpos($headerLower, $fieldLower) !== false || strpos($fieldLower, $headerLower) !== false) {
                if (!isset($allMatches[$field])) {
                    $allMatches[$field] = [];
                }
                $allMatches[$field][] = (int)$idx;
            }
        }
    }
    
    // ---- 2. Определяем, какие индексы использовать ----
    $fields = [
        // Общие поля акта (первые)
        'act' => [],
        // Поля ИД (для каждой строки)
        'id' => [],
        // Статистические поля (последние)
        'stat' => [],
    ];
    
    // Для общих полей берем ПЕРВЫЙ найденный индекс
    $actFields = [
        'Номер акта',
        'Дата акта',
        'Период оказания услуг по акту',
        'Номер конечного договора',
        'Дата конечного договора',
        'ИНН заказчика КД',
        'ID пункта акта',
        'Общее число фактических показов',
        'Общее число показов по акту',
    ];
    foreach ($actFields as $field) {
        if (isset($allMatches[$field]) && !empty($allMatches[$field])) {
            $fields['act'][$field] = min($allMatches[$field]); // берем первый (минимальный индекс)
        } else {
            $fields['act'][$field] = null;
        }
    }
    
    // Для полей ИД берем первый найденный
    $idFields = [
        'Номер изначального договора',
        'Дата заключения ИД',
    ];
    foreach ($idFields as $field) {
        if (isset($allMatches[$field]) && !empty($allMatches[$field])) {
            $fields['id'][$field] = min($allMatches[$field]);
        } else {
            $fields['id'][$field] = null;
        }
    }
    
    // Для статистических полей берем ПОСЛЕДНИЙ найденный индекс
    $statFields = [
        'Сумма без НДС',
        'Ставка НДС',
        'Сумма НДС',
        'Сумма с НДС',
    ];
    foreach ($statFields as $field) {
        if (isset($allMatches[$field]) && !empty($allMatches[$field])) {
            // Проверяем, что это НЕ первый индекс (если их несколько)
            if (count($allMatches[$field]) > 1) {
                $fields['stat'][$field] = max($allMatches[$field]); // берем последний (максимальный индекс)
            } else {
                // Если только один, пробуем найти по названию "Общая сумма ... статистики"
                $statKey = 'Общая ' . strtolower($field) . ' статистики';
                $found = false;
                foreach ($allMatches as $key => $indexes) {
                    if (strpos(strtolower($key), strtolower($field)) !== false && strpos(strtolower($key), 'статистики') !== false) {
                        $fields['stat'][$field] = max($indexes);
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    // Если не нашли, берем единственный (но это может быть неправильно)
                    $fields['stat'][$field] = min($allMatches[$field]);
                }
            }
        } else {
            $fields['stat'][$field] = null;
        }
    }
    
    // ---- 3. Проверяем наличие обязательных полей ----
    $required = ['Номер акта'];
    $missing = [];
    foreach ($required as $field) {
        if (!isset($fields['act'][$field]) || $fields['act'][$field] === null) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        echo json_encode([
            'success' => false,
            'error' => 'Не найдены обязательные столбцы: ' . implode(', ', $missing)
        ]);
        exit;
    }
    
    // ---- 4. Парсим данные ----
    $data = [];
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        $rowData = [];
        
        // Общие поля акта
        foreach ($fields['act'] as $key => $idx) {
            if ($idx !== null && isset($row[$idx])) {
                $value = $row[$idx];
                // Обработка дат
                if (strpos($key, 'Дата') !== false && is_numeric($value) && $value > 0) {
                    try {
                        $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                        if ($dateTime) {
                            $value = $dateTime->format('d.m.Y');
                        }
                    } catch (Exception $e) {}
                }
                $rowData[$key] = $value;
            } else {
                $rowData[$key] = '';
            }
        }
        
        // Поля ИД
        foreach ($fields['id'] as $key => $idx) {
            if ($idx !== null && isset($row[$idx])) {
                $value = $row[$idx];
                // Обработка дат
                if (strpos($key, 'Дата') !== false && is_numeric($value) && $value > 0) {
                    try {
                        $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                        if ($dateTime) {
                            $value = $dateTime->format('d.m.Y');
                        }
                    } catch (Exception $e) {}
                }
                $rowData[$key] = $value;
            } else {
                $rowData[$key] = '';
            }
        }
        
        // Статистические поля (суммы по пункту)
        foreach ($fields['stat'] as $key => $idx) {
            if ($idx !== null && isset($row[$idx])) {
                $value = $row[$idx];
                if (is_numeric($value) && !is_int($value)) {
                    $value = number_format($value, 2, '.', ' ');
                } elseif (is_numeric($value) && is_int($value)) {
                    $value = number_format($value, 0, '.', ' ');
                }
                // Сохраняем как "Общая сумма ... статистики" для совместимости с compare.php
                $statKey = 'Общая ' . $key . ' статистики';
                $rowData[$statKey] = $value;
            } else {
                $statKey = 'Общая ' . $key . ' статистики';
                $rowData[$statKey] = '';
            }
        }
        
        // Проверяем, что строка не пустая (есть номер акта)
        if (!empty($rowData['Номер акта'])) {
            $data[] = $rowData;
        }
    }
    
    if (empty($data)) {
        echo json_encode([
            'success' => false,
            'error' => 'Не найдено данных. Проверьте, что в файле есть строки с заполненным столбцом "Номер акта"'
        ]);
        exit;
    }
    
    $_SESSION['acts_data'] = $data;
    $_SESSION['uploaded_file_name'] = $file['name'];
    
    echo json_encode([
        'success' => true,
        'count' => count($data),
        'message' => 'Файл успешно обработан. Найдено записей: ' . count($data)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка при обработке файла: ' . $e->getMessage()
    ]);
}
?>