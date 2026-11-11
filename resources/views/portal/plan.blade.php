@extends('layouts.portal')
@section('title', $document->displayName())
@section('content')
<style>
    .plan-pages { display: grid; gap: 18px; margin: 18px 0; user-select: none; -webkit-user-select: none; -webkit-touch-callout: none; }
    .plan-page { position: relative; background: #fff; border: 1px solid #dfe3eb; border-radius: 14px; overflow: hidden; }
    .plan-page img { display: block; width: 100%; height: auto; pointer-events: none; -webkit-user-drag: none; }
    .plan-page::after { content: ""; position: absolute; inset: 0; } /* couche transparente au-dessus de l'image */
    .plan-page:not(.ok) { aspect-ratio: 1.414; }
    .plan-page:not(.ok)::before { content: "Chargement de la page…"; position: absolute; top: 50%; left: 0; right: 0; text-align: center; color: #5b6475; transform: translateY(-50%); }
    .plan-page .num { position: absolute; top: 8px; right: 10px; z-index: 1; font-size: .8rem; background: rgba(255, 255, 255, .85); border-radius: 6px; padding: 2px 8px; }
    .plan-print { display: none; }
    @media print {
        .plan-pages, .plan-actions { display: none !important; }
        .plan-print { display: block; }
    }
</style>

<p><a href="{{ route('portal.dossiers.show', $dossier) }}">← Retour à mon dossier</a></p>
<h1>{{ $document->displayName() }}</h1>
<p class="muted" style="margin-top:0">{{ $dossier->title }} · mis en ligne le {{ $document->created_at->format('d/m/Y') }}</p>

@if ($needsBirth ?? false)
    <div class="card action">
        <h2>Avant d'ouvrir vos plans</h2>
        <p>Pour remplir le formulaire officiel (CERFA) demandé par la mairie, j'ai besoin de votre date et de votre lieu de naissance.</p>
        <form method="post" action="{{ route('portal.documents.birth', $document) }}">
            @csrf
            <div class="grid2">
                <div><label for="birth_date">Date de naissance</label><div class="date-field"><input id="birth_date" type="date" name="birth_date" required value="{{ old('birth_date', $client->birth_date?->format('Y-m-d')) }}"><span class="ph" aria-hidden="true">jj/mm/aaaa</span></div></div>
                <div>@include('portal.partials.birth-place', ['value' => old('birth_place', $client->birth_place), 'required' => true])</div>
            </div>
            <p><button class="btn cta block" type="submit">Voir mes plans</button></p>
        </form>
    </div>
@elseif ($pages > 0)
    <p class="muted">Faites défiler pour voir toutes les pages. Sur téléphone, écartez deux doigts pour zoomer.</p>
    <div class="plan-pages" oncontextmenu="return false" ondragstart="return false">
        @for ($p = 1; $p <= $pages; $p++)
            <figure class="plan-page" style="margin:0">
                @if ($pages > 1)<span class="num">Page {{ $p }} sur {{ $pages }}</span>@endif
                <img src="{{ route('portal.documents.page', [$document, $p]) }}" alt="{{ $document->displayName() }}, page {{ $p }}" loading="{{ $p > 2 ? 'lazy' : 'eager' }}" draggable="false" onload="this.parentNode.classList.add('ok')">
            </figure>
        @endfor
    </div>
    <p class="plan-print">Les plans se consultent à l'écran, dans votre espace client.</p>
@else
    <div class="card">
        <p style="margin:0">Les plans ne peuvent pas s'afficher pour le moment. Je reviens vers vous très vite ; vous pouvez aussi m'appeler.</p>
    </div>
@endif

@if ($document->isAwaitingValidation() && ! ($needsBirth ?? false))
    <div class="card action plan-actions">
        <h2>Ces plans vous conviennent ?</h2>
        <form method="post" action="{{ route('portal.documents.validate', $document) }}" onsubmit="return confirm('Confirmez-vous que les plans vous conviennent ?')">
            @csrf
            <button class="btn cta block" type="submit">✓ Je valide les plans</button>
        </form>
        <details style="margin-top:14px">
            <summary>Je souhaite une modification</summary>
            <form method="post" action="{{ route('portal.documents.change', $document) }}">
                @csrf
                <label for="feedback">Quelles modifications souhaitez-vous ?</label>
                <textarea id="feedback" name="feedback" rows="4" required></textarea>
                <p><button class="btn danger" type="submit">Envoyer ma demande</button></p>
            </form>
        </details>
    </div>
@endif

<script>
document.addEventListener('keydown', function (e) {
    var k = (e.key || '').toLowerCase();
    if ((e.ctrlKey || e.metaKey) && (k === 's' || k === 'p')) { e.preventDefault(); }
});
</script>
@endsection
