<?php

require_once __DIR__ . '/ActNumberNormalizer.php';
/**
 * Класс для преобразования данных из API в строки таблицы
 */
class ApiDataParser
{

    /**
     * Преобразует данные заказа в строку для правой таблицы
     */
    public function parseOrder($order, $contract, $legalEntity, $mediaplans)
    {
        $actNumber = ActNumberNormalizer::fromOrder($order);
        // ===== Основные поля заказа =====
        // Для "Номер акта" берём orderNumber (чистое число, как в файле)
        // Из orderNumber убираем префикс "ORD - "
        $reportingDate = $this->formatDate($order['reportingDate'] ?? '');

        $periodStart = $this->formatDate($order['startDate'] ?? '');
        $periodEnd = $this->formatDate($order['endDate'] ?? '');
        $period = ($periodStart && $periodEnd) ? "$periodStart — $periodEnd" : '';

        $ourCompany = $order['ourCompanyName'] ?? '';

        // ===== Договор (для даты; номер уже есть в order) =====
        $contractNumber = $order['contractNumber'] ?? ($contract['contractNumber'] ?? '');
        $contractDate = $this->formatDateTime($contract['startDate'] ?? '');

        // ===== Юрлицо (name + inn) =====
        $contractorName = $legalEntity['name'] ?? '';
        $inn = $legalEntity['inn'] ?? '';

        // ===== Суммы =====
        $amountWithoutVat = $this->formatNumber($order['amountWithoutVatRub'] ?? 0);
        $vatPct = isset($order['vatPct']) ? $order['vatPct'] . '%' : '';
        $vatRub = $this->formatNumber($order['vatRub'] ?? 0);
        $amountWithVat = $this->formatNumber($order['amountWithVatRub'] ?? 0);

        // ===== ID пункта = UUID заказа =====
        $orderId = $order['id'] ?? '';

        // ===== Расчёт показов =====
        $impressions = $this->calculateImpressions($mediaplans);

        return [
            'Номер акта' => $actNumber,
            'Дата акта' => $reportingDate,
            'Период оказания' => $period,
            'Наша компания' => $ourCompany,
            'Номер ДД' => $contractNumber,
            'Дата ДД' => $contractDate,
            'Наименование контрагента' => $contractorName,
            'ИНН заказчика' => $inn,
            'Сумма без НДС' => $amountWithoutVat,
            'Ставка НДС' => $vatPct,
            'Сумма НДС' => $vatRub,
            'Сумма с НДС' => $amountWithVat,
            'ID пункта' => $orderId,
            'Общее число фактич. показов' => '',
            'Общее число показов по акту' => $impressions['value'],
            '_is_cpm' => $impressions['is_cpm'],
        ];
    }

    /**
     * Считает плановые показы из медиаплана.
     *   - unitName = "1000 показов" (CPM) → volume × 1000
     *   - остальное (штук, клики и т.п.) → показов нет, возвращаем объём
     *
     * manualImpressions из API сознательно не используется:
     * фактические показы вводятся вручную в compare.php.
     */
    private function calculateImpressions($mediaplans)
    {
        if (empty($mediaplans)) {
            return ['value' => '', 'is_cpm' => false];
        }

        $totalVolume = 0;
        $hasCpm = false;
        $otherTypes = [];

        foreach ($mediaplans as $mp) {
            $unitName = $mp['unitName'] ?? '';
            $volume = $mp['volume'] ?? 0;

            if (stripos($unitName, '1000 показов') !== false) {
                $totalVolume += (int) $volume;
                $hasCpm = true;
            } else {
                if (!empty($unitName)) {
                    $otherTypes[$unitName] = true;
                }
            }
        }

        if ($hasCpm) {
            return [
                'value' => number_format($totalVolume, 0, '.', ' '),
                'is_cpm' => true,
            ];
        }

        if (!empty($otherTypes)) {
            return [
                'value' => implode(' / ', array_keys($otherTypes)),
                'is_cpm' => false,
            ];
        }

        return ['value' => '', 'is_cpm' => false];
    }

    private function formatDate($date)
    {
        if (empty($date))
            return '';
        $ts = strtotime($date);
        if ($ts === false)
            return $date;
        return date('d.m.Y', $ts);
    }

    private function formatDateTime($datetime)
    {
        if (empty($datetime))
            return '';
        $datePart = substr($datetime, 0, 10);
        return $this->formatDate($datePart);
    }

    private function formatNumber($number)
    {
        if (!is_numeric($number))
            return $number;
        return number_format((float) $number, 2, '.', ' ');
    }
}
?>