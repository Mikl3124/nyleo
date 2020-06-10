<div class="text-center mb-3">
<a class="btn btn-primary btn-block"href="{{ route('message.show') }}"><i class="fas fa-comments"></i> Envoyer un message</a>
</div>

<div class="card">
  <ul class="list-group list-group-flush">
    <a href="{{ route('home') }}" class="list-group-item navlink"><i class="fas fa-home"></i> Accueil</li></a>
    <a href="{{ route('upload.page') }}" class="list-group-item navlink"><i class="fas fa-upload"></i> Envoyer un document</li></a>
    <a href="{{ route('documents.show', Auth::user()->id ) }}" class="list-group-item navlink"><i class="fas fa-briefcase"></i> Mes documents</li></a>
  </ul>
</div>
