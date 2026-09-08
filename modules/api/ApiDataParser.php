<?php
/**
 * Класс для парсинга и преобразования данных из API
 * в формат, совместимый с таблицей сравнения
 */
class ApiDataParser {
    
    /**
     * Преобразует данные из API в формат для таблицы
     * 
     * @param array $order Данные заказа из /orders
     * @param array $legalEntity Данные юрлица из /legal-entities
     * @param array $mediaplans Данные медиапланов из /mediaplans
     * @return array Подготовленная строка для таблицы
     */
    public function parseOrderData($order, $legalEntity, $mediaplans) {
        // 1. Номер акта
        $actNumber = $order['orderNumber'] ?? '';
        
        // 2. Дата акта (reportingDate)
        $actDate = $order['reportingDate'] ?? '';
        if ($actDate) {
            $actDate = date('d.m.Y', strtotime($actDate));
        }
        
        // 3. Период оказания
        $startDate = $order['startDate'] ?? '';
        $endDate = $order['endDate'] ?? '';
        $period = '';
        if ($startDate && $endDate) {
            $period = date('d.m.Y', strtotime($startDate)) . ' — ' . date('d.m.Y', strtotime($endDate));
        }
        
        // 4. Номер договора
        $contractNumber = $order['contractNumber'] ?? '';
        
        // 5. Дата договора - из карточки договора, пока оставляем пустым
        $contractDate = '';
        
        // 6. ИНН заказчика
        $inn = $legalEntity['inn'] ?? '';
        
        // 7. Сумма без НДС
        $amountWithoutVat = $order['amountWithoutVatRub'] ?? 0;
        if (is_numeric($amountWithoutVat)) {
            $amountWithoutVat = number_format($amountWithoutVat, 2, '.', ' ');
        }
        
        // 8. Ставка НДС
        $vatPct = $order['vatPct'] ?? 0;
        if ($vatPct) {
            $vatPct = $vatPct . '%';
        }
        
        // 9. Сумма НДС
        $vatRub = $order['vatRub'] ?? 0;
        if (is_numeric($vatRub)) {
            $vatRub = number_format($vatRub, 2, '.', ' ');
        }
        
        // 10. Сумма с НДС
        $amountWithVat = $order['amountWithVatRub'] ?? 0;
        if (is_numeric($amountWithVat)) {
            $amountWithVat = number_format($amountWithVat, 2, '.', ' ');
        }
        
        // 11. ID пункта акта - берем id заказа
        $itemId = $order['id'] ?? '';
        
        // 12. Показы факт. - поле для ручного ввода (пустое)
        $impressionsFact = '';
        
        // 13. Показы по акту - сумма volume из медиапланов с unitName = "1000 показов"
        $impressionsAct = $this->calculateImpressions($mediaplans);
        
        // 14-16. Статистика - пока оставляем пустым (будет позже)
        $statNoNds = '';
        $statNds = '';
        $statWithNds = '';
        
        return [
            'Номер акта' => $actNumber,
            'Дата акта' => $actDate,
            'Период оказания' => $period,
            'Номер договора' => $contractNumber,
            'Дата договора' => $contractDate,
            'ИНН заказчика' => $inn,
            'Сумма без НДС' => $amountWithoutVat,
            'Ставка НДС' => $vatPct,
            'Сумма НДС' => $vatRub,
            'Сумма с НДС' => $amountWithVat,
            'ID пункта акта' => $itemId,
            'Показы факт.' => $impressionsFact,
            'Показы по акту' => $impressionsAct,
            'Сумма без НДС (стат.)' => $statNoNds,
            'Сумма НДС (стат.)' => $statNds,
            'Сумма с НДС (стат.)' => $statWithNds,
            // Сохраняем raw данные для дальнейшего использования
            '_raw_order' => $order,
            '_raw_legal_entity' => $legalEntity,
            '_raw_mediaplans' => $mediaplans
        ];
    }
    
    /**
     * Рассчитывает показы по акту из медиапланов
     * Суммирует volume только для unitName = "1000 показов"
     * 
     * @param array $mediaplans Массив медиапланов
     * @return string Сумма показов или "клики/шт" если unitName не "1000 показов"
     */
    private function calculateImpressions($mediaplans) {
        if (empty($mediaplans)) {
            return '';
        }
        
        $totalVolume = 0;
        $hasNonImpression = false;
        $nonImpressionTypes = [];
        
        foreach ($mediaplans as $mp) {
            $unitName = $mp['unitName'] ?? '';
            $volume = $mp['volume'] ?? 0;
            
            // Проверяем, что это показы
            if (strpos(strtolower($unitName), '1000 показов') !== false) {
                $totalVolume += (int)$volume;
            } else {
                $hasNonImpression = true;
                $nonImpressionTypes[] = $unitName;
            }
        }
        
        // Если есть показы - возвращаем сумму
        if ($totalVolume > 0) {
            return number_format($totalVolume, 0, '.', ' ');
        }
        
        // Если показов нет, но есть другие типы
        if ($hasNonImpression) {
            $uniqueTypes = array_unique($nonImpressionTypes);
            return implode('/', $uniqueTypes);
        }
        
        return '';
    }
}
?>