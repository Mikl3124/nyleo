@extends('layouts.portal')
@section('title', 'Paiement de la facture '.$invoice->number)
@section('content')
<div class="card {{ $state === 'success' ? 'done' : 'ruled' }}" style="max-width:600px;margin:20px auto">
    @switch($state)
        @case('success')
            <h1>Merci, votre paiement est bien reçu</h1>
            <p>Vous avez réglé <strong>{{ money($amount) }}</strong> par carte bancaire pour la {{ mb_strtolower($invoice->type->label()) }} {{ $invoice->number }}.</p>
            @if ($invoice->remaining() > 0)
                <p>Il reste <strong>{{ money($invoice->remaining()) }}</strong> à régler.</p>
            @endif
            <p class="muted">Vous recevez une confirmation par e-mail. Vous n'avez rien d'autre à faire.</p>
            @break
        @case('pending')
            <h1>Paiement en cours de vérification</h1>
            <p>Votre paiement pour la facture {{ $invoice->number }} est en cours de traitement par votre banque. Vous recevrez une confirmation par e-mail dès qu'il sera validé.</p>
            @break
        @case('cancelled')
            <h1>Paiement annulé</h1>
            <p>Aucun montant n'a été débité. Vous pouvez réessayer quand vous le souhaitez, ou régler la facture {{ $invoice->number }} par virement ou par chèque.</p>
            @break
        @case('paid')
            <h1>Cette facture est déjà réglée</h1>
            <p>La facture {{ $invoice->number }} ne demande plus aucun paiement. Merci !</p>
            @break
        @case('error')
            <h1>Paiement momentanément indisponible</h1>
            <p>Le paiement par carte ne fonctionne pas pour l'instant. Merci de réessayer un peu plus tard, ou de régler la facture {{ $invoice->number }} par virement ou par chèque.</p>
            @break
        @default
            <h1>Paiement par carte indisponible</h1>
            <p>Le paiement par carte n'est pas proposé pour la facture {{ $invoice->number }}. Vous pouvez la régler par virement ou par chèque : les informations sont sur la facture.</p>
    @endswitch

    @if ($retryUrl && in_array($state, ['cancelled', 'error'], true))
        <p><a class="btn cta block" href="{{ $retryUrl }}">Payer {{ money($invoice->remaining()) }} par carte</a></p>
        <p><a class="btn secondary block" href="{{ $portalUrl }}">Aller à mon espace client</a></p>
    @else
        <p><a class="btn {{ $state === 'success' ? 'cta' : 'secondary' }} block" href="{{ $portalUrl }}">Aller à mon espace client</a></p>
    @endif
</div>
@endsection
