<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>
    </head>
    <body>
        <form action=" {{ route('stripepayment')}}" method="POST">
                @csrf
                <label for="prix">Prix:</label>
                <input type="text" name="prix" id="prix">
                <button type="submit">Procéder au paiement</button>
        </form>
    </body>
</html>