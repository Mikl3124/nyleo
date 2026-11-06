@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
@php
    // Couleurs posées aussi en attributs (bgcolor) : Outlook ignore une partie du CSS.
    [$bg, $fg] = match ($color) {
        'secondary', 'blue-solid' => ['#1d3f91', '#ffffff'],
        'success', 'green' => ['#2f7a4b', '#ffffff'],
        'error', 'red' => ['#a3271a', '#ffffff'],
        default => ['#e8a92b', '#101c36'],
    };
@endphp
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table class="button-table" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="button-cell" align="center" bgcolor="{{ $bg }}" style="border-radius: 6px; background-color: {{ $bg }};">
<a href="{{ $url }}" class="button button-{{ $color }}" target="_blank" rel="noopener" style="color: {{ $fg }};">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
