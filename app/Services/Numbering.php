<?php

namespace App\Services;

use App\Models\CompanySetting;
use Illuminate\Support\Facades\DB;

/**
 * Numérotation chronologique et continue, sans trou (art. 242 nonies A, annexe II du CGI).
 * Format conservé de l'existant : D202611-134, F202611-117, AV202611-3.
 */
class Numbering
{
    public static function next(string $kind): string
    {
        return DB::transaction(function () use ($kind) {
            $settings = CompanySetting::query()->lockForUpdate()->first() ?? CompanySetting::current();
            [$prefixField, $counterField] = match ($kind) {
                'quote' => ['quote_prefix', 'quote_next_number'],
                'invoice' => ['invoice_prefix', 'invoice_next_number'],
                'credit_note' => ['credit_note_prefix', 'credit_note_next_number'],
            };
            $seq = max(1, (int) $settings->{$counterField});
            $settings->forceFill([$counterField => $seq + 1])->save();

            return $settings->{$prefixField}.now()->format('Ym').'-'.$seq;
        });
    }
}
