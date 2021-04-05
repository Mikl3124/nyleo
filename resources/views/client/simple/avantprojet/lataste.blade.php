<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  <title>M.Lataste</title>
</head>

<body>
  <div class="container">
    <div class="text-center mb-3">
      <h1 class="text-center mt-5 mb-5">M.Lataste</h1>
    </div>
    <div>
      <p class="ml-2">Nyleo Conception, a mis à disposition vos plans d'avant-projet, pour consultation.</p>
      <p class="ml-2">Si ceux-ci vous conviennent, vous pouvez effectuer un règlement, directement, en cliquant sur
        le bouton
        ci-dessous.</p>
    </div>
    <div class="text-center mb-3">
        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#ModelPaiement">
       Paiement sécurisé
        </button>

    </div>

    <div class="text-center">
          <div class="text-center">
      <iframe allowfullscreen="true" style="border:none;width:100%;height:600px;"
        src="//e.issuu.com/embed.html?d=plans_modificatifs_v_2_f60f86530f6a6c&pageLayout=singlePage&u=mickaeldelpech"></iframe>
    </div>
  </div>

</body>

<!-- Modal -->
<div class="modal fade" id="ModelPaiement" tabindex="-1" role="dialog" aria-labelledby="ModelPaiementTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header text-center">
        <h5 class="modal-title">Paiement Sécurisé</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <p>Saisissez la somme que vous souhaitez régler à Nyleo Conception</p>
        <form action="http://nyleo.test/paiement" method="post">
        @csrf
        <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text">€</span>
        </div>
        <input type="text" class="form-control" name="total">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Retour</button>
        <button type="submit" class="btn btn-success">Payer</button>
    </form>
      </div>
    </div>
  </div>
</div>

</html>