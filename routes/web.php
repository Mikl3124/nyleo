<?php

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
Route::get('/home', 'HomeController@index')->name('home');


//Admin
Route::post('/client-create', 'UserController@store')->name('client.store');
Route::post('/test-mail', 'UserController@testMail')->name('test.mail');

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
Route::get('/avant-projet/create/{id})', 'ProjetController@avantProjetCreate')->name('avantProjet.create');
Route::post('admin/avantProjet-store/', 'ProjetController@avantProjetStore')->name('admin.avantProjet.store');


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

//Admin
Route::get('/admin/clients/show/{id}', 'AdminController@showClient')->name('admin.client.show');
<<<<<<< HEAD
Route::get('/admin/clients/connectAs/{id}', 'AdminController@connectAsClient')->name('admin.client.connectAs');
=======
Route::get('/admin/clients/connect/{id}', 'AdminController@connectAsClient')->name('admin.client.connectAs');
>>>>>>> 30abd8a0d8f9df4e0a8643c554a7e5ea448884e1
Route::get('admin/messagerie/{id}', 'AdminController@showMessage')->name('admin.message.show');
Route::post('admin/message-store', 'AdminController@storeMessage')->name('admin.message.store');
Route::get('admin/messagerie-download/{message}', 'AdminController@download')->name('admin.messagerie.download');
Route::get('admin/documents/{id}', 'AdminController@showDocuments')->name('admin.documents.show');
Route::get('admin/upload/{id}', 'AdminController@showUploadPage')->name('admin.upload.page');



//Stripe
Route::get('stripe', 'PaiementController@index')->name('payment.index');
Route::get('/payment-success/{quote}', 'PaiementController@success');
Route::get('/payment-failed', 'PaiementController@failed');

