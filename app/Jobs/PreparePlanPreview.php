<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\PlanPreview;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Prépare à l'avance les pages avec filigrane des plans, pour que le client les voie sans attendre. */
class PreparePlanPreview implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 2;

    public function __construct(public Document $document) {}

    public function handle(): void
    {
        PlanPreview::warm($this->document);
    }

    public function failed(\Throwable $e): void
    {
        report($e);
    }
}
