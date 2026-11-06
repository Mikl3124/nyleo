@extends('layouts.site')
@section('title', 'Demander un devis gratuit')
@section('description', "Demandez votre devis gratuit pour une déclaration préalable ou un permis de construire en Dordogne ou en Gironde. Rappel sous 48 h ouvrées.")
@section('content')
@include('site.partials.page-hero', [
    'crumbs' => 'Contact', 'kicker' => 'Devis gratuit · rappel sous 48 h',
    'h1' => 'Parlez-moi de <span class="hl">votre projet</span>',
    'lead' => "Quelques informations suffisent. Je vous rappelle pour en parler, puis je vous envoie un devis clair, au prix fixe.",
])
<section>
    <div class="wrap contact">
        <div>
            <h2 style="font-size:1.5rem">Me joindre en direct</h2>
            @include('site.partials.contact-info')
            <div class="box" style="margin-top:28px"><b>Déjà client ?</b><p class="muted" style="margin:6px 0 14px">Retrouvez vos devis, plans et factures dans votre espace.</p><a class="btn ghost sm" href="{{ route('portal.login') }}">Accéder à mon espace</a></div>
        </div>
        @include('site.partials.contact-form', ['procedure' => in_array(request('demarche'), ['DP', 'PC']) ? request('demarche') : 'AUTRE', 'prefillTown' => (string) request('commune')])
    </div>
</section>
@endsection
