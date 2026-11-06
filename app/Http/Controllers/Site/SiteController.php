<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Support\SiteContent;
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
        return view('site.dp', ['price' => $this->startingPrices()['DP'] ?? null]);
    }

    public function pc()
    {
        return view('site.pc', ['price' => $this->startingPrices()['PC'] ?? null]);
    }

    public function pricing()
    {
        $items = CatalogItem::query()->where('active', true)->orderBy('position')->get()->groupBy('category');

        return view('site.pricing', ['groups' => $items]);
    }

    public function area(string $department)
    {
        abort_unless(isset(SiteContent::DEPARTMENTS[$department]), 404);

        return view('site.area', ['slug' => $department, 'dep' => SiteContent::DEPARTMENTS[$department]]);
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
        $urls = collect(['home', 'site.dp', 'site.pc', 'site.pricing', 'site.faq', 'site.contact', 'legal', 'privacy'])
            ->map(fn ($r) => route($r))
            ->merge(collect(array_keys(SiteContent::DEPARTMENTS))->map(fn ($d) => route('site.area', $d)));

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
