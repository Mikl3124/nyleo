<x-filament-panels::page>
@php
$rows = $this->invoices();
$sync = \App\Models\ExternalInvoice::max('source_exported_at');
$money = fn ($n, $currency) => number_format($n / 100, 2, ',', ' ').' '.$currency;
@endphp
<x-filament::section heading="Une facture, un seul enregistrement">
<p>Les factures Permis-construire sont émises sur Permis-construire et suivies ici. Aucun nouvel envoi ni nouveau paiement n’est déclenché par la synchronisation.</p>
<p style="margin-top:8px;color:#64748b">Dernier export reçu : {{ $sync ? \Carbon\Carbon::parse($sync)->timezone('Europe/Paris')->format('d/m/Y à H:i') : 'En attente du premier import' }}.</p>
@if($sync && \Carbon\Carbon::parse($sync)->lt(now()->subMinutes(20)))
<p style="color:#9a3412;margin-top:8px">La synchronisation est en retard. Les montants ci-dessous peuvent ne plus être à jour.</p>
@endif
</x-filament::section>
@foreach($rows->where('counted', true)->groupBy(fn ($r) => $r['issuer'].' · '.$r['currency']) as $issuer => $group)
<x-filament::section :heading="'Émetteur — SIRET '.$issuer">
<div style="display:flex;gap:32px;flex-wrap:wrap">
<div><div>Montant des factures, avoirs déduits</div><strong style="font-size:24px">{{ $money($group->sum('total'), $group->first()['currency']) }}</strong></div>
<div><div>Règlements enregistrés</div><strong style="font-size:24px">{{ $money($group->sum('paid'), $group->first()['currency']) }}</strong></div>
</div>
<p style="margin-top:8px;color:#64748b">Règlements bruts, avant frais Stripe. Ce récapitulatif ne constitue pas une déclaration de chiffre d’affaires.</p>
</x-filament::section>
@endforeach
<x-filament::section heading="Toutes les factures">
<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;text-align:left">
<thead><tr>@foreach(['Date / site','Facture / client','Émetteur (SIRET)','Montant','Réglé','État',''] as $heading)<th style="padding:12px;border-bottom:2px solid #e2e8f0">{{ $heading }}</th>@endforeach</tr></thead>
<tbody>@forelse($rows as $r)<tr>
<td style="padding:12px;border-bottom:1px solid #e2e8f0;white-space:nowrap">{{ $r['date'] ? \Carbon\Carbon::parse($r['date'])->format('d/m/Y') : '—' }}<br><small>{{ $r['source'] }}</small></td>
<td style="padding:12px;border-bottom:1px solid #e2e8f0"><strong>{{ $r['number'] }}</strong><br>{{ $r['client'] }}@if($r['correction'])<br><small>Identité de l’émetteur rectifiée</small>@endif</td>
<td style="padding:12px;border-bottom:1px solid #e2e8f0">{{ $r['issuer'] }}</td>
<td style="padding:12px;border-bottom:1px solid #e2e8f0;white-space:nowrap">{{ $money($r['total'],$r['currency']) }}</td>
<td style="padding:12px;border-bottom:1px solid #e2e8f0;white-space:nowrap">{{ $money($r['paid'],$r['currency']) }}</td>
<td style="padding:12px;border-bottom:1px solid #e2e8f0">{{ $r['status'] }}</td>
<td style="padding:12px;border-bottom:1px solid #e2e8f0"><a href="{{ $r['url'] }}" target="_blank" rel="noopener" style="font-weight:700;text-decoration:underline">Consulter</a></td>
</tr>@empty<tr><td colspan="7" style="padding:20px">Aucune facture émise.</td></tr>@endforelse</tbody>
</table></div>
</x-filament::section>
</x-filament-panels::page>
