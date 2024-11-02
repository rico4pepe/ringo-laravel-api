<?php
namespace App\Import;

use App\Models\Sms;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ExcelCsvImport {
    protected $batchSize;
    protected $successCount = 0;
    protected $failCount = 0;
    protected $errors = [];
    protected $ordinaryMessage;

    public function __construct($batchSize = 500) {
        $this->batchSize = $batchSize;
        //$this->ordinaryMessage = $ordinaryMessage ?? "Dear customer, please fund your account for uninterrupted services.";
    }

    public function importFile($filePath, $isCustomMessage, $ordinaryMessage = null)
{
    try {

        Log::info('Calling importFile method with:', [
            'filePath' => $filePath,
            'isCustomMessage' => $isCustomMessage,
            'ordinaryMessage' => $ordinaryMessage,
        ]);
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $chunk = [];
        $rowCount = 0;

        foreach ($worksheet->getRowIterator() as $row) {
            $rowData = [];
            foreach ($row->getCellIterator() as $cell) {
                $rowData[] = $cell->getFormattedValue(); // Use formatted value to get the actual display value
            }

            if ($this->isValidRow(rowData: $rowData)) {
                $processedData = $this->processData($rowData, $isCustomMessage, $ordinaryMessage);
                $chunk[] = $processedData;

                if (count($chunk) >= $this->batchSize) {
                    $this->saveToDatabase($chunk);
                    $this->sendBatchSms($chunk);
                    $chunk = []; // Reset the chunk
                }
            } else {
                $this->errors[] = "Row {$rowCount} is invalid or incomplete.";
                $this->failCount++;
            }

            $rowCount++;
        }

        // Save and send any remaining rows
        if (!empty($chunk)) {
            $this->saveToDatabase($chunk);
            $this->sendBatchSms($chunk);
        }

        return [
            'successCount' => $this->successCount,
            'failCount' => $this->failCount,
            'errors' => $this->errors,
        ];
    } catch (\Exception $e) {
        throw new \Exception("Error processing file: " . $e->getMessage());
    }
}

    protected function processData($rowData, $isCustomMessage, $ordinaryMessage)
{
    $firstName = $rowData[1] ?? 'Customer'; // First name
    $lastName = $rowData[2] ?? ''; // Last name
    $phoneNumber = $rowData[3] ?? ''; // Phone number
    $dateString = $rowData[5] ?? now()->toDateString(); // Date is at index 5
    $date = $this->parseDate($dateString);
    $accountNumber = $this->maskAccountNumber($rowData[7] ?? ''); // Assuming account number is in the last column

   // $ordinaryMessage = html_entity_decode($ordinaryMessage, ENT_QUOTES, 'UTF-8');

   $message = $isCustomMessage
        ? $this->formatCustomMessage($firstName, $accountNumber, $date)
        : html_entity_decode($ordinaryMessage ?? "Dear customer, please fund your account for uninterrupted services.", ENT_QUOTES, 'UTF-8');

    return [
        'firstname' => $firstName,
        'lastname' => $lastName,
        'phone_number' => $phoneNumber,
        'message' =>  $message,
        'date' => $date,
    ];
}


    protected function parseDate($dateString) {
        try {
            // Adjust to handle multiple formats if needed
            return Carbon::createFromFormat('Y-m-d', trim($dateString))->format('Y-m-d');
        } catch (\Exception $e) {
            Log::error("Date parsing error: " . $e->getMessage());
            // Return a default date or handle it accordingly
            return now()->toDateString(); // Fallback to current date
        }
    }





    protected function formatCustomMessage($firstName, $accountNumber, $date) {
        return "Dear {$firstName}, fund your account {$accountNumber} on {$date} and enjoy the benefits of banking with UBA. You can request an instant ATM card at any of our branches.";
    }

    protected function maskAccountNumber($accountNumber) {
        return substr($accountNumber, 0, 3) . 'XXXX' . substr($accountNumber, -3);
    }

    protected function isValidRow($rowData) {
        // Check if phone number is present and valid
        return isset($rowData[3]) && !empty($rowData[3]);
    }

    protected function saveToDatabase(array $chunk) {
        try {
             Log::info("Saving chunk to database:", $chunk);
            Sms::insert($chunk); // Batch insert for efficiency
            Log::info("Successfully saved chunk of " . count($chunk));
        } catch (\Exception $e) {
            $this->failCount += count(value: $chunk);
            $this->errors[] = "Error saving chunk to database: " . $e->getMessage();
        }
    }

    protected function sendBatchSms(array $chunk) {
        try {
            foreach ($chunk as $data) {
                $response = $this->sendSmsToApi($data);

                if ($response->successful()) {
                    $this->successCount++;
                } else {
                    $this->failCount++;
                    $this->errors[] = "Error sending SMS to {$data['phone_number']}: " . $response->body();
                }
            }
        } catch (\Exception $e) {
            Log::error("Batch SMS sending failed: " . $e->getMessage());
            $this->failCount += count($chunk);
            $this->errors[] = "Batch sending error: " . $e->getMessage();
        }
    }

    protected function sendSmsToApi($data) {


        $apiUrl = 'https://api.example.com/send-sms';

        return Http::post($apiUrl, [
            'reciever' => $data['phone_number'],
            'text' => $data['message'],
            'sender' => 'UBA',
            //'request_id' => $data[''],
        ]);
    }




    public function sendSingleSms($data)
{

    // Adjusted to accommodate single SMS sending
    $apiUrl = 'https://api.example.com/send-sms';

    return Http::post($apiUrl, [
        'phoneNumber' => $data['phone_number'],
        'message' => $data['message'],
    ]);
}

protected function saveSingleToDatabase(array $data, $isCustomMessage)
{
    try {

        $firstName = $data['firstName'];
        $phoneNumber = $data['phone_number'];
        $accountNumber = "";
        $date = date('Y-m-d');
        $message = $isCustomMessage
        ? $this->formatCustomMessage($firstName, $accountNumber, $date)
        : html_entity_decode($ordinaryMessage ?? "Dear customer, please fund your account for uninterrupted services.", ENT_QUOTES, 'UTF-8');

        $smsData = [
            'phone_number' => $phoneNumber,
            'first_name' => $firstName,
            'last_name' => $data['last_name'] ?? '', // Ensure last_name is included
            'message' => $message, // Save the formatted message
            'date' => $date, // You can store the date as well
            // Add any additional fields required by the Sms model
        ];

        Log::info("Saving single SMS to database:", $data);
        Sms::create($smsData); // Single insert
        Log::info("Successfully saved SMS for " . $data['phone_number']);
    } catch (\Exception $e) {
        $this->failCount++;
        $this->errors[] = "Error saving single SMS to database: " . $e->getMessage();
    }
}
}
