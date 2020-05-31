@extends('layouts.app')
@section('content')
  <div class="container-fluid row">
    <div class="col-sm-12 col-md-2">
      @include('layouts.steps')
    </div>
    <div class="col-sm-12 col-md-10">
      <div class="container">

          <h4 class="text-center">Votre devis</h4>

            <iframe src="https://nyleo.s3.eu-west-3.amazonaws.com/documents/D2000002_1590850167.pdf#toolbar=0" width="100%" height="600px"></iframe>
            <div class="d-flex justify-content-between">
                <a href="{{ route('quote.download', $quote->id) }}">Télécharger votre devis</a>
                <a target="_blank" href="https://nyleo.fr/cgv/">Consulter nos CGV</a>
            </div>
            
            <div class="text-center mt-5">
                <a class="btn btn-secondary" href="{{ route('message.show') }}">Demander des informations complémentaires</a>
                
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#paymentModalCenter">
                    Accepter le devis
                </button>
            </div>

      </div>

    <!-- Modal -->
    <div class="modal fade" id="paymentModalCenter" tabindex="-1" role="dialog" aria-labelledby="paymentModalCenterTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="paymentModalLongTitle">Comment souhaitez-vous régler l'acompte de 30% ?</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
              <form action="{{ route('payment.index')}}" method="get">
                  @csrf
                      <select name="payment_method" class="custom-select custom-select-lg mb-3">
                          <option selected value="stripe">Paiement en ligne sécurisé (immédiat)</option>
                          <option value="virement">Virement Bancaire (3 à 4 jours)</option>
                          <option value="cheque">Chèque (4 à 7 jours)</option>
                          <option value="other">Autre</option>
                      </select>
                  <input id="quoteId" name="quote" type="hidden" value="{{ $quote->id }}">
                  <div class="text-center">
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Retour</button>
                      <button type="submit" class="btn btn-primary">Valider</button>
                  </div>
              </form>  
          </div>
      </div>
    </div>
  </div>

@endsection
