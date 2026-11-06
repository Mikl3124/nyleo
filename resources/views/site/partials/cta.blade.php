@php($c = \App\Models\CompanySetting::current())
<section class="tight">
    <div class="wrap">
        <div class="cta">
            <div>
                <h2>Un projet ? Parlons-en simplement.</h2>
                <p>Devis gratuit, prix fixe, sans engagement. Je vous dis tout de suite quelle démarche s'applique.</p>
            </div>
            <div class="actions">
                @if ($c->phone)<a class="btn green" href="tel:{{ preg_replace('/\s+/', '', $c->phone) }}">@include('site.partials.icon', ['name' => 'phone']) {{ $c->phone }}</a>@endif
                <a class="btn light" href="{{ route('site.contact') }}">Demander un devis</a>
            </div>
        </div>
    </div>
</section>
