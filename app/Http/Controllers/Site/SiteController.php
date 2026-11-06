<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Support\Geo;
use App\Support\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\Response;

/** Pages du site vitrine. */
class SiteController extends Controller
{
    public function home()
    {
        return view('site.home', ['prices' => $this->startingPrices()]);
    }

    public function dp()
    {
        $prices = $this->startingPrices();

        return view('site.dp', ['price' => $prices['DP'] ?? null, 'prices' => $prices]);
    }

    public function pc()
    {
        $prices = $this->startingPrices();

        return view('site.pc', ['price' => $prices['PC'] ?? null, 'prices' => $prices]);
    }

    public function areas()
    {
        return view('site.areas');
    }

    public function town(string $department, string $town)
    {
        $commune = Geo::find($department, $town);
        abort_unless($commune && $commune['page'], 404);

        return view('site.town', ['town' => $commune, 'prices' => $this->startingPrices()]);
    }

    /** Recherche de commune : sa page si elle existe, sinon le formulaire de devis prérempli. */
    public function find(Request $request)
    {
        // « St Émilion », « saint-emilion (33330) » ou « SAINT EMILION » trouvent la même commune.
        $key = fn (string $name) => preg_replace(['/^st-/', '/-st-/', '/^ste-/', '/-ste-/'], ['saint-', '-saint-', 'sainte-', '-sainte-'], Str::slug($name));
        $q = $key(preg_replace('/\(.*$/', '', (string) $request->query('q')));
        $commune = $q === '' ? null : (Geo::communes()->first(fn ($c) => $key($c['name']) === $q)
            ?? (strlen($q) >= 3 ? Geo::communes()->sortByDesc('pop')->first(fn ($c) => str_starts_with($key($c['name']), $q)) : null));

        if ($commune && $commune['page']) {
            return redirect(Geo::url($commune));
        }
        if ($commune) {
            return redirect()->route('site.contact', ['commune' => $commune['name'].' ('.($commune['cp'][0] ?? '').')'])
                ->with('status', "J'interviens à {$commune['name']} : décrivez-moi votre projet ci-dessous.");
        }

        return redirect()->route('site.areas')->with('status', 'Commune introuvable en Dordogne ou en Gironde. Appelez-moi pour en parler.');
    }

    public function about()
    {
        return view('site.about');
    }

    public function pricing()
    {
        $items = CatalogItem::query()->where('active', true)->orderBy('position')->get()->groupBy('category');

        return view('site.pricing', ['groups' => $items]);
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

    public function contact()
    {
        return view('site.contact');
    }

    public function sitemap(): Response
    {
        $urls = collect(['home', 'site.dp', 'site.pc', 'site.pricing', 'site.areas', 'site.about', 'site.faq', 'site.contact', 'legal', 'privacy'])
            ->map(fn ($r) => route($r))
            ->merge(collect(array_keys(SiteContent::DEPARTMENTS))->map(fn ($d) => route('site.area', $d)))
            ->merge(Geo::pages()->map(fn ($c) => Geo::url($c)));

        return response()->view('site.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }

    /** Prix « à partir de » par catégorie, d'après le catalogue. */
    private function startingPrices(): array
    {
        return CatalogItem::query()->where('active', true)->where('unit_price', '>', 0)
            ->whereIn('category', ['DP', 'PC'])
            ->selectRaw('category, MIN(unit_price) as price')->groupBy('category')
            ->pluck('price', 'category')->map(fn ($p) => (float) $p)->all();
    }
}
