<?php
namespace App\Import;

use App\Models\Sms;
use App\Events\ImportProgressUpdated;
//use App\Services\SmsWebhook;


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
                $rowCount++; // Increment rowCount at the start of each loop
                $rowData = [];

                foreach ($row->getCellIterator() as $cell) {
                    $rowData[] = $cell->getFormattedValue(); // Use formatted value to get the actual display value
                }

                if ($this->isValidRow(rowData: $rowData)) {
                    $processedData = $this->processData($rowData, $isCustomMessage, $ordinaryMessage);
                    $chunk[] = $processedData;

                    // Process the chunk if it reaches the batch size
                    if (count($chunk) >= $this->batchSize) {
                        $this->saveToDatabase($chunk);
                        $this->sendBatchSms($chunk);

                        // Emit progress after processing each chunk
                        event(new ImportProgressUpdated($rowCount, $this->successCount, $this->failCount));
                        $chunk = []; // Reset the chunk
                    }
                } else {
                    $this->errors[] = "Row {$rowCount} is invalid or incomplete.";
                    $this->failCount++;
                }
            }

            // Process any remaining rows in the chunk
            if (!empty($chunk)) {
                $this->saveToDatabase($chunk);
                $this->sendBatchSms($chunk);

                // Emit progress for any remaining rows
                event(new ImportProgressUpdated($rowCount, $this->successCount, $this->failCount));
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
             //$insertedIds = []; // Array to hold the IDs of inserted records
            Sms::insert($chunk); // Batch insert for efficiency

            // Retrieve the IDs of the newly inserted records and add to chunk line 134 to 138 was added to achieve  the retrieave id
            // This should be made more effective in a concurent anvironmen we should consider unique identifier (like a UUID) for each record or a timestamp to ensure you can
        $insertedIds = Sms::latest()->take(count($chunk))->pluck('id')->toArray();
        foreach ($chunk as $index => &$record) {
            $record['db_id'] = $insertedIds[$index];
        }
            Log::info("Successfully saved chunk of " . count($chunk));
        } catch (\Exception $e) {
            $this->failCount += count(value: $chunk);
            $this->errors[] = "Error saving chunk to database: " . $e->getMessage();
        }
    }

    protected function sendBatchSms(array $chunk) {
        $updateData = []; // Array to hold data for batch updating
        $successCount = 0;
        $failCount = 0;
        $errors = [];

        foreach ($chunk as $data) {
            try {
                $response = $this->sendSmsToApi($data);

                if ($response['status']) { // Check status instead of successful() method
                    $successCount++;
                    $updateData[] = [
                        'id' => $data['db_id'],
                        'status_code' => 'success',
                        'api_message' => $response['body'],
                        'updated_at' => now(),
                    ];
                } else {
                    $failCount++;
                    $error_message = $response['error'] ?? "Error: HTTP code {$response['http_code']}";
                    $errors[] = "Error sending SMS to {$data['phone_number']}: " . $error_message;

                    $updateData[] = [
                        'id' => $data['db_id'],
                        'status_code' => 'failed',
                        'api_message' => $error_message,
                        'updated_at' => now(),
                    ];
                }
            } catch (\Exception $e) {
                Log::error("Error sending SMS to {$data['phone_number']}: " . $e->getMessage());
                $failCount++;
                $errors[] = "Exception error for {$data['phone_number']}: " . $e->getMessage();

                $updateData[] = [
                    'id' => $data['db_id'],
                    'status_code' => 'failed',
                    'api_message' => "Exception: " . $e->getMessage(),
                    'updated_at' => now(),
                ];
            }
        }

        // Perform a batch update in the database for this chunk
        foreach ($updateData as $data) {
            Sms::where('id', $data['id'])->update([
                'status_code' => $data['status_code'],
                'api_message' => $data['api_message'],
                'updated_at' => $data['updated_at'],
            ]);
        }

        // Update class-level success and fail counts
        $this->successCount += $successCount;
        $this->failCount += $failCount;
        $this->errors = array_merge($this->errors, $errors);

        // Emit progress after each chunk is processed
        event(new ImportProgressUpdated($this->successCount, $this->failCount, count($chunk)));
    }



    protected function sendSmsToApi($data) {
        Log::info("Starting SMS send process with data:", $data);

        $apiUrl = '' . http_build_query([
            'reciever' => $data['phone_number'],
            'text' => $data['message'],
            'sender' => 'UBA',
            'request_id' => $data['db_id'], // Using the database ID we added
        ]);

        Log::info("Generated API URL: $apiUrl");
        $ch = curl_init($apiUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Log the HTTP status code and response for debugging
        Log::info("HTTP Status Code: $httpCode");
        Log::info("Raw API Response: $response");

        // Check for cURL errors
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            Log::error("cURL Error for SMS request_id {$data['request_id']}: " . $error_msg);

            // Close cURL before returning the error response
            curl_close($ch);

            // Return error response structure
            return [
                'status' => false,
                'http_code' => $httpCode,
                'error' => $error_msg,
            ];
        }

        // Close cURL before returning the success response
        curl_close($ch);

        // Return successful response structure
        return [
            'status' => ($httpCode >= 200 && $httpCode < 300),
            'http_code' => $httpCode,
            'body' => $response,
        ];
    }





    public function sendSingleSms(array $smsData)
    {
        // Log the initial data before processing
        Log::info("Starting SMS send process with data:", $smsData);

        // Correct URL construction
        $apiUrl = 'https://ubasms.approot.ng/php/bulksms.php?' . http_build_query([
            'receiver' => $smsData['receiver'],
            'text' => $smsData['text'],
            'sender' => $smsData['sender'],
            'request_id' => $smsData['request_id'],
        ]);

        Log::info("Generated API URL: $apiUrl");

        // Initialize cURL
        $ch = curl_init($apiUrl);

            // Prepare JSON data
    $jsonData =  http_build_query([
        'receiver' => $smsData['receiver'],
        'text' => $smsData['text'],
        'sender' => $smsData['sender'],
        'request_id' => $smsData['request_id'],
    ]);


        // Set cURL options
            // Set cURL options
    // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    // curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // Execute cURL request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Log HTTP code and response
        Log::info("HTTP Status Code: $httpCode");
        Log::info("Raw API Response: $response");

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            Log::error("cURL Error for SMS request_id {$smsData['request_id']}: " . $error_msg);
        }

        // Close the cURL session
        curl_close($ch);

        // Extract status code and message from the response
        $statusMessage = trim($response);
        $parts = explode(':', $statusMessage);
        Log::info("Parsed response parts:", $parts);

        $statusCode = null;
        $message = null;

        if (count($parts) >= 2) {
            $statusCode = trim($parts[1]);
            $message = isset($parts[2]) ? trim($parts[2]) : 'No message available';
        }

        // Update the SMS record
        try {
            Sms::where('id', $smsData['request_id'])->update([
                'status_code' => $statusCode,
                'api_message' => $message,
                'updated_at' => now(),
            ]);
            Log::info("Database updated successfully for request_id {$smsData['request_id']}");
        } catch (\Exception $e) {
            Log::error("Database update failed for request_id {$smsData['request_id']}: " . $e->getMessage());
            return [
                'status' => false,
                'error' => "Database update error: " . $e->getMessage()
            ];
        }

        // Prepare webhook data
        $webhookData = [
            'receiver' => $smsData['receiver'],
            'sender' => $smsData['sender'],
            'request_id' => $smsData['request_id'],
            'status_code' => $statusCode,
            'api_message' => $message,
            'logged_at' => now(),
        ];

        try {
            $webhookUrl = route('handlewebook.send');
            Log::info("Webhook URL generated: $webhookUrl");

            $webhookResponse = Http::post($webhookUrl, $webhookData);
            Log::info("Webhook response received:", [
                'status' => $webhookResponse->status(),
                'body' => $webhookResponse->body(),
                'sent_data' => $webhookData
            ]);
        } catch (\Exception $e) {
            Log::error("Webhook call failed: " . $e->getMessage(), [
                'webhook_data' => $webhookData,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'body' => $response,
            'status_code' => $httpCode,
        ];
    }




public function saveSingleToDatabase(array $data, $isCustomMessage)
{
    try {
        $firstName = $data['first_name']; // Ensure key matches your input
        $lastName = $data['last_name'] ?? ''; // Default to empty if not provided
        $phoneNumber = $data['phone_number'];
        $accountNumber = "";
        $date = date('Y-m-d');
        $message = $isCustomMessage
        ? $this->formatCustomMessage($firstName, $accountNumber, $date)
        : html_entity_decode($data['message'] ?? "Dear customer, please fund your account for uninterrupted services.", ENT_QUOTES, 'UTF-8');

        $smsData = [
            'phone_number' => $phoneNumber,
            'firstname' => $firstName,
            'lastname' => $lastName, // Ensure last_name is included
            'message' => $message, // Save the formatted message
            'date' => $date, // You can store the date as well
            // Add any additional fields required by the Sms model
        ];

        Log::info("Attempting to save single SMS to database:", $smsData);
        $smsRecord = Sms::create($smsData);

        Log::info("Successfully saved SMS with ID {$smsRecord->id} for " . $smsData['phone_number']);
        return $smsRecord->id;
       // Log::info("Successfully saved SMS with ID {$insertedId} for " . $smsData['phone_number']);

        //return $insertedId; // Return the ID to be used in sendSingleSms


    } catch (\Exception $e) {
        $this->failCount++;
        $this->errors[] = "Error saving single SMS to database: " . $e->getMessage();
    }





}




public function sendSingleSmsWithSave(array $data, bool $isCustomMessage)
{
    $data['date'] = date('Y-m-d');
    Log::info("Testing what is coming from the data ", $data);

    // Save to database and retrieve ID
    $dbId = $this->saveSingleToDatabase($data, $isCustomMessage);

    if (!$dbId) {
        Log::error("Failed to save SMS to database for data: id is not found ", $data);
        return [
            'status' => false,
            'error' => 'Failed to save SMS to database. Id not found',
        ];
    }

    // Prepare SMS data with db_id
    $data['db_id'] = $dbId;
    $smsData = [
        'text' => $data['message'],
        'receiver' => $data['phone_number'],
        'sender' => "UBA",
        'request_id' => $data['db_id']
    ];

    Log::info("Sending SMS with data: ", $smsData);

    // Send SMS and get response
    $response = $this->sendSingleSms($smsData);

    // Check the response status
    if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
        Log::info("SMS sent successfully to {$data['phone_number']} with request_id {$data['db_id']}");
        return [
            'status' => true,
            'message' => 'SMS sent successfully.',
            'response' => $response['body'], // Log response body on success
        ];
    } else {
        Log::error("Error sending SMS to {$data['phone_number']}: " . $response['body']);
        return [
            'status' => false,
            'error' => 'Failed to send SMS.',
            'response' => $response['body'], // Include the error response
        ];
    }
}


}
