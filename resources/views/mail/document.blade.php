<x-mail::message>
{!! $paragraphs !!}

@if ($buttonUrl)
<x-mail::button :url="$buttonUrl">
{{ $buttonLabel }}
</x-mail::button>
@endif

Cordialement,<br>
{!! nl2br(e($signature)) !!}
</x-mail::message>
