<x-mail::message :preheader="$preheader">
{!! $paragraphs !!}

@if ($buttonUrl)
<x-mail::button :url="$buttonUrl">
{{ $buttonLabel }}
</x-mail::button>
@endif

Cordialement,<br>
{!! nl2br(e($signature)) !!}

@if ($buttonUrl)
<x-slot:subcopy>
@lang("If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:", ['actionText' => $buttonLabel]) <span class="break-all">[{{ $buttonUrl }}]({{ $buttonUrl }})</span>
</x-slot:subcopy>
@endif
</x-mail::message>
