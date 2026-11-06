<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Geo;
use App\Support\ProjectGuides;
use App\Support\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\Response;

/** Pages du site vitrine. */
class SiteController extends Controller
{
    public function home()
    {
        return view('site.home');
    }

    public function dp()
    {
        return view('site.dp');
    }

    public function pc()
    {
        return view('site.pc');
    }

    public function projects()
    {
        return view('site.projects');
    }

    public function project(string $project)
    {
        $guide = ProjectGuides::find($project);
        abort_unless($guide, 404);

        return view('site.project', ['guide' => $guide]);
    }

    public function areas()
    {
        return view('site.areas');
    }

    public function town(string $department, string $town)
    {
        $commune = Geo::find($department, $town);
        abort_unless($commune && $commune['page'], 404);

        return view('site.town', ['town' => $commune]);
    }

    /** Recherche de commune : sa page si elle existe, sinon le formulaire de devis prérempli. */
    public function find(Request $request)
    {
        $raw = is_string($request->query('q')) ? mb_substr($request->query('q'), 0, 120) : '';
        $cp = preg_match('/\((\d{5})\)/', $raw, $m) ? $m[1] : null;
        $dep = in_array($request->query('dep'), ['dordogne', 'gironde'], true) ? $request->query('dep') : null;
        $q = self::searchKey(preg_replace('/\(.*$/s', '', $raw));

        $lookup = function (?string $dep) use ($q, $cp) {
            $communes = Geo::communes($dep);
            $exact = $q === '' ? collect() : $communes->filter(fn ($c) => self::searchKey($c['name']) === $q)->values();
            $commune = ($cp ? $exact->first(fn ($c) => in_array($cp, $c['cp'], true)) : null)
                ?? ($exact->count() === 1 ? $exact->first() : null)
                ?? ($exact->isEmpty() && strlen($q) >= 3 ? $communes->sortByDesc('pop')->first(fn ($c) => str_starts_with(self::searchKey($c['name']), $q)) : null);

            return [$commune, $exact];
        };
        // D'abord dans le département de la page, puis dans les deux.
        [$commune, $exact] = $lookup($dep);
        if (! $commune && $exact->isEmpty() && $dep) {
            [$commune, $exact] = $lookup(null);
        }

        if (! $commune && $exact->count() > 1) {
            // Même nom en Dordogne et en Gironde, sans code postal pour les départager : on garde le nom seul.
            return redirect()->route('site.contact', ['commune' => $exact->first()['name']])
                ->with('commune_status', 'J\'interviens '.Geo::a($exact->first()['name']).' : décrivez-moi votre projet ci-dessous.');
        }
        if ($commune && $commune['page']) {
            return redirect(Geo::url($commune));
        }
        if ($commune) {
            return redirect()->route('site.contact', ['commune' => $commune['name'].' ('.($commune['cp'][0] ?? '').')'])
                ->with('commune_status', 'J\'interviens '.Geo::a($commune['name']).' : décrivez-moi votre projet ci-dessous.');
        }

        return redirect()->route('site.areas')->with('status', 'Commune introuvable en Dordogne ou en Gironde. Vérifiez l\'orthographe, ou appelez-moi pour en parler.');
    }

    /**
     * Clé de recherche tolérante : « St Émilion », « saint-emilion », « Villenave d Ornon »
     * et « Villenave-d'Ornon » donnent la même clé (accents, apostrophes et tirets ignorés).
     */
    private static function searchKey(string $name): string
    {
        $slug = Str::slug(str_replace(["'", '’'], ' ', $name));
        $slug = preg_replace(['/^st-/', '/-st-/', '/^ste-/', '/-ste-/'], ['saint-', '-saint-', 'sainte-', '-sainte-'], $slug);

        return str_replace('-', '', $slug);
    }

    public function about()
    {
        return view('site.about');
    }

    public function area(string $department)
    {
        abort_unless(isset(SiteContent::DEPARTMENTS[$department]), 404);

        return view('site.area', ['slug' => $department, 'dep' => Geo::department($department), 'content' => SiteContent::DEPARTMENTS[$department]]);
    }

    public function faq()
    {
        return view('site.faq');
    }

    public function contact(Request $request)
    {
        $demarche = $request->query('demarche');

        return view('site.contact', [
            'procedure' => in_array($demarche, ['DP', 'PC'], true) ? $demarche : 'AUTRE',
            'prefillTown' => is_string($request->query('commune')) ? mb_substr($request->query('commune'), 0, 120) : '',
        ]);
    }

    /** Communes des deux départements pour l'autocomplétion du formulaire de devis. */
    public function communes()
    {
        $names = Geo::communes()->sortBy('name')->map(fn ($c) => $c['name'].' ('.($c['cp'][0] ?? '').')')->values();

        return response()->json($names, 200, ['Cache-Control' => 'public, max-age=86400', 'X-Robots-Tag' => 'noindex'], JSON_UNESCAPED_UNICODE);
    }

    public function sitemap(): Response
    {
        $urls = collect(['home', 'site.dp', 'site.pc', 'site.projects', 'site.areas', 'site.about', 'site.faq', 'site.contact', 'legal', 'privacy', 'cgv'])
            ->map(fn ($r) => route($r))
            ->merge(ProjectGuides::all()->map(fn ($g) => route('site.project', $g['slug'])))
            ->merge(collect(array_keys(SiteContent::DEPARTMENTS))->map(fn ($d) => route('site.area', $d)))
            ->merge(Geo::pages()->map(fn ($c) => Geo::url($c)));

        return response()->view('site.sitemap', ['urls' => $urls, 'townUpdated' => \App\Support\TownFacts::updated(), 'townPrefix' => route('site.areas').'/'])->header('Content-Type', 'application/xml');
    }

}
