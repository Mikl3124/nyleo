@props(['preheader' => null])
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="x-apple-disable-message-reformatting" />
<meta name="format-detection" content="telephone=no, date=no, address=no, email=no" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<!--[if mso]>
<noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
<style>body, table, td, p, a, li { font-family: Arial, Helvetica, sans-serif !important; }</style>
<![endif]-->
<style>
@media only screen and (max-width: 600px) {
.inner-body, .footer { width: 100% !important; }
.body { padding: 0 8px !important; }
.content-cell { padding: 26px 20px !important; }
.header { padding: 22px 0 16px !important; }
h1 { font-size: 22px !important; }
}
@media only screen and (max-width: 500px) {
.button-table, .button-cell, .button { width: 100% !important; }
.button { padding-left: 0 !important; padding-right: 0 !important; border-left-width: 0 !important; border-right-width: 0 !important; text-align: center !important; }
.recap-label, .recap-value { display: block !important; width: 100% !important; }
.recap-label { padding-bottom: 0 !important; border-bottom: 0 !important; }
.recap-value { padding-top: 2px !important; text-align: left !important; }
}
</style>
{!! $head ?? '' !!}
</head>
<body>
@if ($preheader)
{{-- Texte d'aperçu affiché par Gmail, Outlook et l'iPhone sous l'objet ; invisible dans l'e-mail ouvert. --}}
<div class="preheader" style="display: none; max-height: 0; overflow: hidden; mso-hide: all; font-size: 1px; line-height: 1px; color: #f2eee6; opacity: 0;">{{ $preheader }}&#847; &zwnj; &nbsp; &#847; &zwnj; &nbsp; &#847; &zwnj; &nbsp; &#847; &zwnj; &nbsp; &#847; &zwnj; &nbsp; &#847; &zwnj; &nbsp; &#847; &zwnj; &nbsp; &#847; &zwnj; &nbsp;</div>
@endif

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#f2eee6">
<tr>
<td align="center">
<!--[if mso]><table role="presentation" align="center" width="570" cellpadding="0" cellspacing="0"><tr><td><![endif]-->
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<!-- Corps -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#ffffff">
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
<!--[if mso]></td></tr></table><![endif]-->
</td>
</tr>
</table>
</body>
</html>
