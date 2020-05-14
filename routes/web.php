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

Route::get('/', function () {
    if (Auth::user()){
      if(Auth::user()->role === 'admin'){
        return view('admin.home');
      } elseif (Auth::user()->role === 'client'){
        return view('home');
      }
    }

    return view('auth.login');

});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::get('/admin', 'HomeController@admin')->name('admin');

//Admin
Route::post('/client-create', 'UserController@store')->name('client.store');

//User
Route::post('/profil/password/{id}', 'UserController@changePassword')->name('change.password');
