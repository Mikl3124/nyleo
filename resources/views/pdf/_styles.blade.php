@php
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', $company['pdf_color'] ?? '') ? $company['pdf_color'] : '#94C13D';
@endphp
<style>
    @page { margin: 40px 46px 110px 46px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #2b3036; line-height: 1.45; }
    table { border-collapse: collapse; }
    .head { width: 100%; margin-bottom: 22px; }
    .head td { vertical-align: top; }
    .party-name { font-size: 12px; font-weight: bold; color: #1f2328; margin-bottom: 8px; }
    .party-line { font-size: 9.5px; color: #3d434a; }
    .logo { width: 70px; padding-right: 14px; }
    .doc-title { font-size: 19px; font-weight: bold; color: #1f2328; margin: 0 0 12px; }
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
    .pill-text { font-size: 10px; padding: 10px 0 0 0; color: #3d434a; }
    table.totals { width: 100%; }
    table.totals td { padding: 5px 10px; font-size: 10px; }
    table.totals td.k { font-weight: bold; color: #2b3036; }
    table.totals td.v { text-align: right; }
    table.totals tr.sep td { border-bottom: 1px solid #d5dde5; }
    table.totals tr.grand td { font-weight: bold; padding-top: 10px; }
    .sign { border: 1px solid #d5dde5; border-radius: 6px; padding: 14px 18px; height: 90px; margin-top: 16px; margin-left: 52%; font-size: 10px; }
    .sign strong { color: #2b3036; }
    .sign .small { font-size: 8px; color: #6b7480; margin-top: 6px; }
    .terms { margin-top: 22px; font-size: 8.5px; color: #8a929c; }
    .terms .t { font-weight: bold; margin-bottom: 3px; }
    .paid { margin-top: 10px; font-size: 9px; color: #3d434a; }
    .footer { position: fixed; bottom: -80px; left: 0; right: 0; height: 80px; border-top: 1px solid #d5dde5; padding-top: 12px; font-size: 8.5px; color: #8a929c; }
    .footer table td { vertical-align: top; padding-right: 30px; }
    .footer .t { font-weight: bold; }
</style>
