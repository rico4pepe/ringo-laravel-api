<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DynamicSenderId extends Controller
{
    /**
     * Handle the dynamic sender id API request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendToSecondaryApi(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'phone_number' => 'required|string',
            'message' => 'required|string',
            'sender_id' => 'required|string',
        ]);

        Log::info("Received data for dynamic sending:", $validatedData);

        // Build the secondary API URL
        $dynamicsenderApiUrl = 'https://messaging.approot.ng/api3.php?' . http_build_query([
            'phone' => $validatedData['phone_number'],
            'message' => $validatedData['message'],
            'sender_id' => $validatedData['sender_id'],
        ]);

        Log::info("Generated Secondary API URL: $dynamicsenderApiUrl");

        // Initialize cURL
        $ch = curl_init($dynamicsenderApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // Execute the cURL request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Log the HTTP status and response
        Log::info(" HTTP Status Code: $httpCode");
        Log::info(" API Response: $response");

        // Handle cURL errors
        if (curl_errno($ch)) {
            $errorMsg = curl_error($ch);
            Log::error("cURL Error for secondary request: " . $errorMsg);
            curl_close($ch);

            return response()->json([
                'success' => false,
                'error' => $errorMsg,
            ], 500);
        }

        curl_close($ch);

        // Return the response as JSON
        return response()->json([
            'success' => true,
            'http_status_code' => $httpCode,
            'api_response' => $response,
        ]);
    }
}
