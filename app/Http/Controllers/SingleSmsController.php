<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Import\ExcelCsvImport;

class SingleSmsController extends Controller
{
    protected $importService;

    public function __construct(ExcelCsvImport $importService)
    {
        $this->importService = $importService;
    }

    public function sendSingleSms(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'message' => 'required|string',
            'is_custom_message' => 'boolean',
            'first_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'date' => 'nullable|date',
        ]);

        $phoneNumber = $request->input('phone_number');
        $message = $request->input('message');
        $isCustomMessage = $request->boolean('is_custom_message', false);

        // Format message if it's a custom message
        $processedMessage = $isCustomMessage
            ? $this->importService->formatCustomMessage(
                $request->input('first_name') ?? '',
                $request->input('account_number') ?? '',
                $request->input('date') ?? ''
            )
            : $message;

        // Send SMS directly using the service
        $response = $this->importService->sendSmsDirectly($phoneNumber, $processedMessage);

        return response()->json([
            'message' => $response ? 'SMS sent successfully' : 'SMS failed to send',
        ]);
    }
}
