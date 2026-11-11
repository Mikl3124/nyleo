<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Pages des plans en images JPEG avec un léger filigrane en diagonale, pour la consultation en ligne
 * quand le téléchargement n'est pas encore permis (voir PlanAccess). Le fichier original ne quitte jamais le serveur.
 *
 * Conversion du PDF : pdftoppm (poppler), sinon Ghostscript (gs, présent sur le serveur), sinon Imagick.
 * Chaque page est dessinée à part (à la demande ou à l'avance par PreparePlanPreview), gardée dans
 * storage/app/private/plan-previews/{document}/{clé} ; la clé change avec le fichier ou le filigrane.
 */
class PlanPreview
{
    /** Changer ce numéro régénère toutes les images (nouveau filigrane, nouvelle résolution…). */
    private const VERSION = 3;

    private const DPI = 110;

    private const MAX_PAGES = 30;

    private const MAX_WIDTH = 1800;

    private const JPEG_QUALITY = 80;

    /** Nombre de pages (rapide : Ghostscript compte les pages sans les dessiner). */
    public static function pageCount(Document $document): int
    {
        $dir = self::ensureDirectory($document);
        $file = Storage::disk('local')->path($dir.'/count');
        if (is_file($file)) {
            return (int) file_get_contents($file);
        }
        $count = min(self::MAX_PAGES, self::countPages($document));
        if ($count > 0) {
            file_put_contents($file, (string) $count);
        }

        return $count;
    }

    /** Chemin absolu de l'image d'une page (1 = première), créée à la demande si besoin ; null si la page n'existe pas. */
    public static function pagePath(Document $document, int $page): ?string
    {
        if ($page < 1 || $page > self::pageCount($document)) {
            return null;
        }
        $path = Storage::disk('local')->path(self::directory($document).'/page-'.$page.'.jpg');
        if (! is_file($path)) {
            Cache::lock('plan-preview-'.$document->id.'-'.$page, 120)->block(110, function () use ($document, $page, $path) {
                if (! is_file($path)) {
                    self::renderPage($document, $page, $path);
                }
            });
        }

        return is_file($path) ? $path : null;
    }

    /** Prépare toutes les pages à l'avance (tâche de fond après l'ajout des plans), pour un affichage immédiat. */
    public static function warm(Document $document): int
    {
        $count = self::pageCount($document);
        for ($page = 1; $page <= $count; $page++) {
            self::pagePath($document, $page);
        }

        return $count;
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

    /** Dossier des images de la version actuelle ; les anciennes versions sont effacées. */
    private static function ensureDirectory(Document $document): string
    {
        $disk = Storage::disk('local');
        $dir = self::directory($document);
        if (! $disk->exists($dir)) {
            foreach ($disk->directories('plan-previews/'.$document->id) as $old) {
                $disk->deleteDirectory($old);
            }
            $disk->makeDirectory($dir);
        }

        return $dir;
    }

    private static function source(Document $document): string
    {
        $disk = Storage::disk('local');
        if (! $document->path || ! $disk->exists($document->path)) {
            throw new RuntimeException("Fichier introuvable pour le document {$document->id}");
        }

        return $disk->path($document->path);
    }

    private static function isImage(Document $document): bool
    {
        return str_starts_with((string) ($document->mime_type ?: Storage::disk('local')->mimeType($document->path)), 'image/');
    }

    private static function countPages(Document $document): int
    {
        $pdf = self::source($document);
        if (self::isImage($document)) {
            return 1;
        }
        $result = Process::timeout(30)->run(['gs', '-q', '-dNODISPLAY', '-dBATCH', '-dNOPAUSE', '--permit-file-read='.$pdf,
            '-c', '('.addcslashes($pdf, '()\\').') (r) file runpdfbegin pdfpagecount = quit']);
        if ($result->successful() && ($n = (int) trim($result->output())) > 0) {
            return $n;
        }
        $result = Process::timeout(30)->run(['pdfinfo', $pdf]);
        if ($result->successful() && preg_match('/^Pages:\s+(\d+)/m', $result->output(), $m)) {
            return (int) $m[1];
        }
        if (class_exists(\Imagick::class)) {
            try {
                $im = new \Imagick;
                $im->pingImage($pdf);

                return $im->getNumberImages();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return 0;
    }

    /** Dessine une seule page, ajoute le filigrane et l'enregistre en JPEG. */
    private static function renderPage(Document $document, int $page, string $target): void
    {
        $source = self::source($document);
        $raw = self::isImage($document) ? $source : self::rasterize($source, $page, dirname($target));
        if (! $raw) {
            throw new RuntimeException("Impossible de convertir la page {$page} de « {$document->path} » (pdftoppm, gs ou Imagick requis)");
        }
        $image = @imagecreatefromstring((string) file_get_contents($raw));
        if ($raw !== $source) {
            @unlink($raw);
        }
        if (! $image) {
            throw new RuntimeException("Image illisible pour la page {$page} du document {$document->id}");
        }
        $image = self::fit($image);
        self::watermark($image, self::watermarkText($document));
        imageinterlace($image, true); // JPEG progressif : la page apparaît tout de suite, puis se précise
        imagejpeg($image, $target.'.tmp', self::JPEG_QUALITY);
        imagedestroy($image);
        rename($target.'.tmp', $target);
    }

    /** Image brute d'une page du PDF (chemin du fichier), ou null. */
    private static function rasterize(string $pdf, int $page, string $out): ?string
    {
        $base = $out.'/raw-'.$page.'-'.bin2hex(random_bytes(3));
        $attempts = [
            ['pdftoppm', '-r', (string) self::DPI, '-png', '-f', (string) $page, '-l', (string) $page, '-singlefile', $pdf, $base],
            ['gs', '-q', '-dSAFER', '-dBATCH', '-dNOPAUSE', '-sDEVICE=png16m', '-r'.self::DPI, '-dTextAlphaBits=4', '-dGraphicsAlphaBits=4',
                '-dFirstPage='.$page, '-dLastPage='.$page, '-sOutputFile='.$base.'.png', $pdf],
        ];
        foreach ($attempts as $command) {
            $result = Process::timeout(110)->run($command);
            if ($result->successful() && is_file($base.'.png') && filesize($base.'.png') > 0) {
                return $base.'.png';
            }
            @unlink($base.'.png');
        }

        if (class_exists(\Imagick::class)) {
            try {
                $im = new \Imagick;
                $im->setResolution(self::DPI, self::DPI);
                $im->readImage($pdf.'['.($page - 1).']');
                $im->setImageBackgroundColor('white');
                $im = $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $im->setImageFormat('png');
                $im->writeImage($base.'.png');

                return $base.'.png';
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return null;
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
