<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'index'])->name('marketplace');
Route::get('/acunar', [MarketplaceController::class, 'mint'])->name('mint');
Route::get('/perfil', [MarketplaceController::class, 'profile'])->name('profile');
Route::get('/pendientes', [MarketplaceController::class, 'roadmap'])->name('roadmap');
Route::post('/demo/usuario', [MarketplaceController::class, 'switchUser'])->name('demo.user');

// Acciones de escritura del mercado: bloqueadas para cuentas suspendidas.
Route::middleware('active')->group(function () {
    Route::post('/acunar', [MarketplaceController::class, 'storeMint'])->name('mint.store');
    Route::post('/publicaciones/{listing}/comprar', [MarketplaceController::class, 'buy'])->name('listings.buy');
    Route::post('/publicaciones/{listing}/pujar', [MarketplaceController::class, 'bid'])->name('listings.bid');
    Route::post('/nfts/{nft}/vender', [MarketplaceController::class, 'sell'])->name('nfts.sell');
});

// Panel de administración: solo para el usuario demo con rol admin.
Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::post('/colecciones/{collection}/visibilidad', [AdminController::class, 'toggleCollection'])->name('collections.toggle');
    Route::post('/usuarios/{user}/suspension', [AdminController::class, 'toggleUser'])->name('users.toggle');
});
