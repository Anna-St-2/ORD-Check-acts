<?php

class ActNumberNormalizer
{
    /**
     * Достаёт первый непрерывный блок цифр.
     *
     * "ORD - 28144"       -> "28144"
     * " 28144 "           -> "28144"
     * "ORD-28144-2"       -> "28144"
     * "28144/13.07.2026"  -> "28144"  (на всякий случай, если попадёт)
     * "акт № 28144"       -> "28144"
     * ""                  -> ""
     */
    public static function normalize($value): string
    {
        $value = trim((string)$value);

        if ($value === '') {
            return '';
        }

        if (preg_match('/\d+/', $value, $m)) {
            return $m[0];
        }

        return '';
    }

    /**
     * Номер акта из заказа API.
     * Единственный источник — orderNumber.
     * applicationNumber сознательно не используется.
     */
    public static function fromOrder(array $order): string
    {
        return self::normalize($order['orderNumber'] ?? '');
    }

    /**
     * Диагностика: почему номер не извлёкся.
     * Возвращает null, если всё ок, или строку-причину.
     */
    public static function diagnoseOrder(array $order): ?string
    {
        $raw = $order['orderNumber'] ?? null;

        if ($raw === null || trim((string)$raw) === '') {
            return 'orderNumber пустой';
        }

        if (self::normalize($raw) === '') {
            return 'orderNumber без цифр: ' . $raw;
        }

        return null;
    }
}