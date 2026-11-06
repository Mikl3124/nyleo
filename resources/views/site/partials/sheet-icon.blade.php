{{-- Miniature de planche selon le code de la pièce --}}
@php($k = $code === 'PCMI4' ? '11' : preg_replace('/^(PCMI|DP)/', '', $code))
<svg viewBox="0 0 60 42" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($k)
    @case('1')<path d="M4 30c8-6 12 2 20-4s14-10 32-6M6 12c10 4 16-6 26-2s14 6 24 2M20 4l6 34" opacity=".55"/><circle cx="31" cy="20" r="3.2" fill="currentColor"/><circle cx="31" cy="20" r="7"/>@break
    @case('2')<path d="M8 6h44v30H8z" stroke-dasharray="3 2"/><rect x="20" y="14" width="16" height="11" fill="currentColor" fill-opacity=".18"/><rect x="36" y="17" width="7" height="8"/><path d="M8 40h44M8 38v4M52 38v4M20 30h16" /><text x="26" y="33" font-size="4" fill="currentColor" stroke="none" font-family="IBM Plex Mono">4,00</text>@break
    @case('3')<path d="M2 32c10-2 16 1 24 0s20-4 32-3"/><path d="M18 31V16l9-7 9 7v15" /><path d="M40 10v22M38 10h4M38 32h4"/><path d="M2 32v8h56v-9" opacity=".4"/>@break
    @case('4')<path d="M10 34V18l20-12 20 12v16z"/><rect x="16" y="22" width="7" height="7"/><rect x="37" y="22" width="7" height="7"/><path d="M27 34v-9h6v9M4 34h52"/>@break
    @case('5')<path d="M10 34V18l20-12 20 12v16z"/><rect x="16" y="22" width="7" height="7"/><rect x="37" y="22" width="7" height="7"/><path d="M27 34v-9h6v9M4 34h52"/>@break
    @case('6')<rect x="4" y="4" width="52" height="34" rx="2"/><path d="M4 30c10-8 16-4 24-9s14-2 28 2" opacity=".5"/><path d="M22 30V20l8-6 8 6v10z" fill="currentColor" fill-opacity=".2"/><circle cx="46" cy="12" r="3"/>@break
    @case('7')<rect x="8" y="10" width="44" height="26" rx="3"/><circle cx="30" cy="23" r="8"/><circle cx="30" cy="23" r="4"/><path d="M22 10l3-4h10l3 4"/>@break
    @case('8')<path d="M2 34c8-10 14-8 20-14s12-6 18 0 12 10 18 6" /><path d="M2 38h56" opacity=".5"/><circle cx="46" cy="10" r="3.5"/><path d="M12 30v-4M16 31v-6" />@break
    @case('11')<path d="M14 6h32v30H14z"/><path d="M19 13h22M19 18h22M19 23h18M19 28h12"/>@break
    @default<path d="M14 4h26l6 6v28H14z"/><path d="M40 4v6h6M19 14h10M19 20h22M19 25h22M19 30h14"/><rect x="34" y="28" width="8" height="5" fill="currentColor" fill-opacity=".25"/>
@endswitch
</svg>
