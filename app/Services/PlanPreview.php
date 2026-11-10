<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Pages des plans en images PNG avec un léger filigrane en diagonale, pour la consultation en ligne
 * quand le téléchargement n'est pas encore permis (voir PlanAccess). Le fichier original ne quitte jamais le serveur.
 *
 * Conversion du PDF : pdftoppm (poppler), sinon Ghostscript (gs, présent sur le serveur), sinon Imagick.
 * Les images sont gardées dans storage/app/private/plan-previews/{document}/{clé} ; la clé change avec le fichier ou le filigrane.
 */
class PlanPreview
{
    /** Changer ce numéro régénère toutes les images (nouveau filigrane, nouvelle résolution…). */
    private const VERSION = 2;

    private const DPI = 110;

    private const MAX_PAGES = 30;

    private const MAX_WIDTH = 2200;

    /** Nombre de pages, après avoir créé les images si besoin. */
    public static function pageCount(Document $document): int
    {
        $dir = self::directory($document);
        $count = count(self::pages($dir));
        if ($count > 0) {
            return $count;
        }

        return Cache::lock('plan-preview-'.$document->id, 180)->block(170, function () use ($document, $dir) {
            $count = count(self::pages($dir));

            return $count > 0 ? $count : self::render($document, $dir);
        });
    }

    /** Chemin absolu de l'image d'une page (1 = première), ou null si elle n'existe pas. */
    public static function pagePath(Document $document, int $page): ?string
    {
        if ($page < 1 || $page > self::pageCount($document)) {
            return null;
        }
        $path = Storage::disk('local')->path(self::directory($document).'/page-'.$page.'.png');

        return is_file($path) ? $path : null;
    }

    public static function watermarkText(Document $document): string
    {
        $client = trim((string) $document->dossier?->client?->display_name);

        return 'Nyleo · Document de travail'.($client !== '' ? ' · '.$client : '');
    }

    /** Efface les images d'un document (fichier supprimé ou remplacé). */
    public static function forget(Document $document): void
    {
        Storage::disk('local')->deleteDirectory('plan-previews/'.$document->id);
    }

    private static function directory(Document $document): string
    {
        $key = substr(md5(implode('|', [self::VERSION, $document->path, $document->size, self::watermarkText($document)])), 0, 12);

        return 'plan-previews/'.$document->id.'/'.$key;
    }

    /** @return list<string> */
    private static function pages(string $dir): array
    {
        $files = glob(Storage::disk('local')->path($dir).'/page-*.png') ?: [];
        natsort($files);

        return array_values($files);
    }

    private static function render(Document $document, string $dir): int
    {
        $disk = Storage::disk('local');
        if (! $document->path || ! $disk->exists($document->path)) {
            throw new RuntimeException("Fichier introuvable pour le document {$document->id}");
        }

        // Une seule version gardée par document.
        $disk->deleteDirectory('plan-previews/'.$document->id);
        $disk->makeDirectory($dir);
        $source = $disk->path($document->path);
        $out = $disk->path($dir);

        $mime = (string) ($document->mime_type ?: $disk->mimeType($document->path));
        $raw = str_starts_with($mime, 'image/') ? [$source] : self::rasterize($source, $out);
        if ($raw === []) {
            throw new RuntimeException("Impossible de convertir « {$document->path} » en images (pdftoppm, gs ou Imagick requis)");
        }

        $text = self::watermarkText($document);
        foreach (array_values($raw) as $i => $file) {
            $image = @imagecreatefromstring((string) file_get_contents($file));
            if (! $image) {
                continue;
            }
            $image = self::fit($image);
            self::watermark($image, $text);
            imagepng($image, $out.'/page-'.($i + 1).'.png', 9);
            imagedestroy($image);
            if ($file !== $source) {
                @unlink($file);
            }
        }

        return count(self::pages($dir));
    }

    /** @return list<string> images brutes, dans l'ordre des pages */
    private static function rasterize(string $pdf, string $out): array
    {
        $attempts = [
            ['pdftoppm', '-r', (string) self::DPI, '-png', '-l', (string) self::MAX_PAGES, $pdf, $out.'/raw'],
            ['gs', '-q', '-dSAFER', '-dBATCH', '-dNOPAUSE', '-sDEVICE=png16m', '-r'.self::DPI,
                '-dTextAlphaBits=4', '-dGraphicsAlphaBits=4', '-dLastPage='.self::MAX_PAGES, '-sOutputFile='.$out.'/raw-%03d.png', $pdf],
        ];
        foreach ($attempts as $command) {
            $result = Process::timeout(150)->run($command);
            $files = glob($out.'/raw*.png') ?: [];
            if ($result->successful() && $files) {
                natsort($files);

                return array_values($files);
            }
            array_map('unlink', $files);
        }

        if (class_exists(\Imagick::class)) {
            try {
                $im = new \Imagick;
                $im->setResolution(self::DPI, self::DPI);
                $im->readImage($pdf.'[0-'.(self::MAX_PAGES - 1).']');
                $files = [];
                foreach ($im as $i => $page) {
                    $page->setImageBackgroundColor('white');
                    $page = $page->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                    $page->setImageFormat('png');
                    $page->writeImage($files[] = sprintf('%s/raw-%03d.png', $out, $i + 1));
                }

                return $files;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return [];
    }

    private static function fit(\GdImage $image): \GdImage
    {
        $w = imagesx($image);
        if ($w <= self::MAX_WIDTH) {
            return $image;
        }
        $scaled = imagescale($image, self::MAX_WIDTH, (int) round(imagesy($image) * self::MAX_WIDTH / $w), IMG_BICUBIC);
        imagedestroy($image);

        return $scaled;
    }

    /** Texte répété en diagonale, gris-bleu très clair : les plans restent lisibles mais pas exploitables. */
    private static function watermark(\GdImage $image, string $text): void
    {
        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, true);
        $w = imagesx($image);
        $h = imagesy($image);
        $font = self::font();
        $size = max(11, (int) round(min($w, $h) / 42));
        $color = imagecolorallocatealpha($image, 29, 63, 145, 98); // bleu de Prusse, très transparent
        $angle = 30;

        if (! $font) {
            for ($y = 20; $y < $h; $y += 60) {
                imagestring($image, 5, ($y / 60 % 2) * 120, $y, $text, $color);
            }

            return;
        }

        // Lignes parallèles dans le sens du texte, décalées d'une demi-longueur une ligne sur deux.
        $box = imagettfbbox($size, 0, $font, $text);
        $along = abs($box[2] - $box[0]) + $size * 5;
        $across = $size * 8;
        $rad = deg2rad($angle);
        [$dx, $dy] = [cos($rad), -sin($rad)];   // sens du texte
        [$px, $py] = [sin($rad), cos($rad)];    // perpendiculaire
        $diag = hypot($w, $h);
        $rows = (int) ceil($diag / $across);
        $cols = (int) ceil($diag / $along) + 1;
        for ($r = -$rows; $r <= $rows; $r++) {
            $shift = ($r % 2) * $along / 2;
            for ($c = -$cols; $c <= $cols; $c++) {
                $x = $w / 2 + ($c * $along + $shift) * $dx + $r * $across * $px;
                $y = $h / 2 + ($c * $along + $shift) * $dy + $r * $across * $py;
                if ($x > -$along && $x < $w + $along && $y > -$along && $y < $h + $along) {
                    imagettftext($image, $size, $angle, (int) $x, (int) $y, $color, $font, $text);
                }
            }
        }
    }

    private static function font(): ?string
    {
        foreach ([
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'),
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
