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
