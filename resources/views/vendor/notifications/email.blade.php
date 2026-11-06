{{--
    Gabarit commun des notifications (clients, admin, mot de passe).
    Données facultatives passées par App\Notifications\ClientNotification::message() :
    $eyebrow (petite étiquette), $title (titre), $preheader (aperçu dans la boîte de réception),
    $recap (tableau libellé => valeur), $panelTitle + $panelLines (encadré ocre), $help (ligne d'aide), $buttonColor (primary = ocre, secondary = bleu).
--}}
<x-mail::message :preheader="$preheader ?? ($introLines[0] ?? null ? strip_tags(str_replace('**', '', $introLines[0])) : null)">
@if (! empty($eyebrow))
<p class="eyebrow">{{ $eyebrow }}</p>

@endif
@if (! empty($title))
# {{ $title }}

@endif

{{-- Salutation d'ouverture --}}
@if (! empty($greeting))
{{ $greeting }}

@elseif (empty($title))
@lang('Hello!')

@endif

{{-- Lignes d'introduction --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

@if (! empty($recap))
<x-mail::recap :rows="$recap" />
@endif

@if (! empty($panelLines))
<x-mail::panel>
@if (! empty($panelTitle))
**{{ $panelTitle }}**

@endif
@foreach ($panelLines as $line)
{{ $line }}

@endforeach
</x-mail::panel>
@endif

{{-- Bouton --}}
@isset($actionText)
<x-mail::button :url="$actionUrl" :color="$buttonColor ?? ($level === 'error' ? 'error' : 'primary')">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Lignes de fin --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Signature --}}
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('Regards,')<br>
{{ config('app.name') }}
@endif

@if (! empty($help))
<p class="help">{{ $help }}</p>
@endif

{{-- Lien de secours --}}
@isset($actionText)
<x-slot:subcopy>
@lang(
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
    'into your web browser:',
    [
        'actionText' => $actionText,
    ]
) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
