<div class="plates">
    @foreach ($pieces as [$code, $name, $hint])
        <figure class="plate" style="margin:0">
            <div class="sheet">@include('site.partials.sheet-icon', ['code' => $code])</div>
            <figcaption class="cart"><b>{{ $code }}</b><span>{{ $name }}</span></figcaption>
        </figure>
    @endforeach
</div>
