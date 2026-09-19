<?php

use App\Http\Controllers\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'index'])->name('marketplace');
Route::get('/acunar', [MarketplaceController::class, 'mint'])->name('mint');
Route::post('/acunar', [MarketplaceController::class, 'storeMint'])->name('mint.store');
Route::post('/publicaciones/{listing}/comprar', [MarketplaceController::class, 'buy'])->name('listings.buy');
Route::post('/publicaciones/{listing}/pujar', [MarketplaceController::class, 'bid'])->name('listings.bid');
Route::get('/perfil', [MarketplaceController::class, 'profile'])->name('profile');
Route::post('/nfts/{nft}/vender', [MarketplaceController::class, 'sell'])->name('nfts.sell');
Route::post('/demo/usuario', [MarketplaceController::class, 'switchUser'])->name('demo.user');
Route::get('/pendientes', [MarketplaceController::class, 'roadmap'])->name('roadmap');
