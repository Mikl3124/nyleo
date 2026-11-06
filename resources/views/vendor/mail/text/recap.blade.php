@props(['rows' => []])
@foreach (array_filter($rows, fn ($v) => filled($v)) as $label => $value)
{{ $label }} : {{ $value }}
@endforeach
