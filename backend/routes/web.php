<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| API Routes for Review Queue
|--------------------------------------------------------------------------
*/

Route::prefix('api')->group(function () {

    // Get list of items (queue + filters)
    Route::get('/items', [ItemController::class, 'index']);

    // Create a new item (submit to queue)
    Route::post('/items', [ItemController::class, 'store']);

    // Get a single item
    Route::get('/items/{item}', [ItemController::class, 'show']);

    // Review item (approve / reject)
    Route::post('/items/{item}/review', [ItemController::class, 'review']);

});