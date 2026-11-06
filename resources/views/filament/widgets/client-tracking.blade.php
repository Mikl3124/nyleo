<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-eye" :description="$summary['label'] ?? 'Rien d\'envoyé par e-mail pour le moment.'">
        <x-slot name="heading">Suivi client</x-slot>

        <div class="nyleo-track">
            <div class="nyleo-track-col">
                <p class="nyleo-track-title">E-mails envoyés</p>
                @forelse ($emails as $email)
                    <div class="nyleo-track-row">
                        <div class="nyleo-track-main">
                            <strong>{{ $email->subject }}</strong>
                            <span class="nyleo-track-meta">
                                à {{ $email->to }}@if ($email->sent_at), le {{ \App\Models\ClientActivity::when($email->sent_at) }}@endif
                            </span>
                        </div>
                        <x-filament::badge :color="$email->clicked_at ? 'success' : ($email->opened_at ? 'info' : 'gray')">
                            {{ $email->statusLabel() }}
                        </x-filament::badge>
                    </div>
                @empty
                    <p class="nyleo-track-empty">Aucun e-mail suivi pour le moment.</p>
                @endforelse
            </div>

            <div class="nyleo-track-col">
                <p class="nyleo-track-title">Ce que le client a fait</p>
                @forelse ($activities as $activity)
                    <div class="nyleo-track-row">
                        <span class="nyleo-track-date">{{ \App\Models\ClientActivity::when($activity->created_at) }}</span>
                        <div class="nyleo-track-main">
                            <span>{{ $activity->label }}</span>
                            @if ($showDocument && ($activity->quote || $activity->invoice))
                                <span class="nyleo-track-meta">
                                    {{ $activity->quote ? \App\Services\ClientTracking::quoteLabel($activity->quote) : \App\Services\ClientTracking::invoiceLabel($activity->invoice) }}
                                </span>
                            @endif
                        </div>
                        <x-filament::badge :color="$activity->color()">{{ $activity->typeLabel() }}</x-filament::badge>
                    </div>
                @empty
                    <p class="nyleo-track-empty">Le client n'a encore rien ouvert.</p>
                @endforelse
            </div>
        </div>

        <p class="nyleo-track-note">
            « Ouvert » dépend de la messagerie du client (certaines bloquent les images ou les ouvrent automatiquement) ; un clic, une consultation ou un téléchargement est une lecture certaine. Vos propres ouvertures (copie cachée, administration) ne sont jamais comptées.
        </p>
    </x-filament::section>
</x-filament-widgets::widget>
