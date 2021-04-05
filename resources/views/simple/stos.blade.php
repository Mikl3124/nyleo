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
  <title>M et Mme STOS</title>
</head>

<body>
  <div class="container">
    <div class="text-center mb-3">
      <h1 class="text-center mt-5 mb-5">M et Mme STOS</h1>
    </div>
    <div>
      <p class="ml-2">Nyleo Conception, a mis à disposition vos plans d'avant-projet, pour consultation.</p>
      <p class="ml-2">Si ceux-ci vous conviennent, vous pouvez effectuer le règlement du solde du devis, directement, en
        cliquant sur
        le bouton
        ci-dessous.</p>
    </div>
    <div class="text-center mb-3">
      <button type="button" class="btn btn-success" data-toggle="modal" data-target="#exampleModalCenter">
        Paiement sécurisé
      </button>
    </div>


    <div class="text-center">
      <iframe allowfullscreen="true" style="border:none;width:100%;height:600px;" src="//e.issuu.com/embed.html?d=plans_pour_pc_watermark&pageLayout=singlePage&u=mickaeldelpech"></iframe>
    </div>
  </div>  

</body>

<!-- Select amount modal -->
<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Paiement Sécurisé</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form action="http://nyleo.test/paiement" method="post">
        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <div class="form-group">
            <input type="text" class="form-control" name="customer" placeholder="Votre nom" required>
        </div>
        <label for="amount">Saisissez le montant</label>
          <div class="input-group mb-3">
            <div class="input-group-prepend">
              <span class="input-group-text">€</span>
            </div>
            <input type="number" step="0.01" name="total" class="form-control" required>
            <div class="input-group-append">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Retour</button>
            <button type="submit" class="btn btn-success">Payer</button>
          </div>
        </form>

      </div>

    </div>
  </div>
</div>

</html>