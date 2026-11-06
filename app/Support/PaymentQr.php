<?php

namespace App\Support;

use App\Models\Invoice;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QR code de virement SEPA au format EPC (« QR code de paiement » lu par les applications bancaires).
 * Généré localement avec chillerlan/php-qrcode : aucun service extérieur, aucun coût.
 */
class PaymentQr
{
    /**
     * Charge utile EPC069-12 (version 002, UTF-8, virement SCT), ou null si un QR code n'a pas de sens :
     * pas d'IBAN, avoir, brouillon sans numéro, rien à payer.
     */
    public static function payload(Invoice $invoice, array $company, ?float $amount = null): ?string
    {
        $iban = strtoupper(preg_replace('/\s+/', '', (string) ($company['iban'] ?? '')));
        if (! self::validIban($iban)) {
            return null;
        }
        if ($invoice->isCreditNote() || ! $invoice->number) {
            return null;
        }
        $amount = round($amount ?? $invoice->remaining(), 2);
        if ($amount < 0.01 || $amount > 999999999.99) {
            return null;
        }
        $name = trim((string) (($company['company_name'] ?? null) ?: ($company['trade_name'] ?? null) ?: config('app.name')));
        $bic = strtoupper(preg_replace('/\s+/', '', (string) ($company['bic'] ?? '')));
        if (! preg_match('/^[A-Z0-9]{8}([A-Z0-9]{3})?$/', $bic)) {
            $bic = '';
        }

        return implode("\n", [
            'BCD',
            '002',
            '1',
            'SCT',
            $bic,
            mb_substr($name, 0, 70),
            $iban,
            'EUR'.number_format($amount, 2, '.', ''),
            '',
            '',
            mb_substr($invoice->number, 0, 140),
        ]);
    }

    /** Image PNG (SVG à défaut) en data URI, pour le PDF, ou null. */
    public static function png(Invoice $invoice, array $company, ?float $amount = null): ?string
    {
        $payload = self::payload($invoice, $company, $amount);

        if (! $payload) {
            return null;
        }

        // Sans l'extension GD, DomPDF sait aussi afficher le SVG.
        return self::render($payload, extension_loaded('gd') ? QROutputInterface::GDIMAGE_PNG : QROutputInterface::MARKUP_SVG);
    }

    /** Image SVG en data URI (pour l'espace client), ou null. */
    public static function svg(Invoice $invoice, array $company, ?float $amount = null): ?string
    {
        $payload = self::payload($invoice, $company, $amount);

        return $payload ? self::render($payload, QROutputInterface::MARKUP_SVG) : null;
    }

    /** Vérifie la clé de contrôle de l'IBAN (modulo 97), pour ne jamais imprimer un QR code faux. */
    public static function validIban(string $iban): bool
    {
        if (! preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban)) {
            return false;
        }
        $digits = '';
        foreach (str_split(substr($iban, 4).substr($iban, 0, 4)) as $char) {
            $digits .= ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
        }
        $mod = 0;
        foreach (str_split($digits, 7) as $chunk) {
            $mod = (int) ($mod.$chunk) % 97;
        }

        return $mod === 1;
    }

    /** IBAN lisible, par groupes de quatre. */
    public static function formatIban(?string $iban): string
    {
        return trim(chunk_split(strtoupper(preg_replace('/\s+/', '', (string) $iban)), 4, ' '));
    }

    private static function render(string $payload, string $type): string
    {
        return (new QRCode(new QROptions([
            'outputType' => $type,
            'eccLevel' => EccLevel::M,
            'scale' => 8,
            'quietzoneSize' => 2,
            'outputBase64' => true,
            'svgAddXmlHeader' => false,
            'drawLightModules' => false,
        ])))->render($payload);
    }
}
