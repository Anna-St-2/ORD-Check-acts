<?php

class ActComparator
{
    private $apiData;
    private $manualImpressions;

    public function __construct(array $apiData, array $manualImpressions = [])
    {
        $this->apiData = [];
        foreach ($apiData as $row) {
            $actNum = trim((string) ($row['Номер акта'] ?? ''));
            if ($actNum !== '')
                $this->apiData[$actNum] = $row;
        }
        $this->manualImpressions = $manualImpressions;
    }

    public function compare(array $groupedFileData): array
    {
        $results = [];
        foreach ($groupedFileData as $actNumber => $group) {
            $results[] = $this->compareAct((string) $actNumber, $group['items'] ?? []);
        }
        return $results;
    }

    private function compareAct(string $actNumber, array $items): array
    {
        $file = $this->aggregateFile($items);
        $api = $this->apiData[$actNumber] ?? null;

        if ($api === null) {
            return [
                'actNumber' => $actNumber,
                'status' => 'not_found',
                'message' => 'Акт не обнаружен в API',
                'checks' => [],
                'failed' => ['not_found'],
                'overall' => false,
            ];
        }

        $checks = [];
        $checks['date'] = $this->checkDate($file['date'], $api['Дата акта'] ?? '');
        $checks['period'] = $this->checkPeriod($file['period'], $api['Период оказания'] ?? '');
        $checks['contractNumber'] = $this->checkString($file['contractNumber'], $api['Номер ДД'] ?? '');
        $checks['contractDate'] = $this->checkDate($file['contractDate'], $api['Дата ДД'] ?? '');
        $checks['inn'] = $this->checkString($file['inn'], $api['ИНН заказчика'] ?? '');

        $checks['amountWithoutVat'] = $this->checkAmountExact($file['amountWithoutVat'], $api['Сумма без НДС'] ?? '');
        $checks['vatPct'] = $this->checkNumber($file['vatPct'], $api['Ставка НДС'] ?? '');
        $checks['vat'] = $this->checkAmountExact($file['vat'], $api['Сумма НДС'] ?? '');
        $checks['amountWithVat'] = $this->checkAmountExact($file['amountWithVat'], $api['Сумма с НДС'] ?? '');

        $checks['itemsAmountWithoutVat'] = $this->checkAmountExact($file['itemsAmountWithoutVat'], $api['Сумма без НДС'] ?? '');
        $checks['itemsVat'] = $this->checkAmountExact($file['itemsVat'], $api['Сумма НДС'] ?? '');
        $checks['itemsAmountWithVat'] = $this->checkAmountExact($file['itemsAmountWithVat'], $api['Сумма с НДС'] ?? '');

        // Статистика: каждая с допуском 5, плюс "одна совпала точно"
        $apiWithout = $this->parseAmount($api['Сумма без НДС'] ?? '');
        $apiWith = $this->parseAmount($api['Сумма с НДС'] ?? '');
        $diffWithout = abs($file['statWithoutVat'] - $apiWithout);
        $diffWith = abs($file['statWithVat'] - $apiWith);

        $withoutOk = ($diffWithout <= 5);
        $withOk = ($diffWith <= 5);
        $withoutExact = ($diffWithout < 0.005);
        $withExact = ($diffWith < 0.005);
        $statsOverall = ($withoutOk && $withOk && ($withoutExact || $withExact));

        $checks['statWithoutVat'] = ['ok' => $withoutOk, 'diff' => $diffWithout];
        $checks['statWithVat'] = ['ok' => $withOk, 'diff' => $diffWith];
        $checks['statsOverall'] = $statsOverall;

        $imp = $this->compareImpressions($actNumber, $file, $api);
        $checks['impressions'] = $imp;

        $failed = [];
        foreach (['date', 'period', 'contractNumber', 'contractDate', 'inn', 'amountWithoutVat', 'vatPct', 'vat', 'amountWithVat', 'itemsAmountWithoutVat', 'itemsVat', 'itemsAmountWithVat'] as $k) {
            if (empty($checks[$k]))
                $failed[] = $k;
        }
        if (!$statsOverall)
            $failed[] = 'stats';
        if (!$imp['ok'])
            $failed[] = 'impressions';

        return [
            'actNumber' => $actNumber,
            'status' => 'ok',
            'file' => $file,
            'api' => $api,
            'checks' => $checks,
            'failed' => $failed,
            'overall' => empty($failed),
        ];
    }

    private function aggregateFile(array $items): array
    {
        $first = $items[0] ?? [];
        return [
            'date' => $first['Дата акта'] ?? '',
            'period' => $first['Период оказания услуг по акту'] ?? '',
            'contractNumber' => $first['Номер конечного договора'] ?? '',
            'contractDate' => $first['Дата конечного договора'] ?? '',
            'inn' => $first['ИНН заказчика КД'] ?? '',
            'amountWithoutVat' => $first['Сумма без НДС (акт)'] ?? '',
            'vatPct' => $first['Ставка НДС (акт)'] ?? '',
            'vat' => $first['Сумма НДС (акт)'] ?? '',
            'amountWithVat' => $first['Сумма с НДС (акт)'] ?? '',
            'itemsAmountWithoutVat' => $this->sumField($items, 'Сумма без НДС (пункт)'),
            'itemsVat' => $this->sumField($items, 'Сумма НДС (пункт)'),
            'itemsAmountWithVat' => $this->sumField($items, 'Сумма с НДС (пункт)'),
            'statWithoutVat' => $this->parseAmount($first['Общая сумма без НДС статистики'] ?? ''),
            'statWithVat' => $this->parseAmount($first['Общая сумма с НДС статистики'] ?? ''),
            'factImpressions' => $first['Общее число фактических показов'] ?? '',
            'planImpressions' => $first['Общее число показов по акту'] ?? '',
        ];
    }

    private function sumField(array $items, string $key): float
    {
        $sum = 0.0;
        foreach ($items as $item)
            $sum += $this->parseAmount($item[$key] ?? '');
        return $sum;
    }

    private function compareImpressions(string $actNumber, array $file, array $api): array
    {
        $manual = trim((string) ($this->manualImpressions[$actNumber] ?? ''));
        $isCpm = !empty($api['_is_cpm']);

        if ($manual === '') {
            return [
                'ok' => false,
                'status' => 'no_input',
                'message' => 'Не введены фактические показы',
                'fact' => '',
                'plan' => ''
            ];
        }

        // ИСПОЛЬЗУЕМ parseAmount ВМЕСТО preg_replace, чтобы корректно обрабатывать "26 516 801.00"
        $manualNum = (int) $this->parseAmount($manual);
        $fileFact = (int) $this->parseAmount($file['factImpressions'] ?? '');
        $filePlan = (int) $this->parseAmount($file['planImpressions'] ?? '');
        $apiPlan = (int) $this->parseAmount($api['Общее число показов по акту'] ?? '');

        $factStr = number_format($manualNum, 0, '.', ' ');

        if (!$isCpm) {
            // Для штук, кликов, просмотров: факт = акт = ручной ввод
            $planStr = $factStr;

            if ($manualNum === $fileFact && $manualNum === $filePlan) {
                return ['ok' => true, 'status' => 'ok', 'message' => 'ок', 'fact' => $factStr, 'plan' => $planStr];
            }

            $msgs = [];
            if ($manualNum !== $fileFact)
                $msgs[] = 'факт не совпадает с файлом';
            if ($manualNum !== $filePlan)
                $msgs[] = 'показы по акту не совпадают с файлом';

            return ['ok' => false, 'status' => 'mismatch', 'message' => 'Данные не соответствуют: ' . implode(', ', $msgs), 'fact' => $factStr, 'plan' => $planStr];
        }

        // Для CPM: факт = ручной ввод, акт = данные из API
        $planStr = number_format($apiPlan, 0, '.', ' ');

        $factOk = ($manualNum === $fileFact);
        $planOk = ($apiPlan === $filePlan);
        $orderOk = ($manualNum >= $apiPlan);

        if (!$orderOk) {
            $diff = $apiPlan - $manualNum;
            $msg = 'Показов по факту меньше, чем по акту на ' . number_format($diff, 0, '.', ' ');
            if (!$factOk || !$planOk)
                $msg .= ' (также данные не соответствуют)';
            return ['ok' => false, 'status' => 'mismatch', 'message' => $msg, 'fact' => $factStr, 'plan' => $planStr];
        }

        if (!$factOk || !$planOk) {
            return ['ok' => false, 'status' => 'mismatch', 'message' => 'Данные не соответствуют', 'fact' => $factStr, 'plan' => $planStr];
        }

        return ['ok' => true, 'status' => 'ok', 'message' => 'ок', 'fact' => $factStr, 'plan' => $planStr];
    }
    private function parseAmount($value): float
    {
        if (is_numeric($value))
            return (float) $value;
        $s = str_replace(' ', '', (string) $value);
        $s = str_replace(',', '.', $s);
        $s = preg_replace('/[^\d.\-]/', '', $s);
        return (float) $s;
    }

    private function checkString($a, $b): bool
    {
        return trim((string) $a) === trim((string) $b);
    }

    private function checkDate($a, $b): bool
    {
        return $this->normalizeDate($a) === $this->normalizeDate($b);
    }

    private function normalizeDate($date): string
    {
        $date = trim((string) $date);
        if ($date === '')
            return '';
        $ts = strtotime($date);
        if ($ts === false)
            return $date;
        return date('Y-m-d', $ts);
    }

    private function checkPeriod($filePeriod, $apiPeriod): bool
    {
        [$f1, $f2] = $this->parsePeriod($filePeriod);
        [$a1, $a2] = $this->parsePeriod($apiPeriod);
        return $this->normalizeDate($f1) === $this->normalizeDate($a1)
            && $this->normalizeDate($f2) === $this->normalizeDate($a2);
    }

    private function parsePeriod($period): array
    {
        $period = trim((string) $period);
        if ($period === '')
            return ['', ''];
        $parts = explode('—', $period);
        if (count($parts) === 2)
            return [trim($parts[0]), trim($parts[1])];
        return [$period, ''];
    }

    private function checkAmountExact($a, $b): bool
    {
        return abs($this->parseAmount($a) - $this->parseAmount($b)) < 0.005;
    }

    private function checkNumber($a, $b): bool
    {
        return abs($this->parseAmount($a) - $this->parseAmount($b)) < 0.005;
    }
}