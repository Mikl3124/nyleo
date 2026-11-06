@php
    // Couleurs du site nyleo.fr : bleu de Prusse (réglable dans les Paramètres) et ocre.
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', $company['pdf_color'] ?? '') ? $company['pdf_color'] : '#1D3F91';
    $accent = '#E8A92B';
    $ink = '#101C36';
@endphp
<style>
    @page { margin: 40px 46px 110px 46px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #2b3346; line-height: 1.45; }
    table { border-collapse: collapse; }
    .head { width: 100%; }
    .head td { vertical-align: top; }
    .head td.brand { padding-top: 2px; }
    .head td.issuer { text-align: right; }
    .accent { height: 0; border-top: 2px solid {{ $accent }}; margin: 14px 0 16px; }
    .head2 { width: 100%; margin-bottom: 18px; }
    .head2 td { vertical-align: top; width: 50%; }
    .head2 td.client { padding-left: 14px; border-left: 3px solid {{ $color }}; }
    .label { font-size: 7.5px; letter-spacing: 1.2px; text-transform: uppercase; color: {{ $color }}; font-weight: bold; margin-bottom: 4px; }
    .party-name { font-size: 12px; font-weight: bold; color: {{ $ink }}; margin-bottom: 8px; }
    .party-line { font-size: 9.5px; color: #3d434a; }
    .doc-title { font-size: 19px; font-weight: bold; color: {{ $color }}; margin: 0 0 8px; }
    .dates { font-size: 10px; color: #3d434a; margin-bottom: 14px; }
    .dates div { margin-bottom: 4px; }
    .note { font-size: 9.5px; color: #3d434a; margin: -8px 0 14px; }
    table.lines { width: 100%; }
    table.lines th { background: {{ $color }}; color: #fff; font-weight: bold; font-size: 10px; padding: 7px 8px; text-align: right; white-space: nowrap; }
    table.lines th.first { text-align: left; border-top-left-radius: 5px; border-bottom-left-radius: 5px; border-right: 1px solid #ffffff55; }
    table.lines th.last { border-top-right-radius: 5px; border-bottom-right-radius: 5px; }
    table.lines td { padding: 8px 10px 2px; vertical-align: top; text-align: right; font-size: 10px; }
    table.lines td.label { text-align: left; text-transform: uppercase; color: #2b3036; }
    table.lines td.desc { text-align: left; font-size: 8.5px; color: #3d434a; padding: 4px 10px 12px; border-bottom: 1px solid #e8edf2; }
    table.lines tr.no-desc td { padding-bottom: 12px; border-bottom: 1px solid #e8edf2; }
    .desc ul { margin: 4px 0 2px 0; padding-left: 22px; }
    .desc li { margin: 1px 0; }
    .desc .subtitle { font-weight: bold; color: #2b3036; margin-top: 1px; }
    .desc .gap { height: 6px; }
    .rule { border-top: 1px solid #d5dde5; margin-top: 2px; }
    table.bottom { width: 100%; margin-top: 16px; }
    table.bottom td { vertical-align: top; }
    .pill { background: {{ $color }}; color: #fff; font-weight: bold; font-size: 10px; padding: 7px 12px; border-radius: 5px; width: 230px; }
    table.qr { margin-top: 12px; border-collapse: collapse; }
    table.qr td { vertical-align: middle; padding: 0; }
    table.qr td.qr-img img { width: 84px; height: 84px; display: block; }
    table.qr td.qr-text { font-size: 8.5px; color: #3d434a; padding-left: 10px; width: 150px; }
    .pill-text { font-size: 10px; padding: 10px 0 0 0; color: #3d434a; }
    table.totals { width: 100%; }
    table.totals td { padding: 5px 10px; font-size: 10px; }
    table.totals td.k { font-weight: bold; color: #2b3036; }
    table.totals td.v { text-align: right; }
    table.totals tr.sep td { border-bottom: 1px solid #d5dde5; }
    table.totals tr.grand td { font-weight: bold; padding-top: 10px; }
    .sign { border: 1px solid #d5dde5; border-top: 3px solid {{ $accent }}; border-radius: 6px; padding: 14px 18px; height: 90px; margin-top: 16px; margin-left: 52%; font-size: 10px; }
    .sign strong { color: {{ $ink }}; }
    .sign .small { font-size: 8px; color: #6b7480; margin-top: 6px; }
    .terms { margin-top: 22px; font-size: 8.5px; color: #8a929c; }
    .terms .t { font-weight: bold; margin-bottom: 3px; }
    .paid { margin-top: 10px; font-size: 9px; color: #3d434a; }
    .footer { position: fixed; bottom: -80px; left: 0; right: 0; height: 80px; border-top: 2px solid {{ $color }}; padding-top: 12px; font-size: 8.5px; color: #8a929c; }
    .footer table td { vertical-align: top; padding-right: 30px; }
    .footer .t { font-weight: bold; }
</style>
