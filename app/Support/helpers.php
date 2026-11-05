<?php

if (! function_exists('money')) {
    function money($amount): string
    {
        return number_format((float) $amount, 2, ',', ' ').' €';
    }
}
