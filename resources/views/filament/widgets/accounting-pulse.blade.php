<x-filament-widgets::widget>
<x-filament::section heading="Mon activité · Nyleo et Permis-construire">
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px">
<div><p>Factures actives</p><strong style="font-size:28px">{{ number_format($factured/100,2,',',' ') }} €</strong><p>{{ $invoiceCount }} facture(s), tous sites confondus</p><a style="text-decoration:underline;font-weight:700" href="{{ \App\Filament\Resources\Invoices\InvoiceResource::getUrl() }}">Voir la liste unique</a></div>
<div><p>Préparation URSSAF · {{ $period }}</p><strong style="font-size:28px">{{ number_format($preparation['base_minor']/100,2,',',' ') }} €</strong><p>CA HT encaissé enregistré, avant frais.</p><a style="text-decoration:underline;font-weight:700" href="{{ \App\Filament\Pages\Accounting::getUrl() }}">Compléter et vérifier ce mois</a></div>
<div style="padding:14px;background:#fff7ed;border-radius:10px"><strong>Avant de déclarer</strong><p>Ajoutez les recettes de vos autres activités et vérifiez la rubrique BIC/BNC. Les frais Stripe ne diminuent pas le chiffre d’affaires déclaré.</p></div>
</div>
</x-filament::section>
</x-filament-widgets::widget>
