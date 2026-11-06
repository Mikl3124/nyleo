@php($photo = file_exists(public_path('img/mickael.jpg')) ? asset('img/mickael.jpg') : null)
<div class="portrait">
    @if ($photo)<img src="{{ $photo }}" alt="Mickaël Delpech, dessinateur" loading="lazy">@else<span class="mono" aria-hidden="true">MD</span>@endif
    <div class="cap"><b>Mickaël Delpech</b>Dessinateur · {{ \App\Support\Geo::base()['name'] }} (24)</div>
</div>
