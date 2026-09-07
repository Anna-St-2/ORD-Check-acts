<?php
echo "<h2>Проверка PhpSpreadsheet</h2>";

// Проверяем расширения
$extensions = ['gd', 'zip', 'xml', 'mbstring'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext - установлено<br>";
    } else {
        echo "❌ $ext - НЕ установлено<br>";
    }
}

echo "<br>";

// Проверяем установку библиотеки
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "✅ vendor/autoload.php найден<br>";
    require_once __DIR__ . '/vendor/autoload.php';
    
    if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        echo "✅ PhpSpreadsheet установлен корректно<br>";
    } else {
        echo "❌ PhpSpreadsheet не найден<br>";
    }
} else {
    echo "❌ vendor/autoload.php НЕ найден<br>";
    echo "Выполните: composer require phpoffice/phpspreadsheet<br>";
}
?>