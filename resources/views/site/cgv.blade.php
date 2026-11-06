@extends('layouts.site')
@section('title', 'Conditions générales de vente')
@section('description', "Conditions générales de vente des plans de déclaration préalable et de permis de construire réalisés par Nyleo en Dordogne et en Gironde.")
@php
    $c = \App\Models\CompanySetting::current();
    $email = $c->email ?: 'mickael@nyleo.fr';
    $name = $c->company_name ?: 'M-GREEN';
@endphp
@section('content')
@include('site.partials.page-hero', ['crumbs' => 'Conditions générales de vente', 'kicker' => 'Informations', 'h1' => 'Conditions générales de vente', 'lead' => "Les règles qui s'appliquent aux plans et aux dossiers de déclaration préalable et de permis de construire réalisés par Nyleo."])
<section>
    <div class="wrap prose cgv">
        <p class="note" style="margin-top:0">En vigueur au {{ \App\Support\Legal::CGV_DATE }}.</p>

        <h2>1. Qui sommes-nous</h2>
        <p>Nyleo est une marque de <strong>{{ $name }}</strong>@if ($c->legal_form), {{ $c->legal_form }}@endif@if ($c->address), dont le siège est situé {{ $c->address }}, {{ $c->postal_code }} {{ $c->city }}@endif@if ($c->rcs), {{ $c->rcs }}@endif@if ($c->siret), SIRET {{ $c->siret }}@endif, représentée par Mickaël Delpech, son président (ci-après « Nyleo »).</p>
        <p>Les présentes conditions générales de vente (« CGV ») s'appliquent à toute commande passée auprès de Nyleo par une personne physique ou morale (ci-après « le Client »). Elles complètent le devis. En cas de différence, le devis l'emporte.</p>

        <h2>2. Ce que comprend la prestation</h2>
        <p>Nyleo réalise les plans et les pièces graphiques nécessaires à une demande d'autorisation d'urbanisme (déclaration préalable de travaux ou permis de construire) et, si le devis le prévoit, prépare le formulaire CERFA et dépose le dossier sur le guichet en ligne de la commune ou auprès de la mairie.</p>
        <p>Le contenu exact de la prestation (type de dossier, pièces fournies, dépôt inclus ou non) est celui décrit sur le devis. Toute demande qui n'y figure pas fait l'objet d'un devis complémentaire.</p>
        <p>Mickaël Delpech est dessinateur, et non architecte. Lorsque la loi impose le recours à un architecte (notamment lorsque la surface de plancher ou l'emprise au sol dépasse 150 m², ou lorsque le demandeur est une société), le projet est signé par un architecte partenaire. Son intervention est indiquée sur le devis.</p>
        <p>La prestation est achevée à la livraison des plans lorsque le dépôt n'est pas inclus, et au jour du dépôt du dossier lorsqu'il est inclus.</p>

        <h2>3. Devis et signature en ligne</h2>
        <p>Chaque prestation fait l'objet d'un devis gratuit, valable pendant la durée qui y est indiquée. Le devis est envoyé par e-mail et peut être consulté dans l'espace client.</p>
        <p>La commande est passée lorsque le Client signe le devis en ligne : il saisit son nom, sa date et son lieu de naissance (nécessaires au CERFA), coche la case « Bon pour accord » et valide. La signature est enregistrée avec la date, l'heure et l'adresse de connexion, et une copie du devis signé reste disponible dans l'espace client. Le Client peut aussi renvoyer le devis signé par courrier ou par e-mail. La signature du devis vaut acceptation des présentes CGV.</p>

        <h2>4. Montant, acompte et paiement</h2>
        <p>Le montant de la prestation est celui indiqué sur le devis signé. Nyleo n'est pas assujettie à la TVA (article 293 B du Code général des impôts), sauf mention contraire sur le devis.</p>
        <p>Lorsque le devis le prévoit, un acompte est demandé à la signature. Le travail commence à réception de cet acompte. Le solde est facturé à la livraison des plans.</p>
        <p>Les factures sont payables dans un délai de <strong>10 jours</strong> à compter de leur date d'émission, par virement bancaire ou par chèque. Chaque facture comporte un QR code : il suffit de le scanner avec l'application de sa banque pour préremplir le virement (bénéficiaire, IBAN, montant et référence). Aucun paiement par carte bancaire n'est proposé.</p>
        <p>Le dossier n'est déposé en mairie qu'après réception du paiement complet.</p>
        <p>En cas de retard de paiement, des pénalités égales à trois fois le taux d'intérêt légal sont exigibles. Pour un Client professionnel, s'y ajoute l'indemnité forfaitaire pour frais de recouvrement prévue à l'article D441-5 du Code de commerce. Aucun escompte n'est accordé pour paiement anticipé.</p>

        <h2>5. Informations et pièces fournies par le Client</h2>
        <p>Les plans sont établis d'après les informations et des documents transmis par le Client : photos du terrain et de la construction existante, dimensions, références cadastrales, description du projet, choix des matériaux et des couleurs, et pour le CERFA son identité, sa date et son lieu de naissance. Le Client peut les déposer directement dans son espace client.</p>
        <p>Le Client garantit l'exactitude de ces informations et qu'il a le droit de réaliser le projet sur le terrain concerné (propriétaire ou autorisé par le propriétaire). Nyleo ne réalise pas de relevé de géomètre ni de bornage. Une information erronée, incomplète ou transmise en retard peut entraîner la modification des plans, un retard ou un refus, sans que la responsabilité de Nyleo soit engagée.</p>

        <h2>6. Délais</h2>
        <p>Les plans sont réalisés en général dans un délai de 20 jours ouvrés à compter de la signature du devis et de la réception de toutes les pièces nécessaires. Ce délai est indicatif. Il est prolongé d'autant en cas de retard dans la transmission des pièces, de dossier particulièrement complexe ou de circonstance extérieure à Nyleo. Un dépassement ne peut donner lieu à indemnisation, sauf faute prouvée de Nyleo.</p>
        <p>Les délais d'instruction de la mairie ne dépendent pas de Nyleo.</p>

        <h2>7. Validation des plans et modifications</h2>
        <p>Les plans sont mis à disposition dans l'espace client. Le Client peut les valider ou demander des modifications en un clic. Sans réponse de sa part dans un délai de 7 jours, les plans sont réputés acceptés.</p>
        <p>Des ajustements raisonnables sont inclus dans la prestation, dans la limite du projet décrit sur le devis. Un changement important du projet (dimensions, implantation, nouvelle construction, autre type de dossier) fait l'objet d'un devis complémentaire, envoyé avant tout travail.</p>

        <h2>8. Demandes de pièces complémentaires de la mairie</h2>
        <p>Pendant l'instruction, la mairie peut demander des pièces complémentaires ou des corrections. Lorsque la demande porte sur les plans réalisés par Nyleo pour le projet décrit au devis, Nyleo les complète ou les corrige sans frais supplémentaires et, si le dépôt était inclus, transmet les compléments à la mairie.</p>
        <p>Les pièces qui ne relèvent pas des plans restent à fournir par le Client (par exemple une attestation, une étude de sol, l'avis du service d'assainissement non collectif, une étude thermique ou un accord de copropriété). Si la demande impose de modifier le projet lui-même, un devis complémentaire est proposé.</p>
        <p>Le Client transmet à Nyleo, dès sa réception, tout courrier de la mairie concernant son dossier, pour que la réponse puisse être faite dans le délai fixé par la mairie.</p>

        <h2>9. Pas de garantie d'obtention de l'autorisation</h2>
        <p>Nyleo s'engage à réaliser des plans soignés et conformes aux pièces demandées pour le type de dossier choisi. Il s'agit d'une obligation de moyens : la décision appartient à la mairie, et Nyleo ne peut pas garantir l'obtention de la déclaration préalable ou du permis de construire, ni l'absence d'opposition, de prescriptions ou de recours d'un tiers.</p>
        <p>Un refus ou une opposition ne donne pas lieu à remboursement de la prestation réalisée. Si le Client souhaite déposer une nouvelle demande, Nyleo lui propose un devis adapté.</p>
        <p>Après le dépôt, le Client suit l'avancement de son dossier auprès de la mairie. Lorsque Nyleo reçoit une information sur le dossier (récépissé, demande de pièces, décision), elle la transmet au Client.</p>

        <h2>10. Droit de rétractation</h2>
        <p>Lorsque le Client est un particulier et que le contrat est conclu à distance (par téléphone, par e-mail ou par signature en ligne), il dispose d'un délai de <strong>14 jours</strong> à compter de la signature du devis pour se rétracter, sans avoir à se justifier (articles L221-18 et suivants du Code de la consommation).</p>
        <p>Pour se rétracter, le Client informe Nyleo de sa décision par une déclaration claire, par e-mail à <a href="mailto:{{ $email }}">{{ $email }}</a> ou par courrier, avant la fin de ce délai. Il peut utiliser le modèle de formulaire ci-dessous. Nyleo rembourse alors les sommes versées dans les 14 jours, par virement.</p>
        <p>Le Client peut demander que le travail commence avant la fin du délai de rétractation. Dans ce cas, s'il se rétracte ensuite, il règle une somme proportionnelle au travail déjà réalisé (article L221-25). S'il a demandé expressément que la prestation soit entièrement réalisée avant la fin du délai et reconnu qu'il perdait alors son droit de rétractation, ce droit ne peut plus être exercé une fois les plans livrés (article L221-28).</p>
        <div style="margin:18px 0;padding:18px 20px;border:1px solid var(--line, #dfe3eb);border-radius:14px;background:#fff">
            <p style="margin-top:0"><strong>Modèle de formulaire de rétractation</strong><br>
            <span class="note">À compléter et renvoyer uniquement si vous souhaitez vous rétracter.</span></p>
            <p style="margin-bottom:0">À l'attention de {{ $name }} (Nyleo)@if ($c->address), {{ $c->address }}, {{ $c->postal_code }} {{ $c->city }}@endif, {{ $email }} :<br>
            Je vous notifie par la présente ma rétractation du contrat portant sur la prestation ci-dessous.<br>
            Numéro du devis et date de signature :<br>
            Nom et adresse du Client :<br>
            Date et signature (en cas d'envoi papier) :</p>
        </div>

        <h2>11. Annulation par Nyleo</h2>
        <p>Nyleo peut mettre fin à une commande pour un motif légitime : projet impossible à réaliser techniquement ou réglementairement, dossier inexploitable, absence de réponse ou de collaboration du Client, ou comportement irrespectueux ou menaçant. Le Client en est informé par e-mail. Les sommes correspondant au travail non réalisé lui sont remboursées dans les 14 jours.</p>

        <h2>12. Propriété des plans</h2>
        <p>Les plans et documents réalisés par Nyleo restent sa propriété intellectuelle. Une fois la prestation entièrement payée, le Client peut les utiliser librement pour son projet, sur le terrain concerné : dépôt du dossier, demande de devis aux entreprises, réalisation des travaux. Toute autre utilisation (autre terrain, revente, diffusion publique, modification par un tiers présentée comme un plan de Nyleo) nécessite l'accord écrit de Nyleo.</p>
        <p>Les plans ne sont pas des plans d'exécution : ils ne remplacent ni une étude de structure, ni une étude de sol, ni les plans des entreprises qui réalisent les travaux.</p>

        <h2>13. Responsabilité</h2>
        <p>La responsabilité de Nyleo ne peut pas être engagée en cas d'information inexacte, incomplète ou tardive fournie par le Client, de refus ou d'opposition de la mairie, de travaux réalisés différemment des plans autorisés, ou de travaux commencés avant l'obtention de l'autorisation et l'expiration des délais de recours. Le Client reste seul responsable de la conformité des travaux réalisés.</p>
        <p>En tout état de cause, la responsabilité de Nyleo est limitée au montant de la commande concernée, sauf faute lourde ou intentionnelle. Cette limite ne s'applique pas aux droits que la loi garantit au consommateur.</p>

        <h2>14. Force majeure</h2>
        <p>Les obligations de Nyleo sont suspendues en cas de force majeure au sens de l'article 1218 du Code civil. Si l'événement dure plus de 90 jours, chaque partie peut mettre fin au contrat sans indemnité.</p>

        <h2>15. Données personnelles</h2>
        <p>Nyleo traite les données du Client (identité, coordonnées, adresse et références cadastrales du terrain, photos, et pour le CERFA sa date et son lieu de naissance) uniquement pour établir le devis, réaliser les plans, déposer le dossier, facturer et informer le Client de l'avancement. La date et le lieu de naissance sont demandés parce qu'ils figurent obligatoirement sur le formulaire CERFA. Ces données sont transmises à la mairie et aux services instructeurs dans le cadre du dépôt, et à l'architecte partenaire lorsqu'il intervient. Elles ne sont jamais revendues.</p>
        <p>Les factures sont conservées 10 ans, les autres données 3 ans après le dernier échange. Le Client peut demander l'accès, la rectification ou la suppression de ses données en écrivant à <a href="mailto:{{ $email }}">{{ $email }}</a>, et adresser une réclamation à la CNIL. Le détail figure sur la page <a href="{{ route('privacy') }}">Confidentialité</a>.</p>

        <h2>16. Réclamation et médiation</h2>
        <p>Pour toute réclamation, le Client écrit d'abord à <a href="mailto:{{ $email }}">{{ $email }}</a>. Nous cherchons ensemble une solution amiable.</p>
        <p>À défaut d'accord dans un délai de deux mois après cette réclamation écrite, le Client consommateur peut saisir gratuitement le médiateur de la consommation :</p>
        <ul>
            <li><strong>{{ \App\Support\Legal::MEDIATOR_NAME }}</strong></li>
            <li>Site : <a href="{{ \App\Support\Legal::MEDIATOR_URL }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', \App\Support\Legal::MEDIATOR_URL) }}</a></li>
            <li>E-mail : <a href="mailto:{{ \App\Support\Legal::MEDIATOR_EMAIL }}">{{ \App\Support\Legal::MEDIATOR_EMAIL }}</a></li>
        </ul>

        <h2>17. Droit applicable et litiges</h2>
        <p>Les présentes CGV sont soumises au droit français. Pour un Client professionnel, tout litige relève de la compétence exclusive du tribunal de commerce de Périgueux. Pour un Client consommateur, le tribunal compétent est désigné selon les règles du Code de la consommation.</p>
        <p>Si une clause des présentes CGV était déclarée nulle, les autres resteraient applicables.</p>

        <h2>Contact</h2>
        <p>{{ $name }} (Nyleo)@if ($c->address)<br>{{ $c->address }}, {{ $c->postal_code }} {{ $c->city }}@endif
            @if ($c->phone)<br>Téléphone : {{ $c->phone }}@endif
            <br>E-mail : <a href="mailto:{{ $email }}">{{ $email }}</a></p>
    </div>
</section>
@endsection
