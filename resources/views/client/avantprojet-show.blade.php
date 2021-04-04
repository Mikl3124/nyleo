@extends('layouts.app')
@section('content')
<div class="container-fluid row">
  <div class="col-sm-12 col-md-2">
    @include('layouts.steps')
  </div>
  <div class="col-sm-12 col-md-8">
      <div class="container">
        <h4 class="text-center">Votre avant-projet</h4>
        {!! $avantprojet->url !!}
        <div class="d-flex justify-content-between">
            <a target="_blank" href="https://nyleo.fr/cgv/">Consulter nos CGV</a>
        </div>
        <div class="text-center mt-5">
            <a class="btn btn-secondary mb-2" href="{{ route('message.show') }}">Demander des informations complémentaires</a>
            <button type="button" class="btn btn-primary mb-2" data-toggle="modal" data-target="#validate-avant-projet">
                Valider l'avant projet
            </button>


        </div>
      </div>
    </div>
  <div class="col-sm-12 col-md-2">
    @include('layouts.vertical-navbar')
  </div>
</div>
<!-- Modal -->
<div class="modal fade" id="validate-avant-projet" tabindex="-1" role="dialog" aria-labelledby="validate-avant-projet" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Validation de l'avant projet</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>Souhaitez-vous valider l'avant projet, et régler le solde du devis?</p>
        <p>- Montant du devis: {{ $quote->amount }}€ <br>
        - Acompte déjà versé:  {{ number_format((float)$paiement->amount, 2, '.', '') }}€<br>
        - Reste à régler: {{ number_format(($quote->amount - $paiement->amount), 2, '.', '') }}€
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
        <form action="{{ route('pay-avantprojet', $quote->id) }}" method="post">
              @csrf
           <input type="hidden" id="quote_amount" name="quote_amount" value="{{ $quote->amount }}">
           <input type="hidden" id="pay_amount" name="pay_amount" value="{{ $paiement->amount }}">
          <button class="btn btn-success" type="submit">Payer {{ number_format(($quote->amount - $paiement->amount), 2, '.', '') }}€</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>

@endsection