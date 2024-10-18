<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\BVNController;
use App\Http\Controllers\ValidateSwiftController;
use App\Http\Controllers\AuthController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});   

Route::post('/upload-csv', [CsvImportController::class, 'upload'])->name('csv.upload');
Route::post('bvn/verify/{userId}', [BVNController::class, 'verify'])->name('bvn.verify');

Route::post('/swift/validate', [ValidateSwiftController::class, 'validateSwift'])->name('swift.validate');
// Handle login form submission
Route::post('/login', [AuthController::class, 'login'])->name('login.post');