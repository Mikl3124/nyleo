<x-mail::message :preheader="$preheader">
{!! $paragraphs !!}

@if ($payUrl)
<x-mail::button :url="$payUrl">
{{ $payLabel }}
</x-mail::button>
@endif

@if ($buttonUrl)
<x-mail::button :url="$buttonUrl" :color="$payUrl ? 'secondary' : 'primary'">
{{ $buttonLabel }}
</x-mail::button>
@endif

@foreach ($online as $item)
<x-mail::button :url="$item['url']" :color="($payUrl || $buttonUrl) ? 'secondary' : 'primary'">
{{ $item['label'] }}
</x-mail::button>
@endforeach

@if ($downloads)
<x-mail::downloads :items="$downloads" />

@endif
Cordialement,<br>
{!! nl2br(e($signature)) !!}

@if ($buttonUrl)
<x-slot:subcopy>
@lang("If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:", ['actionText' => $buttonLabel]) <span class="break-all">[{{ $buttonUrl }}]({{ $buttonUrl }})</span>
</x-slot:subcopy>
@endif
</x-mail::message>
