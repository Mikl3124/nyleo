<?php

namespace App\Support;

use App\Models\ClientActivity;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Résumé du suivi client d'un devis, d'une facture, d'un dossier ou d'un client :
 * « Vu le 06/11 à 14 h 02 », « Ouvert le … », « Envoyé, pas encore ouvert ».
 */
class TrackingSummary
{
    /** Ajoute à la requête de la liste les dates utiles (une seule requête pour toute la page). */
    public static function withAggregates(Builder $query): Builder
    {
        return $query
            ->withMax(['activities as tracking_read_at' => fn ($q) => $q->where('type', '!=', ClientActivity::OPENED)], 'created_at')
            ->withMax(['activities as tracking_opened_at' => fn ($q) => $q->where('type', ClientActivity::OPENED)], 'created_at')
            ->withMax('emailMessages as tracking_sent_at', 'sent_at');
    }

    /** @return array{label: string, color: string}|null */
    public static function of(Model $record): ?array
    {
        $read = $record->getAttribute('tracking_read_at') ?? $record->activities()->where('type', '!=', ClientActivity::OPENED)->max('created_at');
        if ($read) {
            return ['label' => 'Vu le '.ClientActivity::when(Carbon::parse($read)), 'color' => 'success'];
        }
        $opened = $record->getAttribute('tracking_opened_at') ?? $record->activities()->where('type', ClientActivity::OPENED)->max('created_at');
        if ($opened) {
            return ['label' => 'E-mail ouvert le '.ClientActivity::when(Carbon::parse($opened)), 'color' => 'info'];
        }
        $sent = $record->getAttribute('tracking_sent_at') ?? $record->emailMessages()->max('sent_at');
        if ($sent) {
            return ['label' => 'Envoyé le '.ClientActivity::when(Carbon::parse($sent)).', pas encore ouvert', 'color' => 'gray'];
        }

        return null;
    }

    /** Colonne « Suivi client » des listes. */
    public static function column(): TextColumn
    {
        return TextColumn::make('tracking')->label('Suivi client')->badge()
            ->state(fn (Model $record) => self::of($record)['label'] ?? null)
            ->color(fn (Model $record) => self::of($record)['color'] ?? 'gray')
            ->placeholder('—')
            ->toggleable();
    }
}
