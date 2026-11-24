{{-- Fichiers envoyés par le client (fiche dossier admin) : vignette pour les photos, un clic ouvre le fichier. --}}
@php($files = $getState() ?? collect())
<div class="nyleo-client-files">
    @foreach ($files as $doc)
        @php($url = route('admin.documents.download', $doc))
        @php($isImage = str_starts_with((string) $doc->mime_type, 'image/') && ! str_contains((string) $doc->mime_type, 'hei'))
        <div class="nyleo-client-file">
            <a href="{{ $url }}" target="_blank" rel="noopener" class="nyleo-client-file-thumb" title="Ouvrir {{ $doc->original_name ?: $doc->name }}">
                @if ($isImage)
                    <img src="{{ $url }}" alt="" loading="lazy">
                @else
                    <span class="nyleo-client-file-ext">{{ strtoupper(pathinfo($doc->original_name ?: $doc->path, PATHINFO_EXTENSION) ?: 'Fichier') }}</span>
                @endif
            </a>
            <div class="nyleo-client-file-info">
                <a href="{{ $url }}" target="_blank" rel="noopener" class="nyleo-client-file-name">{{ $doc->original_name ?: $doc->name }}</a>
                <span class="nyleo-client-file-meta">Reçu le {{ $doc->created_at->format('d/m/Y à H:i') }}</span>
                <a href="{{ route('admin.documents.download', [$doc, 'telecharger' => 1]) }}" class="nyleo-client-file-dl">Télécharger</a>
            </div>
        </div>
    @endforeach
</div>
