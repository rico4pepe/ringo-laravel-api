<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\BVNController;
use App\Http\Controllers\ValidateSwiftController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ElectricityPaymentController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ExcelCsvImportController;
use App\Http\Controllers\SmsWebhookController;
//use App\Http\Controllers\Testing;
use App\Http\Controllers\GetSmsReportByDate;
use App\Http\Controllers\DynamicSenderId;
use App\Http\Controllers\MessageStatController;
use Illuminate\Support\Facades\Log;

//use Illuminate\Support\Facades\Mail;
//use App\Mail\OtpMail; // Adjust this according to your mail class
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

Route::post('/electrical-payment', [ElectricityPaymentController::class, 'makeElectricityPayment'])->name('electric.payment');


Route::post('/signin', [LoginController::class, 'signin'])->name('signin.post');

Route::post('/verifyOtp', [LoginController::class, 'verifyOtp'])->name('verifyOtp.verify');
Route::post('/resendOtp', [LoginController::class, 'resendOtp'])->name('resendOtp.resend');
Route::post('/import', action: [ExcelCsvImportController::class, 'import'])->name('import.excel');
Route::post('/sendSingleSms', [ExcelCsvImportController::class, 'sendSingleSms'])->name('sendSingleSms.send');
Route::get('/handlewebook', [SmsWebhookController::class, 'handleWebhook'])->name('handlewebook.send');
// In routes/api.php - Add this temporary debugging route
// Route::any('/handlewebook', function(Request $request) {
//     Log::info('Webhook Endpoint Debug', [
//         'method' => $request->method(),
//         'url' => $request->fullUrl(),
//         'headers' => $request->headers->all(),
//         'ip' => $request->ip(),
//         'user_agent' => $request->userAgent(),
//         'request_data' => $request->all()
//     ]);

//     if ($request->method() !== 'POST') {
//         Log::warning('Invalid method used for webhook', [
//             'method' => $request->method(),
//             'url' => $request->fullUrl()
//         ]);
//         return response()->json([
//             'error' => 'Method not allowed',
//             'allowed_methods' => ['POST']
//         ], 405);
//     }

//     return app(SmsWebhookController::class)->handleWebhook($request);
// })->name('handlewebook.send');



Route::get('/smsFilterByDate', [GetSmsReportByDate::class, 'getSmsByDate'])->name('smsFilter.send');
Route::post('/dynamicSenderId', [DynamicSenderId::class, 'sendToDynamicSenderApi'])->name('sendToDynamic.send');
Route::get('/message-stats', [MessageStatController::class, 'getMessageStats'])->name('messageStats.get');
Route::get('/message-stats/export', [MessageStatController::class, 'exportCsv'])->name('messageStats.export');




