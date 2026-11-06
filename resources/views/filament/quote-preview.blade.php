@php
    /** @var \App\Models\Quote $quote */
    $quote = $this->record;
    $quote->unsetRelation('lines');
    $html = \App\Services\PdfService::quoteHtml($quote->refresh());
@endphp
<div class="nyleo-quote-preview">
    <div class="nyleo-quote-preview__bar">
        <div>
            <div class="nyleo-quote-preview__title">Aperçu du devis, tel que le client le recevra</div>
            <div class="nyleo-quote-preview__meta">
                {{ $quote->client?->display_name }}@if($quote->subject) · {{ $quote->subject }}@endif · <strong>{{ money($quote->total_ttc ?? 0) }}</strong>
            </div>
        </div>
        <div class="nyleo-quote-preview__actions">
            @if ($quote->isEditable())
                <x-filament::button wire:click="startEditing" icon="heroicon-o-pencil-square">
                    Modifier
                </x-filament::button>
            @endif
        </div>
    </div>
    <div class="nyleo-quote-preview__stage"><iframe srcdoc="{{ $html }}" title="Aperçu du devis" class="nyleo-quote-preview__frame" wire:key="preview-{{ md5($html) }}"></iframe></div>
</div>
<style>
    .nyleo-quote-preview { background: var(--nyleo-surface, #fff); border-radius: 1rem; box-shadow: 0 1px 3px rgb(0 0 0 / .06), 0 8px 24px rgb(0 0 0 / .04); overflow: hidden; }
    .nyleo-quote-preview__bar { display: flex; flex-wrap: wrap; gap: .75rem 1.5rem; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid rgb(0 0 0 / .06); }
    .nyleo-quote-preview__title { font-weight: 600; }
    .nyleo-quote-preview__meta { font-size: .875rem; opacity: .75; margin-top: .125rem; }
    .nyleo-quote-preview__actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .nyleo-quote-preview__stage { background: #e9e6df; padding: 1.5rem; overflow-x: auto; }
    .nyleo-quote-preview__frame { display: block; width: 210mm; max-width: 100%; height: min(297mm, 85vh); margin: 0 auto; border: 0; background: #fff; box-shadow: 0 2px 12px rgb(0 0 0 / .12); }
    @media (max-width: 640px) { .nyleo-quote-preview__stage { padding: .5rem; } }
</style>
