<p class="muted">Photos de la maison et du terrain sous plusieurs angles, plan cadastral, croquis… Vous pouvez en choisir plusieurs à la fois, directement depuis votre téléphone.</p>
<form method="post" action="{{ route('portal.dossiers.upload', $dossier) }}" enctype="multipart/form-data" class="upload">
    @csrf
    <input id="files" class="file-input" type="file" name="files[]" multiple accept="image/*,.pdf" required>
    <label for="files" class="btn secondary block file-pick">📷 Choisir des photos ou des documents</label>
    <p class="file-count muted" aria-live="polite" hidden></p>
    <p><button class="btn {{ $primary ? 'cta' : '' }} block" type="submit">Envoyer</button></p>
</form>
