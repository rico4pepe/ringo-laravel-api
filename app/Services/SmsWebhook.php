<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;



class SmsWebhook {
    public function handleWebhook($dlrData) {
        // Prepare the data for logging
        $data = [
            'receiver' => $dlrData['receiver'],
            //'text' => $dlrData['text'],
            'sender' => $dlrData['sender'],
            'request_id' => $dlrData['request_id'],
            'status_code' => $dlrData['status_code'] ?? 'N/A', // Assuming status_code is included in dlrData
            'api_message' => $dlrData['api_message'] ?? 'No message', // Assuming api_message is included in dlrData
            'status' => $dlrData['status'] ?? 'unknown', // Assuming status is included in dlrData
            'date' => now()->toDateTimeString(), // Current date and time
        ];

        // Log the received data
        Log::info("Received DLR Data: ", $data);

        // Log the processed status
        Log::info("Webhook processed for request_id: {$data['request_id']}");

        // Optionally, log if a message was successfully delivered
        if ($data['status'] === 'Aceepted') {
            Log::info("Message delivered to: {$data['receiver']}");
        } else {
            Log::warning("Message delivery failed for request_id: {$data['request_id']}");
        }

        // Return a success response if needed
        return [
            'status' => true,
            'message' => 'Webhook processed successfully',
        ];
    }
}
