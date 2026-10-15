<?php

if (! function_exists('format_idr')) {
    /**
     * Format a number as Indonesian Rupiah.
     */
    function format_idr(int|float|string|null $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }
}
if (! function_exists('format_idr_compact')) {
    /**
     * Format a number as compact Indonesian Rupiah (e.g. Rp 1,5 M, Rp 250 Jt, Rp 500 rb).
     */
    function format_idr_compact(int|float|string|null $amount): string
    {
        $num = (float) ($amount ?? 0);
        $abs = abs($num);
        $sign = $num < 0 ? '-' : '';

        if ($abs >= 1_000_000_000_000) {
            $formatted = number_format($abs / 1_000_000_000_000, 1, ',', '.');
            return $sign . 'Rp ' . rtrim(rtrim($formatted, '0'), ',') . ' T';
        }
        if ($abs >= 1_000_000_000) {
            $formatted = number_format($abs / 1_000_000_000, 1, ',', '.');
            return $sign . 'Rp ' . rtrim(rtrim($formatted, '0'), ',') . ' M';
        }
        if ($abs >= 1_000_000) {
            $formatted = number_format($abs / 1_000_000, 1, ',', '.');
            return $sign . 'Rp ' . rtrim(rtrim($formatted, '0'), ',') . ' Jt';
        }
        if ($abs >= 1_000) {
            $formatted = number_format($abs / 1_000, 0, ',', '.');
            return $sign . 'Rp ' . $formatted . ' rb';
        }

        return format_idr($amount);
    }
}

