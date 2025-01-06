<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Sms;


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
            'method' => $request->method(),
            'content' => $request->getContent()
        ]);

        // Get the entire payload
        $data = $request->all();

        // Now you can access the type and other fields correctly
        if (isset($data['type']) && ($data['type'] == 8 || $data['type'] == 16)) {
            Log::info("Webhook ignored due to type: {$data['type']}");
            return response()->json([
                'status' => true,
                'message' => 'Webhook data ignored due to type',
            ]);
        }

        

        // Extract smsID from the correct location
        $smsID = $data['smsID'] ?? null;
        $kannel_id = $data['kannel_id'] ?? null;
        $sender = $data['sender'] ?? null;
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

        $updateData = [
            'status' => $status,
            'err_code' => $error,
            'kannel_id' => $kannel_id,
            'sender' => $sender,
            'updated_at' => now()
        ];

        try {
            Sms::where('id', $smsID)->update($updateData);
            Log::info("Database updated successfully for request_id {$smsID}");
            return response()->json([
                'status' => true,
                'message' => 'Database updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Database update failed for request_id {$smsID}: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'error' => "Database update error: " . $e->getMessage()
            ], 500); // Optionally, you can set a status code like 500 for server error
        }
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
