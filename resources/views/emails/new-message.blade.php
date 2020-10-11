<!DOCTYPE html>
<html lang="en">

<head>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&amp;display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>

<body style="font-family: 'Roboto', sans-serif;color:#353b48;">
    <span class="preheader">Vous avez un nouveau message</span>
    <table with="100%" style="max-width:650px;display:block;margin:auto;">
        <tr>
          <td>

          </td>
        </tr>
        <tr style="text-align:center">
            <td width="100%">
                <img style="height:80px" src="https://nyleo.s3.eu-west-3.amazonaws.com/Logo-nyleoxhdpi.png" alt="logo iziplans">
            </td>
        </tr>
        <tr>
          <td width="100%" style="font-size:22px;display: block;margin-bottom:30px;">Bonjour</td>
        </tr>
        @isset($messageField)
            <tr>
            <td width="100%" style="font-size:22px;display: block;margin-bottom:30px;">Vous avez reçu un nouveau message sur la plateforme Nyleo Conception </td>
            </tr>
            <tr>
                <td width="100%" style="font-size:22px;display: block;margin-bottom:30px;">Le voici: "{{ $messageField }}"</td>
            </tr>
        @endisset

        @empty($messageField)
            <tr>
                <td width="100%" style="font-size:22px;display: block;margin-bottom:30px;">Vous avez reçu un nouveau document de {{$from_id->lastname}} {{$from_id->firstname}}</td>
            </tr>

        @endempty

        <tr>
            <td width="100%" style="font-size:22px;display: block;margin-bottom:30px;"></td>
        </tr>

        <tr style="text-align:center">
            <td width="100%"><a href="https://clients.nyleo.fr" style="padding:15px;width:150px;text-align:center;border-radius:3px;color:white;font-weight: bold;text-decoration:none;background-color:#ff6600;font-size:20px;">Accéder à la plateforme</a></td>
        </tr>
    </table>

</body>
</html>