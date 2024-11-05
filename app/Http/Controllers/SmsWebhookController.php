<?php

namespace App\Http\Controllers;

use App\Models\Sms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SmsWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Log the incoming request
        Log::info('Incoming SMS webhook', $request->all());

        $smsId = $request->input('smsID');
        $type = $request->input('type');
        $message = $request->input('message');
        $phoneNumber = $request->input('phone');
        $sender = $request->input('sender');

        // Validate the request
        if ($type == 8 || $type == 16) {
            return response()->json(['message' => 'Request ignored'], 200);
        }

        // Parse the message content
        $parts = explode(' ', $message);
        $status = $parts[0] ?? null;
        preg_match('/err:(\d+)/', $message, $matches);
        $errorCode = $matches[1] ?? null;

        try {
            // Fetch the message record from the database
            $messageRecord = Sms::findOrFail($smsId);

            // Generate a random "done" timestamp
            $createdAt = $messageRecord->created_at->getTimestamp();
            $randomDoneTimestamp = rand($createdAt, $createdAt + 60);
            $doneDate = date('Y-m-d H:i:s', $randomDoneTimestamp);

            // Update the message record
            $messageRecord->update([
                'dlr_status' => $status,
                'dlr_request' => $errorCode,
                'dlr_results' => $smsId,
                'network' => $sender,
                'updated_at' => $doneDate,
            ]);

            Log::info('SMS webhook processed successfully', [
                'sms_id' => $smsId,
                'status' => $status,
                'error_code' => $errorCode,
            ]);

            return response()->json(['message' => 'SMS webhook processed'], 200);
        } catch (\Exception $e) {
            Log::error('Error processing SMS webhook', [
                'error' => $e->getMessage(),
                'sms_id' => $smsId,
            ]);

            return response()->json(['message' => 'Error processing SMS webhook'], 500);
        }
    }
}