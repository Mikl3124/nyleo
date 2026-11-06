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
    if ($current === 'instruction' && $dossier->status->value === 'incomplet') {
        $details['instruction'] = 'La mairie a demandé des compléments'.($dossier->incomplete_notified_at ? ' le '.$dossier->incomplete_notified_at->format('d/m/Y') : '').' : je m\'en occupe.';
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
                </span>
            </li>
        @endforeach
    </ol>
</section>
