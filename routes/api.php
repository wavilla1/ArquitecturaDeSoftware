<?php

use App\Http\Controllers\Api\NftCatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/nfts', NftCatalogController::class)
    ->middleware('throttle:60,1')
    ->name('api.nfts.index');
