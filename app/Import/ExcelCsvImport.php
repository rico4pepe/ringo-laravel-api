<?php

namespace App\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ExcelCsvImport{
    protected $batchSize;
    protected $successCount = 0;
    protected $failCount = 0;
    protected $errors = [];

    protected $ordinaryMessage; // New variable for ordinary message

    public function __construct($batchSize = 500, $ordinaryMessage = null)
    {
        $this->batchSize = $batchSize;
        $this->ordinaryMessage = $ordinaryMessage ?? "Dear customer, please fund your account for uninterrupted services.";
    }

    public function importFile($filePath, $isCustomMessage)
{
    try {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $chunk = [];
        $rowCount = 0;
        $successCount = 0;
        $failCount = 0;
        $errors = [];

        foreach ($worksheet->getRowIterator() as $row) {
            try {
                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $rowData[] = $cell->getValue();
                }

                // Process row data with the isCustomMessage flag
                $processedData = $this->processData($rowData, $isCustomMessage);

                // Send SMS using the external API
                $response = $this->sendSmsToApi($processedData);

                if ($response->successful()) {
                    $successCount++; // Increment on successful API response
                } else {
                    $failCount++;
                    $errors[] = "Row {$rowCount}: " . $response->body(); // Capture any API error messages
                }
            } catch (\Exception $e) {
                $failCount++;
                $errors[] = "Row {$rowCount}: " . $e->getMessage(); // Capture any row-specific processing error
            }

            $rowCount++;
        }

        return [
            'successCount' => $successCount,
            'failCount' => $failCount,
            'errors' => $errors,
        ];
    } catch (\Exception $e) {
        throw new \Exception("Error processing file: " . $e->getMessage());
    }
}


 // Format custom message for personalized SMS
 public function formatCustomMessage($firstName, $accountNumber, $date)
 {
     // Mask all but the last 4 digits of the account number
     $maskedAccountNumber = $this->maskAccountNumber($accountNumber);

     // Return the formatted message
     return "Dear {$firstName}, fund your account {$maskedAccountNumber} on {$date} and enjoy the benefits of banking with UBA. You can request an instant ATM card at any of our branches.";
 }

    protected function processData($rowData, $isCustomMessage)
    {
        $firstName = $rowData[0]; // Assuming first name is in index 0
        $accountNumber = $this->maskAccountNumber($rowData[1]); // Assuming account number is in index 1
        $date = $rowData[2];
        if ($isCustomMessage) {
            // Apply custom message template
            return $this->formatCustomMessage($firstName, $accountNumber, $date);
           // return "Dear {$firstName}, fund your account {$accountNumber} on {$date} and enjoy the benefits of banking with UBA. You can request an instant ATM card at any of our branches.";
        } else {
            // Apply ordinary message
            return [
                'phoneNumber' => $rowData[3],
                'message' => $this->ordinaryMessage
            ];
        }
    }

    protected function maskAccountNumber($accountNumber)
    {
        return substr($accountNumber, 0, 3) . 'XXXX' . substr($accountNumber, 7);
    }

    protected function saveChunk($chunk)
    {
        try {
            // Save the chunk to database (for example)
            // Model::insert($chunk);
            Log::info("Saving chunk of size " . count($chunk));
        } catch (\Exception $e) {
            $this->failCount += count($chunk);
            $this->errors[] = "Error saving chunk: " . $e->getMessage();
        }
    }



    protected function sendSmsToApi($processedData)
{
    // Example API URL
    $apiUrl = 'https://api.example.com/send-sms';

    // Make an HTTP POST request to the external API with processed data
    return Http::post($apiUrl, [
        'phoneNumber' => $processedData['phoneNumber'],
        'message' => $processedData['message'],
        'otherParam' => $processedData['otherParam'], // Add other parameters as needed
    ]);
}


public function sendSmsDirectly($phoneNumber, $message)
{
    $apiUrl = 'https://api.example.com/send-sms';

    return Http::post($apiUrl, [
        'phoneNumber' => $phoneNumber,
        'message' => $message,
    ])->successful();
}

}
