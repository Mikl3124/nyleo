<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::get('/', 'HomeController@index')->name('home');


//Admin
Route::post('/client-create', 'UserController@store')->name('client.store');
Route::post('/test-mail', 'UserController@testMail')->name('test.mail');
Route::get('/admin/clients/show/{id}', 'AdminController@showClient')->name('admin.client.show');
Route::get('/admin/clients/connect/{id}', 'AdminController@connectAsClient')->name('admin.client.connectAs');
Route::get('admin/messagerie/{id}', 'AdminController@showMessage')->name('admin.message.show');
Route::post('admin/message-store', 'AdminController@storeMessage')->name('admin.message.store');
Route::get('admin/messagerie-download/{message}', 'AdminController@download')->name('admin.messagerie.download');
Route::get('admin/documents/{id}', 'AdminController@showDocuments')->name('admin.documents.show');
Route::get('admin/upload/{id}', 'AdminController@showUploadPage')->name('admin.upload.page');
Route::post('/avantprojet-create)', 'ProjetController@avantprojetStore')->name('admin.avant-projet.store');

//User
Route::get('/profil/show/)', 'UserController@clientShow')->name('client.show');
Route::post('/profil/password/{id}', 'UserController@changePassword')->name('change.password');
Route::get('/profil/edit/)', 'UserController@clientEdit')->name('client.edit');
Route::post('/profil/client-update/{id}', 'UserController@clientUpdate')->name('client.update');

//Projet
Route::get('/projet/create/{id})', 'ProjetController@projetCreate')->name('projet.create');
Route::post('/projet-create)', 'ProjetController@projetStore')->name('projet.store');
Route::post('/projet-update)', 'ProjetController@projetUpdate')->name('projet.update');
Route::get('/projet/show/{id})', 'ProjetController@projetShow')->name('projet.show');
Route::get('/projet/edit/{id})', 'ProjetController@projetEdit')->name('projet.edit');
Route::get('/avant-projet/create/{id})', 'ProjetController@avantProjetCreate')->name('avant-projet.create');
Route::post('admin/avantProjet-store/', 'ProjetController@avantProjetStore')->name('admin.avantProjet.store');



//Avant Projet
Route::get('/avantprojet/show/{id})', 'ProjetController@avantprojetShow')->name('avantprojet.show');
Route::get('/avantprojet/delete/{id})', 'ProjetController@deleteAvantProjet')->name('avant-projet.delete');
Route::post('/payment-avant-projet/{quote}', 'PaiementController@payAvantProjet')->name('pay-avantprojet');
Route::get('/payment-success-avant-projet/{id}', 'PaiementController@successPayAvantProjet')->name('success-paiement-avantprojet');



//Quote
Route::get('/quote/show/{id})', 'QuoteController@quoteShow')->name('quote.show');
Route::post('admin/quote-store/', 'QuoteController@storeQuote')->name('admin.quote.store');
Route::get('admin/quote-create/{id}', 'QuoteController@createQuote')->name('devis.create');
Route::get('/quote-download/{id}', 'QuoteController@downloadQuote')->name('quote.download');
Route::get('/quote-accepted/{id}', 'QuoteController@acceptedQuote')->name('quote.accepted');
Route::get('/quote-delete/{quote_id}{projet_id}', 'QuoteController@deleteQuote')->name('quote.delete');


//Files
Route::get('/upload/', 'ProjetController@showUploadPage')->name('upload.page');
Route::post('/upload-files', 'ProjetController@uploadFile')->name('file.upload');
Route::get('documents/{id}', 'ProjetController@showDocuments')->name('documents.show');
Route::get('documents/delete/{id}', 'ProjetController@deleteDocument')->name('document.delete');

//Messagerie
Route::get('/messagerie', 'ConversationController@showMessage')->name('message.show');
Route::post('/message-store', 'ConversationController@storeMessage')->name('message.store');
Route::get('showMessageNotification/{message}/{notification}', 'ConversationController@showMessageNotification')->name('message.showMessageNotification');

//Stripe
Route::get('stripe', 'PaiementController@index')->name('payment.index');
Route::post('/payment-with-options', 'PaiementController@with_options')->name('pay-with-options');
Route::get('/payment-failed', 'PaiementController@failed');
Route::get('/payment-success/', 'PaiementController@successPay')->name('success-paiement');

//simple
Route::post('/paiement', 'DonateController@pay');

Route::get('/projets/{name}', 'DonateController@redirect');
