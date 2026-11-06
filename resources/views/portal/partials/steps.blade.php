{{-- Frise « Où en est mon dossier ? » : étapes faites (avec leur date), étape en cours, étapes à venir. --}}
@php
    $signed = $dossier->quotes->firstWhere('status.value', 'signe');
    $validatedPlan = $dossier->documents->filter(fn ($d) => $d->validated_at)->sortByDesc('validated_at')->first();
    // Une ligne de détail par étape (date, numéro, échéance), affichée sous son nom.
    $details = [
        'devis' => $signed?->signed_at ? 'Signé le '.$signed->signed_at->format('d/m/Y') : null,
        'plans' => $current === 'plans' ? 'Je vous préviens par e-mail dès qu\'ils sont prêts.' : null,
        'validation' => $validatedPlan ? 'Validés le '.$validatedPlan->validated_at->format('d/m/Y') : ($current === 'validation' ? 'À vous de jouer : ouvrez les plans ci-dessus.' : null),
        'depot' => $dossier->deposit_date ? 'Déposé le '.$dossier->deposit_date->format('d/m/Y').($dossier->deposit_number ? ', n° '.$dossier->deposit_number : '') : null,
        'instruction' => $current === 'instruction' && $dossier->decision_due_date ? 'Réponse de la mairie attendue au plus tard le '.$dossier->decision_due_date->format('d/m/Y') : null,
        'decision' => $dossier->decision_date ? $dossier->status->label().' le '.$dossier->decision_date->format('d/m/Y') : null,
    ];
    if ($current === 'instruction' && $dossier->status->value === 'incomplet' && $dossier->pieceRequests->isEmpty()) {
        $details['instruction'] = 'La mairie a demandé des compléments'.($dossier->incomplete_notified_at ? ' le '.$dossier->incomplete_notified_at->format('d/m/Y') : '').' : je m\'en occupe.';
    } elseif ($current === 'instruction' && $dossier->status->value === 'incomplet') {
        $details['instruction'] = 'Le délai de la mairie est suspendu jusqu\'à l\'envoi des pièces.';
    }
    // Chaque demande de pièces de la mairie, avec son état.
    $pieceLines = $current === 'instruction' ? $dossier->pieceRequests->sortBy('requested_at')->map(fn ($pr) => [
        'Pièces demandées par la mairie'.($pr->requested_at ? ' le '.$pr->requested_at->format('d/m/Y') : '').($pr->pieces ? ' : '.$pr->pieces : '').'.',
        match (true) {
            $pr->sent_at !== null => 'Envoyées le '.$pr->sent_at->format('d/m/Y').'.',
            $pr->waiting_on?->value === 'client' => 'J\'ai besoin de documents de votre part'.($pr->deadline ? ', avant le '.$pr->deadline->copy()->subDays(15)->format('d/m/Y') : '').'.',
            default => 'Je prépare la réponse'.($pr->deadline ? ' (à remettre avant le '.$pr->deadline->format('d/m/Y').')' : '').'.',
        },
    ]) : collect();
    if ($current === 'instruction' && $dossier->instruction_started_at) {
        $details['instruction'] = trim('Instruction commencée le '.$dossier->instruction_started_at->format('d/m/Y').'. '.($details['instruction'] ?? ''));
    }
@endphp
<section class="card">
    <h2>Où en est mon dossier ?</h2>
    <ol class="steps">
        @foreach ($steps as $key => $label)
            @php
                $i = $loop->index;
                $state = $i < $currentIndex || ($key === 'decision' && $closed) ? 'done' : ($i === $currentIndex ? 'current' : 'todo');
            @endphp
            <li class="{{ $state }}" @if ($state === 'current') aria-current="step" @endif>
                <span class="dot">{{ $state === 'done' ? '✓' : $i + 1 }}</span>
                <span class="step-body">
                    <span class="step-label">{{ $label }}</span>
                    @if ($state === 'current' && ! $closed)<span class="step-now">En cours</span>@endif
                    @if ($details[$key] ?? null)<span class="step-detail">{{ $details[$key] }}</span>@endif
                    @if ($key === 'instruction')
                        @foreach ($pieceLines as $line)
                            <span class="step-detail">{{ $line[0] }} <strong>{{ $line[1] }}</strong></span>
                        @endforeach
                    @endif
                </span>
            </li>
        @endforeach
    </ol>
</section>
