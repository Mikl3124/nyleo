<x-filament-panels::page>
@php
$allRows = $this->invoices();
$rows = $allRows->where('issuer', '82503053900035');
$sync = \App\Models\ExternalInvoice::max('source_exported_at');
$account = \App\Models\ExternalBillingAccount::where('source','permis-construire.com')->first()?->payload;
$money = fn ($n, $currency='EUR') => number_format($n / 100, 2, ',', ' ').' '.$currency;
@endphp
<x-filament::section heading="Michaël DELPECH · Entrepreneur individuel">
<p><strong>SIRET 82503053900035</strong> · Deux sites, une seule entreprise : Nyleo et Permis-construire.</p>
<p style="margin-top:8px">Synchronisation automatique <strong>toutes les 5 minutes</strong>. Les factures Permis-construire sont suivies ici sans seconde émission.</p>
<p style="margin-top:8px;color:#64748b">Dernière synchronisation : {{ $sync ? \Carbon\Carbon::parse($sync)->timezone('Europe/Paris')->format('d/m/Y à H:i') : 'En attente du premier import' }}.</p>
@if($sync && \Carbon\Carbon::parse($sync)->lt(now()->subMinutes(20)))<p style="color:#9a3412;margin-top:8px">La synchronisation est en retard. Les montants peuvent ne plus être à jour.</p>@endif
</x-filament::section>
@foreach($rows->where('counted', true)->groupBy('currency') as $currency => $group)
@php
$incomplete = $group->contains(fn($r)=>$r['fees']===null);
$periodStart = $group->pluck('date')->filter()->min();
$otherFees = $account && !empty($account['transactions_complete']) ? -collect($account['transactions'])->filter(fn($t)=>$t['currency']===$currency && in_array($t['type'],['stripe_fee','stripe_fx_fee','tax_fee']) && (!$periodStart || $t['created']>=\Carbon\Carbon::parse($periodStart,'Europe/Paris')->timestamp))->sum('net_minor') : null;
$totalFees = $incomplete || $otherFees===null ? null : $group->sum('fees')+$otherFees;
@endphp
<x-filament::section :heading="'Factures et encaissements · '.$currency">
<div style="display:flex;gap:28px;flex-wrap:wrap">
@foreach(['Facturé'=>$group->sum('total'),'Réglé brut'=>$group->sum('paid'),'Frais Stripe (total)'=>$totalFees,'Net après tous frais Stripe'=>$totalFees===null ? null : $group->sum('paid')-$totalFees] as $label=>$amount)
<div><div>{{ $label }}</div><strong style="font-size:24px">{{ $amount===null ? 'À rapprocher' : $money($amount,$currency) }}</strong></div>
@endforeach
</div>
@if($incomplete)<p style="margin-top:10px;color:#9a3412">Certains frais ne sont pas encore confirmés. Ils ne sont pas considérés comme nuls.</p>@endif
<p style="margin-top:10px;color:#64748b">Frais sur les paiements : {{ $incomplete ? 'À rapprocher' : $money($group->sum('fees'),$currency) }} · Autres frais Stripe (dont Radar) : {{ $otherFees===null ? 'À rapprocher' : $money($otherFees,$currency) }}{{ $periodStart ? ' depuis le '.\Carbon\Carbon::parse($periodStart)->format('d/m/Y') : '' }}.</p>
<p style="margin-top:10px;color:#64748b">Le net correspond aux paiements après frais Stripe. Le solde disponible et les virements sont détaillés ci-dessous.</p>
</x-filament::section>
@endforeach
<x-filament::section heading="Factures des deux sites">
<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;text-align:left">
<thead><tr>@foreach(['Date / site','Facture / client','Facturé','Réglé brut','Frais paiement','Net paiement','État',''] as $heading)<th style="padding:10px;border-bottom:2px solid #e2e8f0">{{ $heading }}</th>@endforeach</tr></thead>
<tbody>@forelse($rows as $r)<tr>
<td style="padding:10px;border-bottom:1px solid #e2e8f0;white-space:nowrap">{{ $r['date'] ? \Carbon\Carbon::parse($r['date'])->format('d/m/Y') : '—' }}<br><small>{{ $r['source'] }}</small></td>
<td style="padding:10px;border-bottom:1px solid #e2e8f0"><strong>{{ $r['number'] }}</strong><br>{{ $r['client'] }}@if($r['correction'])<br><small>Émetteur rectifié</small>@endif</td>
@foreach(['total','paid','fees','net'] as $key)<td style="padding:10px;border-bottom:1px solid #e2e8f0;white-space:nowrap">{{ $r[$key]===null ? 'À rapprocher' : $money($r[$key],$r['currency']) }}</td>@endforeach
<td style="padding:10px;border-bottom:1px solid #e2e8f0">{{ $r['status'] }}</td>
<td style="padding:10px;border-bottom:1px solid #e2e8f0"><a href="{{ $r['url'] }}" target="_blank" rel="noopener" style="font-weight:700;text-decoration:underline">Consulter</a></td>
</tr>@empty<tr><td colspan="8" style="padding:20px">Aucune facture émise.</td></tr>@endforelse</tbody>
</table></div>
</x-filament::section>
@if($account)
<x-filament::section heading="Compte Stripe · Solde réel">
@if(($account['business_type'] ?? null)==='company')
<p style="padding:12px;background:#fff7ed;color:#9a3412;border-radius:8px;margin-bottom:12px"><strong>Identité Stripe à finaliser.</strong> Ce compte est encore déclaré comme société. Le formulaire de passage à l’entreprise individuelle doit être validé dans <a href="https://dashboard.stripe.com/{{ $account['id'] }}/settings/taxation" target="_blank" rel="noopener" style="text-decoration:underline">les informations fiscales Stripe</a>.</p>
@endif
<p style="color:#64748b">Vérifié le {{ \Carbon\Carbon::parse($account['checked_at'])->timezone('Europe/Paris')->format('d/m/Y à H:i') }}. Ce solde tient compte de tous les mouvements du compte, y compris son historique.</p>
@if(\Carbon\Carbon::parse($account['checked_at'])->lt(now()->subMinutes(20)))<p style="color:#9a3412">Les données Stripe sont anciennes : rapprochement à actualiser.</p>@endif
<div style="display:flex;gap:28px;flex-wrap:wrap;margin-top:14px">
@foreach($account['available'] as $balance)<div><div>Disponible sur Stripe</div><strong style="font-size:24px">{{ $money($balance['amount_minor'],$balance['currency']) }}</strong></div>@endforeach
@foreach($account['pending'] as $balance)<div><div>En cours de traitement</div><strong style="font-size:24px">{{ $money($balance['amount_minor'],$balance['currency']) }}</strong></div>@endforeach
</div>
@foreach(collect($account['available'])->groupBy('currency') as $currency=>$balances)
@php
$actual=collect($account['available'])->where('currency',$currency)->sum('amount_minor')+collect($account['pending'])->where('currency',$currency)->sum('amount_minor');
$ledger=collect($account['transactions'])->where('currency',$currency)->sum('net_minor');
@endphp
<p style="margin-top:12px">{{ !empty($account['transactions_complete']) && $actual===$ledger ? 'Solde rapproché avec l’historique des mouvements Stripe.' : 'Rapprochement du solde à compléter.' }}</p>
@endforeach
<p style="margin-top:12px"><a href="https://dashboard.stripe.com/{{ $account['id'] }}/balance/overview" target="_blank" rel="noopener" style="text-decoration:underline;font-weight:700">Ouvrir Stripe</a></p>
</x-filament::section>
<x-filament::section heading="Virements Stripe vers la banque">
<p style="color:#64748b;margin-bottom:12px">Un virement déplace l’argent déjà encaissé. Il ne s’ajoute pas une seconde fois aux recettes.</p>
@forelse($account['payouts'] as $p)
<div style="padding:10px 0;border-bottom:1px solid #e2e8f0">{{ \Carbon\Carbon::createFromTimestamp($p['arrival_date'])->format('d/m/Y') }} · <strong>{{ $money($p['amount_minor'],$p['currency']) }}</strong> · {{ ['paid'=>'Viré','pending'=>'En attente','in_transit'=>'En cours','failed'=>'Échec','canceled'=>'Annulé'][$p['status']] ?? $p['status'] }}</div>
@empty<p>Aucun virement enregistré.</p>@endforelse
</x-filament::section>
<x-filament::section heading="Mouvements et autres frais Stripe">
<details><summary style="cursor:pointer;font-weight:700">Voir les mouvements du compte ({{ count($account['transactions']) }})</summary>
<div style="overflow-x:auto;margin-top:12px"><table style="width:100%;text-align:left;border-collapse:collapse">
<thead><tr>@foreach(['Date','Opération','Montant','Frais','Net'] as $h)<th style="padding:10px">{{ $h }}</th>@endforeach</tr></thead>
<tbody>@foreach($account['transactions'] as $t)<tr>
<td style="padding:10px;border-top:1px solid #e2e8f0">{{ \Carbon\Carbon::createFromTimestamp($t['created'])->format('d/m/Y') }}</td>
<td style="padding:10px;border-top:1px solid #e2e8f0">{{ ['charge'=>'Paiement','payment'=>'Paiement','payout'=>'Virement bancaire','stripe_fee'=>'Frais Stripe','refund'=>'Remboursement','adjustment'=>'Ajustement','stripe_fx_fee'=>'Frais de change'][$t['type']] ?? $t['type'] }}<br><small>{{ $t['description'] ?? '' }}</small></td>
@foreach(['amount_minor','fee_minor','net_minor'] as $key)<td style="padding:10px;border-top:1px solid #e2e8f0;white-space:nowrap">{{ $money($t[$key],$t['currency']) }}</td>@endforeach
</tr>@endforeach</tbody></table></div>
</details>
</x-filament::section>
@endif
@if($allRows->count()>$rows->count())<p style="color:#64748b">{{ $allRows->count()-$rows->count() }} pièce(s) historique(s) avec un ancien émetteur restent archivées et ne sont pas comptabilisées dans ce suivi.</p>@endif
</x-filament-panels::page>
