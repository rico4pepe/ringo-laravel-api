<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Import\ExcelCsvImport;
use App\Models\ScheduledCampaign;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExcelCsvImportController extends Controller
{
    protected $importService;

    public function __construct(ExcelCsvImport $importService)
    {
        $this->importService = $importService;
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx',
           // 'ordinaryMessage' => 'required_if:isCustomMessage,false|string',
            'campaignTitle' => 'required_if:isScheduled,true|string',
            'scheduleDate' => 'required_if:isScheduled,true|date',
            'scheduleTime' => 'required_if:isScheduled,true|date_format:H:i'
        ]);




        $isCustomMessage = filter_var($request->input('isCustomMessage'), FILTER_VALIDATE_BOOLEAN);
        $ordinaryMessage = trim($request->input('messageContent'));


           // Sanitize the ordinaryMessage
         $ordinaryMessage = strip_tags($ordinaryMessage);
        $ordinaryMessage = htmlspecialchars($ordinaryMessage, ENT_QUOTES, 'UTF-8');


        // Dump and inspect values
       // dd($isCustomMessage, $ordinaryMessage);
        Log::info('Received input', [
            'isCustomMessage' => $isCustomMessage,
            'ordinaryMessage' => $ordinaryMessage,
        ]);




        // Store file temporarily
        $filePath = $request->file('file')->store('temp');

        try {
            if ($request->has('scheduleDate') && $request->has('scheduleTime')) {
                $this->scheduleCampaign($request, $filePath, $isCustomMessage);
                return response()->json(['message' => 'Campaign scheduled successfully.']);
            } else {

                Log::info('Calling importFile method with:', [
                    'isCustomMessage' => $isCustomMessage,
                    'ordinaryMessage' => $ordinaryMessage,
                ]);
                // If no schedule, process and send immediately
                $summary = $this->importService->importFile(storage_path("app/{$filePath}"), $isCustomMessage, $ordinaryMessage);

                return response()->json([
                    'message' => 'File import completed.',
                    'summary' => $summary
                ]);
            }
        } catch (\Exception $e) {
            Log::error("File import failed: " . $e->getMessage());
            return response()->json(['error' => 'File import failed. Please try again.'], 500);
        } finally {
            // Delete the temporary file after processing or error
            Storage::delete($filePath);
        }
    }




public function sendSingleSms(Request $request)
{
    $request->validate([
        'phone_number' => 'required|string',
        'first_name' => 'required|string',
        'last_name' => 'required|string',
    ]);

     $isCustomMessage = filter_var($request->input('isCustomMessage'), FILTER_VALIDATE_BOOLEAN);
     $ordinaryMessage = trim($request->input('messageContent'));

     //
     Log::info('Test the log info');

           // Sanitize the ordinaryMessage
         $ordinaryMessage = strip_tags($ordinaryMessage);
        $ordinaryMessage = htmlspecialchars($ordinaryMessage, ENT_QUOTES, 'UTF-8');

        Log::info('Incoming SMS request:', $request->all());

        Log::info('Received input', [
            'isCustomMessage' => $isCustomMessage,
            'ordinaryMessage' => $ordinaryMessage,
        ]);
    try {
        // Create a single record array
        $singleRecord = [
            'phone_number' => $request->input('phone_number'),
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'message' => $ordinaryMessage,
            //'is_custom_message' => $request->input('is_custom_message'),
        ];

        // Call the sendSingleSms method in your service
        $response = $this->importService->sendSingleSmsWithSave($singleRecord, $isCustomMessage);

        if ($response && $response['status'] === true) { // Adjusted to check status
            return response()->json(['message' => 'SMS sent successfully.'], 200);
        } else {
            $error = $response['error'] ?? 'Failed to send SMS.';
            return response()->json(['error' => $error], 500);
        }
    } catch (\Exception $e) {
        Log::error("Error sending single SMS: " . $e->getMessage());
        return response()->json(['error' => 'An error occurred while sending SMS.'], 500);
    }
}

    protected function scheduleCampaign($request, $filePath, $isCustomMessage)
    {
        ScheduledCampaign::create(attributes: [
            'campaign_title' => $request->input('campaignTitle'),
            'file_path' => $filePath,
            'schedule_date' => $request->input('scheduleDate'),
            'schedule_time' => $request->input('scheduleTime'),
            'is_custom_message' => $isCustomMessage,
            'unique_reference' => Str::uuid()->toString(), // Corrected this line
        ]);
    }
}
