<?php

namespace Tests\Feature;

use App\Support\Typography;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TypographyTest extends TestCase
{
    use RefreshDatabase;

    private const N = "\u{00A0}";

    public function test_punctuation_never_starts_a_line(): void
    {
        $n = self::N;
        $this->assertSame("la toiture{$n}: c'est ça{$n}? Oui{$n}! «{$n}Bien{$n}»{$n};", Typography::text("la toiture : c'est ça ? Oui ! « Bien » ;"));
        $this->assertSame("400{$n}€, 20{$n}m², 1{$n}500{$n}€ et 07{$n}85{$n}65{$n}91{$n}65", Typography::text('400 €, 20 m², 1 500 € et 07 85 65 91 65'));
        $this->assertSame("Rappel sous 48{$n}h, 10{$n}jours, 3{$n}mois. Le 2 hameaux reste.", Typography::text('Rappel sous 48 h, 10 jours, 3 mois. Le 2 hameaux reste.'));
        // Ni l'IBAN, ni les heures, ni les adresses web, ni les mots qui suivent un nombre.
        $this->assertSame('FR76 3000 6000 0112 3456 7890 189', Typography::text('FR76 3000 6000 0112 3456 7890 189'));
        $this->assertSame('10:30 https://nyleo.fr 2 habitants', Typography::text('10:30 https://nyleo.fr 2 habitants'));
    }

    public function test_html_keeps_scripts_attributes_and_form_values_untouched(): void
    {
        $html = '<p title="a : b">Note : ok</p><script>var x = a ? b : c;</script><textarea>x : y</textarea><style>a{b:c !important}</style>';
        $this->assertSame('<p title="a : b">Note'.self::N.': ok</p><script>var x = a ? b : c;</script><textarea>x : y</textarea><style>a{b:c !important}</style>', Typography::html($html));
    }

    public function test_pages_pdf_and_emails_use_non_breaking_spaces(): void
    {
        $page = $this->get('/projets/veranda')->assertOk()->getContent();
        $this->assertStringContainsString('la toiture'.self::N.': c', $page);
        $this->assertStringNotContainsString('la toiture : c', $page);
        // Les données structurées restent du JSON intact.
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $page, $m);
        foreach ($m[1] as $json) {
            $this->assertNotNull(json_decode($json, true));
        }
        $this->get('/espace/connexion')->assertOk();

    }

    public function test_outgoing_emails_are_fixed(): void
    {
        config(['mail.default' => 'array']);
        \Illuminate\Support\Facades\Mail::html('<p>Votre devis : prêt ?</p>', fn ($msg) => $msg->to('client@example.com')->subject('Devis : prêt'));
        $sent = app('mailer')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $this->assertStringContainsString('devis'.self::N.': prêt'.self::N.'?', $sent->getHtmlBody());
        $this->assertSame('Devis'.self::N.': prêt', $sent->getSubject());
    }
}
