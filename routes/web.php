<?php

use App\Http\Controllers\Admin\DownloadController;
use App\Http\Controllers\Portal;
use App\Http\Controllers\Site\SiteController;
use Illuminate\Support\Facades\Route;

// Site vitrine
Route::controller(SiteController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/declaration-prealable', 'dp')->name('site.dp');
    Route::get('/permis-de-construire', 'pc')->name('site.pc');
    Route::get('/tarifs', 'pricing')->name('site.pricing');
    Route::get('/secteurs', 'areas')->name('site.areas');
    Route::get('/secteurs/recherche', 'find')->name('site.find');
    Route::get('/secteurs/{department}', 'area')->whereIn('department', ['dordogne', 'gironde'])->name('site.area');
    Route::get('/secteurs/{department}/{town}', 'town')->whereIn('department', ['dordogne', 'gironde'])->where('town', '[a-z0-9-]+')->name('site.town');
    Route::get('/qui-suis-je', 'about')->name('site.about');
    Route::get('/questions-frequentes', 'faq')->name('site.faq');
    Route::get('/contact', 'contact')->name('site.contact');
    Route::get('/sitemap.xml', 'sitemap')->name('sitemap');
});
Route::view('/mentions-legales', 'site.legal')->name('legal');
Route::view('/confidentialite', 'site.privacy')->name('privacy');
Route::post('/demande', [Portal\RequestController::class, 'store'])->middleware('throttle:5,10')->name('site.request');

// Téléchargements du back-office (protégés par la connexion admin)
Route::middleware('auth:web')->prefix('admin-fichiers')->name('admin.')->group(function () {
    Route::get('devis/{quote}.pdf', [DownloadController::class, 'quote'])->name('quotes.pdf');
    Route::get('factures/{invoice}.pdf', [DownloadController::class, 'invoice'])->name('invoices.pdf');
    Route::get('documents/{document}', [DownloadController::class, 'document'])->name('documents.download');
});

// Espace client
Route::prefix('espace')->name('portal.')->group(function () {
    Route::middleware('guest:client')->group(function () {
        Route::get('connexion', [Portal\AuthController::class, 'showLogin'])->name('login');
        Route::post('connexion', [Portal\AuthController::class, 'sendLink'])->middleware('throttle:5,10')->name('login.send');
    });
    Route::get('acces/{client}', [Portal\AuthController::class, 'magic'])->middleware('signed')->name('magic');

    Route::middleware('auth:client')->group(function () {
        Route::post('deconnexion', [Portal\AuthController::class, 'logout'])->name('logout');
        Route::get('/', [Portal\DossierController::class, 'index'])->name('home');
        Route::get('dossiers/{dossier}', [Portal\DossierController::class, 'show'])->name('dossiers.show');
        Route::post('dossiers/{dossier}/photos', [Portal\DossierController::class, 'upload'])->name('dossiers.upload');
        Route::post('dossiers/{dossier}/infos', [Portal\DossierController::class, 'updateInfo'])->name('dossiers.info');
        Route::get('documents/{document}', [Portal\DocumentController::class, 'download'])->name('documents.download');
        Route::post('documents/{document}/valider', [Portal\DocumentController::class, 'validatePlan'])->name('documents.validate');
        Route::post('documents/{document}/modifier', [Portal\DocumentController::class, 'requestChange'])->name('documents.change');
        Route::get('devis/{quote}', [Portal\QuoteController::class, 'show'])->name('quotes.show');
        Route::get('devis/{quote}/pdf', [Portal\QuoteController::class, 'pdf'])->name('quotes.pdf');
        Route::post('devis/{quote}/signer', [Portal\QuoteController::class, 'sign'])->name('quotes.sign');
        Route::get('factures/{invoice}/pdf', [Portal\InvoiceController::class, 'pdf'])->name('invoices.pdf');
    });
});
