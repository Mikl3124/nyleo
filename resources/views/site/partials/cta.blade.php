@php($c = \App\Models\CompanySetting::current())
<section class="tight">
    <div class="wrap">
        <div class="cta-band">
            <div>
                <span class="eyebrow" style="color:var(--ochre)">Devis gratuit · réponse sous 48 h</span>
                <h2>Parlons de votre projet</h2>
                <p>Un appel suffit pour savoir quelle démarche s'applique et ce qu'il faut préparer. Sans engagement.</p>
            </div>
            <div>
                @if ($c->phone)<a class="big-phone" href="tel:{{ preg_replace('/\s+/', '', $c->phone) }}">{{ $c->phone }}</a>@endif
                <div class="btn-stack">
                    @if ($c->phone)<a class="btn cta" href="tel:{{ preg_replace('/\s+/', '', $c->phone) }}">@include('site.partials.icon', ['name' => 'phone']) Appeler</a>@endif
                    <a class="btn light" href="{{ route('site.contact') }}">Demander un devis</a>
                </div>
            </div>
        </div>
    </div>
</section>
