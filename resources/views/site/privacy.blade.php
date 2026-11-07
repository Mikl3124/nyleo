@extends('layouts.site')
@section('title', 'Confidentialité et données personnelles')
@php
    $c = \App\Models\CompanySetting::current();
@endphp
@section('content')
@include('site.partials.page-hero', ['crumbs' => 'Confidentialité', 'kicker' => 'Vos données', 'h1' => 'Confidentialité', 'lead' => 'Ce que je fais de vos informations, et comment les faire modifier ou supprimer.'])
<section>
    <div class="wrap prose">
        <h2>Responsable du traitement</h2>
        <p>{{ $c->company_name ?: $c->displayName() }}@if ($c->address), {{ $c->address }}, {{ $c->postal_code }} {{ $c->city }}@endif.</p>
        <h2>Informations recueillies et usage</h2>
        <p>Les informations que vous transmettez (identité, coordonnées, informations sur votre projet, et pour le formulaire CERFA votre date et votre lieu de naissance) servent uniquement à :</p>
        <ul>
            <li>vous rappeler et établir votre devis ;</li>
            <li>préparer et déposer votre dossier d'urbanisme ;</li>
            <li>établir vos factures et vous informer de l'avancement de votre dossier.</li>
        </ul>
        <p>Elles ne sont jamais revendues ni utilisées à des fins publicitaires. Elles sont transmises à l'administration uniquement dans le cadre du dépôt de votre dossier.</p>
        <h2>Durée de conservation</h2>
        <p>Les factures sont conservées 10 ans (obligation comptable). Les autres données sont conservées 3 ans après notre dernier échange.</p>
        <h2>Vos droits</h2>
        <p>Vous pouvez demander l'accès, la rectification ou la suppression de vos données en écrivant à {{ $c->email ?: "l'adresse de contact du site" }}. Vous pouvez aussi adresser une réclamation à la CNIL (cnil.fr).</p>
        <h2>Cookies</h2>
        <p>Ce site n'utilise que des cookies techniques, nécessaires au formulaire et à la connexion à votre espace client. Aucun cookie publicitaire ni de mesure d'audience. Les polices de caractères sont hébergées sur ce site : aucune donnée n'est envoyée à un service tiers.</p>
        <h2>Protection contre les robots</h2>
        <p>Pour écarter les envois automatiques, le formulaire de demande de devis utilise Cloudflare Turnstile. Ce service vérifie de façon invisible que la demande vient d'un navigateur utilisé par une personne. Il ne dépose pas de cookie publicitaire et ne sert pas à vous suivre d'un site à l'autre. Voir la <a href="https://www.cloudflare.com/fr-fr/privacypolicy/" rel="noopener">politique de confidentialité de Cloudflare</a>.</p>
    </div>
</section>
@endsection
