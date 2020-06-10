 @auth
    {{-- ------------------------- steps ----------------------- --}}
    <h3 class="text-center mb-3">Les étapes</h3>
    <div class="row">
        <div class="text-center w-100">
                @if ($step > 0)
                        <a href="{{ route('client.show') }}" class="btn-success btn-lg btn-block mb-2"><i class="fas fa-check"></i>  Informations</a>
                @else
                        <a href="{{ route('client.edit', Auth::user()) }}" class="btn btn-secondary btn-block btn-lg disabled mb-2">Etape 1 : Informations</a>
                @endif
        </div>
        <div class="col-md-12 text-center w-100">
          <i class="fas fa-arrow-down text-muted"></i>
        </div>
        <div class="text-center w-100">
                @if ($step > 1)
                        <a href="{{ route('projet.show', Auth::user()) }}" class="btn-success btn-lg btn-block mb-2"><i class="fas fa-check"></i>  Projet</a>
                @else
                        <a href="{{ route('projet.create', Auth::user()) }}" class="btn btn-secondary btn-block btn-lg disabled mb-2">Etape 2: Projet</a>
                @endif
        </div>
        <div class="col-md-12 text-center w-100">
          <i class="fas fa-arrow-down text-muted"></i>
        </div>
        <div class="text-center w-100">
                @if ($step > 2)
                        <a href="http://" class="btn-success btn-lg btn-block mb-2"><i class="fas fa-check"></i>  Devis</a>
                @else
                        <a href="http://" class="btn btn-secondary btn-block btn-lg disabled mb-2">Etape 3: Devis</a>
                @endif
        </div>
        <div class="col-md-12 text-center w-100">
          <i class="fas fa-arrow-down text-muted"></i>
        </div>
        <div class="text-center w-100">
                @if ($step > 3)
                        <a href="http://" class="btn-success btn-lg btn-block mb-2"><i class="fas fa-check"></i>  Avant-Projet</a>
                @else
                        <a href="http://" class="btn btn-secondary btn-block btn-lg disabled mb-2">Etape 4: Avant-Projet</a>
                @endif
        </div>
        <div class="col-md-12 text-center w-100">
          <i class="fas fa-arrow-down text-muted"></i>
        </div>
        <div class="text-center w-100">
                @if ($step > 5)
                        <a href="http://" class="btn-success btn-lg btn-block mb-2"><i class="fas fa-check"></i>  Facture</a>
                @else
                        <a href="http://" class="btn btn-secondary btn-block btn-lg disabled mb-2">Etape 5: Facture</a>
                @endif
        </div>
        <div class="col-md-12 text-center w-100">
          <i class="fas fa-arrow-down text-muted"></i>
        </div>
        <div class="text-center w-100">
                @if ($step > 6 )
                        <a href="http://" class="btn-success btn-lg btn-block mb-2"><i class="fas fa-check"></i>  Consulter mon Projet</a>
                @else
                        <a href="http://" class="btn btn-secondary btn-block btn-lg disabled mb-2">Etape 6: Livraison</a>
                @endif
        </div>
    </div>
    <p>
@endauth
