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

    // Get the entire payload
     $data = $request->all();
    
    // // Access the nested 'all' data
     //$allData = $data['all'] ?? [];

     // Now you can access the type and other fields correctly
    if (isset($data['type']) && ($data['type'] == 8 || $data['type'] == 16)) {
        Log::info("Webhook ignored due to type: {$data['type']}");
        return response()->json([
            'status' => true,
            'message' => 'Webhook data ignored due to type',
        ]);
    }

    // // Extract smsID from the correct location
     $smsID = $data['smsID'] ?? null;
     Log::info("Extracted smsID:", ['smsID' => $smsID]);

     // Access the message from the correct location
     $message = $data['message'] ?? '';
     Log::info("Message content:", compact('message'));
    
     $status = null;
     $error = null;

    if ($message) {
        // Parse the message string for the status and error codes
        preg_match('/stat:([^\s]+)/', $message, $statusMatch);
        preg_match('/err:([^\s]+)/', $message, $errorMatch);

        $status = $statusMatch[1] ?? null;
        $error = $errorMatch[1] ?? null;
    }

    // Log extracted information
    Log::info("Processed Webhook Data:", [
        'status' => $status,
        'error' => $error,
        'smsID' => $smsID,
    ]);

    
    

    return response()->json([
        'status' => true,
        'message' => 'Webhook data logged successfully',
    ]);
}

    public function test(){
        return response()->json([
            'message' => 'Hello from Laravel!',
            'data' => [
                'name' => 'John Doe',
                'age' => 30
            ]
        ]);
    }
}





