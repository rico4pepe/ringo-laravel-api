<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsWebhookController extends Controller
{
    /**
     * Handle incoming webhook data and log it.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request)
    {
        Log::info("Raw webhook request received:", [
            'all' => $request->all(),
            'content' => $request->getContent()
        ]);
        // Extract data from the incoming request
        $data = [
            'receiver' => $request->input('receiver', 'N/A'),
            //'text' => $request->input('text', 'N/A'),
            'sender' => $request->input('sender', 'N/A'),
            'request_id' => $request->input('request_id', 'N/A'),
            'status_code' => $request->input('status_code', 'N/A'),
            'api_message' => $request->input('api_message', 'No message'),
           // 'status' => $request->input('status', 'unknown'),
            'date' => now()->toDateTimeString(), // Current date and time
        ];

        // Log the received data
        Log::info("Received SMS Webhook Data:", $data);


           // Return a JSON response to confirm receipt of the webhook data
           return response()->json([
            'status' => true,
            'message' => 'Webhook data logged successfully',
        ]);

        // Return a JSON response to confirm receipt of the webhook data

    }
}
