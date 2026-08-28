<?php

use App\Http\Controllers\WordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::redirect('/', '/app');

Route::middleware([
    'auth',
    'can:publish_checksheet',
])->group(function (): void {
    // Route statis harus berada sebelum word/{order}.
    Route::get('word/bulk', [WordController::class, 'bulk'])
        ->name('word.bulk');

    Route::get('word/{order}', WordController::class)
        ->name('word');
});
