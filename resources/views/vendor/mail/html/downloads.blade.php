@props(['items' => []])
{{-- Liens « Télécharger le PDF » sous le bouton principal : [['label' => ..., 'url' => ...], ...] --}}
@if ($items)
<p class="downloads-title">{{ count($items) > 1 ? 'Vos documents en PDF :' : 'Ou en fichier PDF :' }}</p>
@foreach ($items as $item)
<table class="action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 6px auto;">
<tr>
<td align="center">
<table class="button-table" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="button-cell" align="center" bgcolor="#ffffff" style="border: 2px solid #1d3f91; border-radius: 6px; background-color: #ffffff;">
<a href="{{ $item['url'] }}" class="button button-outline" target="_blank" rel="noopener" style="color: #1d3f91;">{{ $item['label'] }}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
@endforeach
@endif
