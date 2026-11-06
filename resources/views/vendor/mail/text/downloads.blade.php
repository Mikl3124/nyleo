@props(['items' => []])
@foreach ($items as $item)
{{ $item['label'] }} : {{ $item['url'] }}
@endforeach
