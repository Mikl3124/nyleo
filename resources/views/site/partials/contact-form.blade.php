<form method="post" action="{{ route('site.request') }}" class="form-card" id="demande">
    @csrf
    @if (session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="errors" role="alert">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    <div class="sr" aria-hidden="true"><label for="website">Ne pas remplir</label><input id="website" type="text" name="website" tabindex="-1" autocomplete="off"></div>

    <fieldset style="border:0;padding:0;margin:0">
        <legend style="font-weight:600;margin-bottom:10px">Votre démarche</legend>
        <div class="choice">
            @foreach (['DP' => 'Déclaration préalable', 'PC' => 'Permis de construire', 'AUTRE' => 'Je ne sais pas'] as $v => $l)
                <label><input type="radio" name="procedure" value="{{ $v }}" @checked(old('procedure', $procedure ?? 'AUTRE') === $v) required><span>{{ $l }}</span></label>
            @endforeach
        </div>
    </fieldset>

    <div class="grid2">
        <div><label for="last_name">Nom</label><input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name"></div>
        <div><label for="first_name">Prénom</label><input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name"></div>
        <div><label for="phone">Téléphone</label><input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required autocomplete="tel"></div>
        <div><label for="email">E-mail</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></div>
        <div><label for="nature">Votre projet</label>
            <select id="nature" name="nature">
                @foreach (\App\Filament\Resources\Dossiers\DossierResource::NATURES as $k => $v)<option value="{{ $k }}" @selected(old('nature') === $k)>{{ $v }}</option>@endforeach
            </select></div>
        <div><label for="site_city">Commune du terrain</label><input id="site_city" type="text" name="site_city" value="{{ old('site_city') }}" required placeholder="Ex. Ribérac"></div>
    </div>
    <input type="hidden" name="site_postal_code" value="{{ old('site_postal_code') }}">
    <label for="description">Quelques mots sur votre projet <small>(facultatif)</small></label>
    <textarea id="description" name="description" rows="4" placeholder="Ex. Véranda de 15 m² à l'arrière de la maison, côté jardin.">{{ old('description') }}</textarea>
    <div class="form-foot">
        <small>Je vous rappelle sous 48 h ouvrées. Vos informations servent uniquement à établir votre devis (<a href="{{ route('privacy') }}">confidentialité</a>).</small>
        <button class="btn green" type="submit">Envoyer ma demande @include('site.partials.icon', ['name' => 'arrow'])</button>
    </div>
</form>
