<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'index'])->name('marketplace');
Route::get('/acunar', [MarketplaceController::class, 'mint'])->middleware('market.user')->name('mint');
Route::get('/perfil', [MarketplaceController::class, 'profile'])->middleware('market.user')->name('profile');
Route::get('/pendientes', [MarketplaceController::class, 'roadmap'])->name('roadmap');
Route::post('/demo/usuario', [MarketplaceController::class, 'switchUser'])->name('demo.user');
Route::get('/cadena', [CollectionController::class, 'chain'])->name('chain');
Route::get('/colecciones/{collection}/imagen', [CollectionController::class, 'image'])->name('collections.image');
Route::get('/ingresar', [AuthController::class, 'loginForm'])->middleware('guest')->name('login');
Route::post('/ingresar', [AuthController::class, 'login'])->middleware(['guest', 'throttle:10,1'])->name('login.store');
Route::get('/registro', [AuthController::class, 'registerForm'])->middleware('guest')->name('register');
Route::post('/registro', [AuthController::class, 'register'])->middleware(['guest', 'throttle:5,1'])->name('register.store');
Route::post('/salir', [AuthController::class, 'logout'])->name('logout');
Route::get('/favoritos', [CollectionController::class, 'favorites'])->middleware('market.user')->name('favorites');

// Acciones de escritura del mercado: bloqueadas para cuentas suspendidas.
Route::middleware(['market.user', 'active', 'throttle:60,1'])->group(function () {
    Route::post('/favoritos/nfts/{nft}', [CollectionController::class, 'favoriteNft'])->name('favorites.nft');
    Route::post('/favoritos/colecciones/{collection}', [CollectionController::class, 'favoriteCollection'])->name('favorites.collection');
    Route::post('/acunar', [MarketplaceController::class, 'storeMint'])->name('mint.store');
    Route::post('/publicaciones/{listing}/comprar', [MarketplaceController::class, 'buy'])->name('listings.buy');
    Route::post('/publicaciones/{listing}/pujar', [MarketplaceController::class, 'bid'])->name('listings.bid');
    Route::post('/nfts/{nft}/vender', [MarketplaceController::class, 'sell'])->name('nfts.sell');
});

// Panel de administración: solo para cuentas con rol admin.
Route::middleware(['market.user', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::post('/colecciones/{collection}/visibilidad', [AdminController::class, 'toggleCollection'])->name('collections.toggle');
    Route::post('/usuarios/{user}/suspension', [AdminController::class, 'toggleUser'])->name('users.toggle');
});
