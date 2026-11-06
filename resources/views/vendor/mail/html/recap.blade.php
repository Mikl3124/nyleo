@props(['rows' => []])
@php($rows = array_filter($rows, fn ($v) => filled($v)))
@if ($rows)
<table class="recap" width="100%" cellpadding="0" cellspacing="0" role="presentation">
@foreach ($rows as $label => $value)
<tr>
<td class="recap-label" width="42%" valign="top">{{ $label }}</td>
<td class="recap-value" valign="top">{{ $value }}</td>
</tr>
@endforeach
</table>
@endif
