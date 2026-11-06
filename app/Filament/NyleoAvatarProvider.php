<?php

namespace App\Filament;

use App\Support\Brand;
use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Pastille ocre avec les initiales, comme le monogramme du site.
 * Générée sur place : aucun appel à un service extérieur.
 */
class NyleoAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = collect(preg_split('/\s+/u', trim(Filament::getNameForDefaultAvatar($record))) ?: [])
            ->map(fn (string $word) => mb_substr(preg_replace('/^[^\p{L}\p{N}]+/u', '', $word) ?? '', 0, 1))
            ->filter()
            ->take(2)
            ->join('');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="'.Brand::OCHRE.'"/>'
            .'<text x="32" y="33" dy=".35em" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-weight="700" font-size="26" fill="'.Brand::INK.'">'
            .e(mb_strtoupper($initials ?: '?'))
            .'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
