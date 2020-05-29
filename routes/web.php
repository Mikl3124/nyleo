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


//Admin
Route::post('/client-create', 'UserController@store')->name('client.store');
Route::post('/test-mail', 'UserController@testMail')->name('test.mail');

//User
Route::get('/profil/show/)', 'UserController@clientShow')->name('client.show');
Route::post('/profil/password/{id}', 'UserController@changePassword')->name('change.password');
Route::get('/profil/edit/)', 'UserController@clientEdit')->name('client.edit');
Route::post('/profil/client-update/{id}', 'UserController@clientUpdate')->name('client.update');

//Projet
Route::get('/projet/edit/{id})', 'ProjetController@projetEdit')->name('projet.edit');
Route::post('/projet-create)', 'ProjetController@projetCreate')->name('projet.create');

//Files
Route::get('/upload', 'ProjetController@showUploadPage')->name('upload.page');
Route::post('/upload-files', 'ProjetController@uploadFile')->name('file.upload');

//Messagerie
Route::get('/messagerie', 'ConversationController@showMessage')->name('message.show');
Route::post('/message-store', 'ConversationController@storeMessage')->name('message.store');
Route::get('showMessageNotification/{message}/{notification}', 'ConversationController@showMessageNotification')->name('message.showMessageNotification');

//Admin
Route::get('/admin/clients/show/{id}', 'AdminController@showClient')->name('admin.client.show');
Route::get('admin/messagerie/{id}', 'AdminController@showMessage')->name('admin.message.show');
Route::post('admin/message-store', 'AdminController@storeMessage')->name('admin.message.store');
Route::get('admin/messagerie-download/{message}', 'AdminController@download')->name('admin.messagerie.download');
Route::get('admin/documents/{id}', 'AdminController@showDocuments')->name('admin.documents.show');
