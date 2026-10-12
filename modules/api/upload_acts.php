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
    
    // ============================================================
    // ШАГ 1. Находим все вхождения столбцов с одинаковыми названиями
    // ============================================================
    
    // Собираем все индексы для каждого названия
    $headerIndexes = [];
    foreach ($headers as $idx => $header) {
        $header = trim($header);
        if (empty($header)) continue;
        if (!isset($headerIndexes[$header])) {
            $headerIndexes[$header] = [];
        }
        $headerIndexes[$header][] = $idx;
    }
    
    // ============================================================
    // ШАГ 2. Определяем индексы нужных столбцов
    // ============================================================
    
    // Уникальные столбцы (только одно вхождение)
    $uniqueColumns = [
        'Номер акта',
        'Дата акта',
        'Период оказания услуг по акту',
        'Номер конечного договора',
        'Дата конечного договора',
        'ИНН заказчика КД',
        'ID пункта акта',
        'Общее число фактических показов',
        'Общее число показов по акту',
        'Номер изначального договора',
        'Дата заключения ИД',
        'Общая сумма без НДС статистики',
        'Общая сумма НДС статистики',
        'Общая сумма с НДС статистики',
    ];
    
    $columnIndex = []; // [название] => индекс
    
    foreach ($uniqueColumns as $col) {
        if (isset($headerIndexes[$col]) && !empty($headerIndexes[$col])) {
            $columnIndex[$col] = (int)$headerIndexes[$col][0]; // первый
        } else {
            // Пробуем частичное совпадение
            foreach ($headers as $idx => $header) {
                $header = trim($header);
                if (empty($header)) continue;
                if (mb_stripos($header, $col) !== false || mb_stripos($col, $header) !== false) {
                    $columnIndex[$col] = (int)$idx;
                    break;
                }
            }
        }
    }
    
    // Дублирующиеся столбцы (два вхождения) — Сумма без НДС, Ставка НДС, Сумма НДС, Сумма с НДС
    $duplicatedColumns = [
        'Сумма без НДС',
        'Ставка НДС',
        'Сумма НДС',
        'Сумма с НДС',
    ];
    
    // Для каждого дублирующегося столбца берем первый и второй индекс
    foreach ($duplicatedColumns as $col) {
        if (isset($headerIndexes[$col]) && count($headerIndexes[$col]) >= 2) {
            // Есть два вхождения
            $indexes = $headerIndexes[$col];
            sort($indexes);
            $columnIndex[$col . ' (акт)'] = $indexes[0];      // первый — по акту
            $columnIndex[$col . ' (пункт)'] = $indexes[1];    // второй — по пункту
        } elseif (isset($headerIndexes[$col]) && count($headerIndexes[$col]) === 1) {
            // Только одно вхождение — используем его и для акта, и для пункта
            $columnIndex[$col . ' (акт)'] = $headerIndexes[$col][0];
            $columnIndex[$col . ' (пункт)'] = $headerIndexes[$col][0];
        } else {
            // Частичный поиск
            $matches = [];
            foreach ($headers as $idx => $header) {
                $header = trim($header);
                if (empty($header)) continue;
                if (mb_stripos($header, $col) !== false) {
                    $matches[] = $idx;
                }
            }
            if (count($matches) >= 2) {
                $columnIndex[$col . ' (акт)'] = $matches[0];
                $columnIndex[$col . ' (пункт)'] = $matches[1];
            } elseif (count($matches) === 1) {
                $columnIndex[$col . ' (акт)'] = $matches[0];
                $columnIndex[$col . ' (пункт)'] = $matches[0];
            }
        }
    }
    
    // Проверяем обязательные столбцы
    if (!isset($columnIndex['Номер акта'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Не найден столбец "Номер акта". Проверьте структуру файла.',
            'headers' => $headers
        ]);
        exit;
    }
    
    // ============================================================
    // ШАГ 3. Парсим данные
    // ============================================================
    
    $data = [];
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        $rowData = [];
        
        foreach ($columnIndex as $key => $idx) {
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
                
                // Форматирование чисел только для сумм и показов
                $isNumericField = (strpos($key, 'Сумма') !== false || strpos($key, 'число') !== false);
                if ($isNumericField && is_numeric($value) && !is_int($value)) {
                    $value = number_format((float)$value, 2, '.', ' ');
                } elseif ($isNumericField && is_numeric($value) && is_int($value)) {
                    $value = number_format((float)$value, 0, '.', ' ');
                }
                
                $rowData[$key] = $value;
            } else {
                $rowData[$key] = '';
            }
        }
        
        // Проверяем, что строка не пустая
        if (!empty($rowData['Номер акта'])) {
            $data[] = $rowData;
        }
    }
    
    if (empty($data)) {
        echo json_encode([
            'success' => false,
            'error' => 'Не найдено данных'
        ]);
        exit;
    }
    
    $_SESSION['acts_data'] = $data;
    $_SESSION['uploaded_file_name'] = $file['name'];
    
    echo json_encode([
        'success' => true,
        'count' => count($data),
        'columns_found' => $columnIndex,
        'message' => 'Файл успешно обработан. Найдено записей: ' . count($data)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка при обработке файла: ' . $e->getMessage()
    ]);
}
?>