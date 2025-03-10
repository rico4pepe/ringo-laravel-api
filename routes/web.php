<?php

use Illuminate\Support\Facades\Route;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use App\Events\ImportProgressUpdated;


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
    return view('welcome');
});


Route::get('/test-websocket', function () {
    event(new ImportProgressUpdated(5, 3, 2)); // Simulating progress update

    return response()->json(['message' => 'Event fired']);
});

WebSocketsRouter::webSocket('/ws', \BeyondCode\LaravelWebSockets\WebSockets\WebSocketHandler::class);
