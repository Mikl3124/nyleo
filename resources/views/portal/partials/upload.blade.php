{{--
    Zone glisser-déposer : aperçus, retrait d'un fichier, barre de progression, appareil photo sur mobile.
    Sans JavaScript, le formulaire classique fonctionne quand même (les deux champs portent files[]).
--}}
<form class="dz" method="post" action="{{ route('portal.dossiers.upload', $dossier) }}" enctype="multipart/form-data" data-max-files="20" data-max-size="20971520" novalidate>
    @csrf
    <div class="dz-zone" tabindex="-1">
        <svg class="dz-icon" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M24 31V13m0 0-7 7m7-7 7 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 29v6a4 4 0 0 0 4 4h20a4 4 0 0 0 4-4v-6" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        <p class="dz-title dz-desktop">Glissez vos photos ou documents ici</p>
        <p class="dz-or dz-desktop">ou</p>
        <div class="dz-buttons">
            <input id="dz-camera" class="file-input" type="file" name="files[]" accept="image/*" capture="environment">
            <label for="dz-camera" class="btn secondary dz-mobile">📷 Prendre une photo</label>
            <input id="dz-files" class="file-input" type="file" name="files[]" multiple accept="image/*,.pdf,application/pdf">
            <label for="dz-files" class="btn secondary">🖼️ Choisir des fichiers</label>
        </div>
        <p class="dz-hint">Photos ou PDF · 20 Mo maximum par fichier · 20 fichiers à la fois</p>
    </div>

    <ul class="dz-list" aria-live="polite"></ul>
    <p class="dz-error" role="alert" hidden></p>
    <div class="dz-progress" hidden>
        <div class="progress-bar"><span></span></div>
        <p class="dz-progress-text">Envoi en cours…</p>
    </div>
    <p class="dz-actions"><button class="btn block dz-submit" type="submit">Envoyer</button></p>
</form>

<script>
(function () {
    var form = document.currentScript.previousElementSibling;
    if (!form || !window.FormData || !window.XMLHttpRequest) return;
    form.classList.add('dz-js');
    // Téléphone ou tablette : bouton « Prendre une photo », pas de glisser-déposer.
    if ((window.matchMedia && matchMedia('(pointer: coarse)').matches) || navigator.maxTouchPoints > 0) form.classList.add('dz-touch');
    var zone = form.querySelector('.dz-zone'), list = form.querySelector('.dz-list'),
        error = form.querySelector('.dz-error'), submit = form.querySelector('.dz-submit'),
        progress = form.querySelector('.dz-progress'), bar = progress.querySelector('.progress-bar span'),
        progressText = progress.querySelector('.dz-progress-text'),
        maxFiles = +form.dataset.maxFiles, maxSize = +form.dataset.maxSize, files = [];
    var accepted = /^image\/(jpe?g|png|heic|heif|webp)$|^application\/pdf$/i, acceptedExt = /\.(jpe?g|png|heic|heif|webp|pdf)$/i;

    function size(b) { return b > 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' Mo' : Math.max(1, Math.round(b / 1024)) + ' Ko'; }
    function showError(msg) { error.textContent = msg || ''; error.hidden = !msg; }

    function render() {
        list.innerHTML = '';
        files.forEach(function (f, i) {
            var li = document.createElement('li'), thumb = document.createElement('span');
            thumb.className = 'dz-thumb';
            if (/^image\/(jpe?g|png|webp|gif)$/i.test(f.type)) {
                var img = document.createElement('img');
                img.alt = ''; img.src = URL.createObjectURL(f);
                img.onload = function () { URL.revokeObjectURL(img.src); };
                thumb.appendChild(img);
            } else {
                thumb.textContent = /pdf$/i.test(f.type) || /\.pdf$/i.test(f.name) ? 'PDF' : 'Photo';
            }
            var body = document.createElement('span');
            body.className = 'dz-name';
            body.innerHTML = '<strong></strong><span class="muted"></span>';
            body.firstChild.textContent = f.name;
            body.lastChild.textContent = size(f.size);
            var rm = document.createElement('button');
            rm.type = 'button'; rm.className = 'dz-remove'; rm.textContent = '×';
            rm.setAttribute('aria-label', 'Retirer ' + f.name);
            rm.onclick = function () { files.splice(i, 1); render(); };
            li.append(thumb, body, rm);
            list.appendChild(li);
        });
        submit.hidden = files.length === 0;
        submit.textContent = files.length > 1 ? 'Envoyer les ' + files.length + ' fichiers' : 'Envoyer le fichier';
    }

    function add(fileList) {
        showError('');
        var rejected = [];
        Array.prototype.forEach.call(fileList, function (f) {
            if (!(accepted.test(f.type) || acceptedExt.test(f.name))) return rejected.push(f.name + ' (format non accepté)');
            if (f.size > maxSize) return rejected.push(f.name + ' (plus de 20 Mo)');
            if (files.length >= maxFiles) return rejected.push(f.name + ' (20 fichiers au maximum)');
            if (files.some(function (g) { return g.name === f.name && g.size === f.size; })) return;
            files.push(f);
        });
        if (rejected.length) showError('Non ajouté : ' + rejected.join(', ') + '.');
        render();
    }

    form.querySelectorAll('input[type=file]').forEach(function (input) {
        input.addEventListener('change', function () { add(input.files); input.value = ''; });
    });
    ['dragenter', 'dragover'].forEach(function (ev) {
        zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('over'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        zone.addEventListener(ev, function (e) { e.preventDefault(); if (ev === 'drop' || !zone.contains(e.relatedTarget)) zone.classList.remove('over'); });
    });
    zone.addEventListener('drop', function (e) { if (e.dataTransfer) add(e.dataTransfer.files); });
    // Un fichier lâché à côté de la zone ne doit pas ouvrir une autre page.
    window.addEventListener('dragover', function (e) { e.preventDefault(); });
    window.addEventListener('drop', function (e) { if (!zone.contains(e.target)) e.preventDefault(); });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!files.length) return showError('Choisissez au moins une photo ou un document.');
        var data = new FormData();
        data.append('_token', form.querySelector('input[name=_token]').value);
        files.forEach(function (f) { data.append('files[]', f, f.name); });
        var xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.upload.onprogress = function (ev) {
            if (!ev.lengthComputable) return;
            var p = Math.round(ev.loaded / ev.total * 100);
            bar.style.width = p + '%';
            progressText.textContent = p < 100 ? 'Envoi en cours… ' + p + ' %' : 'Presque fini…';
        };
        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                progressText.textContent = 'Envoyé, merci !';
                window.location.reload();
                return;
            }
            var msg = 'L\'envoi n\'a pas abouti. Réessayez, ou envoyez vos photos par e-mail.';
            try { var r = JSON.parse(xhr.responseText); if (r.errors) msg = Object.values(r.errors)[0][0]; else if (xhr.status === 419) msg = 'La page a expiré : rechargez-la puis réessayez.'; } catch (err) {}
            done(msg);
        };
        xhr.onerror = function () { done('Pas de connexion internet ? Réessayez dans un instant.'); };
        progress.hidden = false; submit.disabled = true; bar.style.width = '0';
        list.classList.add('sending');
        xhr.send(data);
    });

    function done(msg) {
        progress.hidden = true; submit.disabled = false; list.classList.remove('sending');
        showError(msg);
    }
    render();
})();
</script>
