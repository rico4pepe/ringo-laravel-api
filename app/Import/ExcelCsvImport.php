<?php
namespace App\Import;

use App\Models\Sms;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Events\ImportProgressUpdated;
use Illuminate\Support\Facades\DB;

class ExcelCsvImport {
    protected $batchSize;
    protected $successCount = 0;
    protected $failCount = 0;
    protected $errors = [];
    protected $ordinaryMessage;

    protected $expectedHeaders = [
        "id", "firstname", "lastname", "phone_number", "message", "date", "created_at", "updated_at"
    ];

    public function __construct($batchSize = 1000) {
        $this->batchSize = $batchSize;
        //$this->ordinaryMessage = $ordinaryMessage ?? "Dear customer, please fund your account for uninterrupted services.";
    }

    public function importFile($filePath, $isCustomMessage, $ordinaryMessage = null)
{
    try {

         // Check file extension and MIME type
         $fileInfo = pathinfo($filePath);
         $fileExtension = strtolower($fileInfo['extension']);
         $allowedExtensions = ['csv', 'xls', 'xlsx'];

           // Validate file extension
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new \Exception("Invalid file type. Only CSV and Excel files are supported.");
        }





        Log::info('Calling importFile method with:', [
            'filePath' => $filePath,
            'isCustomMessage' => $isCustomMessage,
            'ordinaryMessage' => $ordinaryMessage,
        ]);
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $chunk = [];
        $rowCount = 1;

        //$chunk = [];
        //$rowCount = 1; // Start from 1 as headers are row 0


          // Retrieve and validate headers from the first row
          $headerRow = [];
          foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
              foreach ($row->getCellIterator() as $cell) {
                  $headerRow[] = strtolower(trim($cell->getFormattedValue()));
              }
              break; // Only get the first row for headers
          }

            // Validate headers
        $validationResult = $this->validateHeaders($headerRow);
        if (!$validationResult['status']) {
            return [
                'successCount' => $this->successCount,
                'failCount' => $this->failCount,
                'errors' => [$validationResult['message']],
            ];
        }

        foreach ($worksheet->getRowIterator($rowCount + 1) as $row) {
            $rowData = [];
            foreach ($row->getCellIterator() as $cell) {
                $rowData[] = $cell->getFormattedValue(); // Use formatted value to get the actual display value
            }

            if ($this->isValidRow(rowData: $rowData)) {
                $processedData = $this->processData($rowData, $isCustomMessage, $ordinaryMessage);
                $chunk[] = $processedData;

                if (count($chunk) >= $this->batchSize) {
                    $chunkWithDbIds = $this->saveToDatabase($chunk);

                    // Send batch SMS with the chunk containing db_id
                    $this->sendBatchSms($chunkWithDbIds);
                    $chunk = []; // Reset the chunk
                }
            } else {
                $this->errors[] = "Row {$rowCount} is invalid or incomplete.";
                $this->failCount++;
            }

            // Emit the progress event after processing each row
            event(new ImportProgressUpdated($rowCount, $this->successCount, $this->failCount));
            $rowCount++;
        }

        // Save and send any remaining rows
        if (!empty($chunk)) {
            $chunkWithDbIds = $this->saveToDatabase($chunk);

            // Send batch SMS with the chunk containing db_id
            $this->sendBatchSms($chunkWithDbIds);
        }
        event(new ImportProgressUpdated($rowCount, $this->successCount, $this->failCount));
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


        Log::info("Processed row data with message:", ['message' => $message, 'isCustomMessage' => $isCustomMessage]);
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


              DB::beginTransaction();



             //Log::info("Saving chunk to database:", $chunk);
             $insertedIds = []; // Array to hold the IDs of inserted records
             DB::table('sms')->insert($chunk); // Batch insert for efficiency
           // $this->successCount += count($chunk);
            Log::info("Successfully saved chunk of " . count($chunk) . " records.");
            // Retrieve the IDs of the newly inserted records and add to chunk line 134 to 138 was added to achieve  the retrieave id
            // This should be made more effective in a concurent anvironmen we should consider unique identifier (like a UUID) for each record or a timestamp to ensure you can
        $insertedIds = Sms::latest()->take(count($chunk))->pluck('id')->toArray();
          // Assign the db_id to each record in the chunk
          foreach ($chunk as $index => &$record) {
            if (isset($insertedIds[$index])) {
                $record['db_id'] = $insertedIds[$index]; // Assign the db_id
                $this->successCount++;
            } else {
                Log::warning("No db_id found for record at index $index");
                $this->failCount++; // Update failCount correctly
            }
        }
        Log::info("Successfully saved chunk of " . count($chunk) . " records with db_ids.");

        // Log the final chunk with db_ids assigned
        Log::info("Returning chunk with db_ids:", $chunk);

        DB::commit();
        return $chunk;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->failCount += count(value: $chunk);
            $this->errors[] = "Error saving chunk to database: " . $e->getMessage();
            return []; // Return an empty array in case of an error
        }
    }

    protected function sendBatchSms(array $chunk) {

        if (empty($chunk)) {
            Log::warning('Empty chunk provided to sendBatchSms.');
            return; // Exit if chunk is empty
        }

        try {
            foreach ($chunk as $data) {

                if (!isset($data['db_id'])) {
                    Log::error("Missing db_id in record: ", $data);
                    //$this->failCount++;  // Increment failCount for missing db_id
                    $this->errors[] = "Error: Missing db_id for phone number {$data['phone_number']}";
                    continue;
                }

                $response = $this->sendSmsToApi($data);
 // Check the response status
    if ($response['status']) {
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
        } catch (\Exception $e) {
            Log::error("Batch SMS sending failed: " . $e->getMessage());
            $this->failCount += count($chunk);
            $this->errors[] = "Batch sending error: " . $e->getMessage();
        }
    }


    protected function validateHeaders(array $fileHeaders)
{
    // Trim whitespace and normalize headers to lowercase for a flexible comparison
    $cleanedHeaders = array_map('strtolower', array_map('trim', $fileHeaders));

    if ($cleanedHeaders !== $this->expectedHeaders) {
        return [
            'status' => false,
            'message' => "Headers do not match. Please use the correct format: " . implode(", ", $this->expectedHeaders),
        ];
    }

    return ['status' => true];
}


    protected function sendSmsToApi($data) {


        // $apiUrl = '';

        // return Http::post($apiUrl, [
        //     'reciever' => $data['phone_number'],
        //     'text' => $data['message'],
        //     'sender' => 'UBA',
        //     'request_id' => $data['db_id'], // Using the database ID we added
        // ]);


        Log::info("Starting SMS send process with data:", $data);
    $apiUrl = 'https://ubasms.approot.ng/php/bulksms.php?' .
        http_build_query([
            'receiver' => $data['phone_number'],
            'text' => $data['message'],
            'sender' => 'UBA',
            'request_id' => $data['db_id'], // Using the database ID we added
        ]);

          // Log the generated API URL
    Log::info("Generated API URL: $apiUrl");

    // Prepare JSON data
    // $jsonData =  http_build_query([
    //     'receiver' => $smsData['receiver'],
    //     'text' => $smsData['text'],
    //     'sender' => $smsData['sender'],
    //     'request_id' => $smsData['request_id'],
    // ]);

    // Initialize cURL
    $ch = curl_init($apiUrl);

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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code


    // Log the HTTP status code and response for debugging
    Log::info("HTTP Status Code: $httpCode");
    Log::info("Raw API Response: $response");

    // Check for cURL errors
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        Log::error("cURL Error for SMS request_id {$data['db_id']}: " . $error_msg);
    } else {
        // Log the response
        Log::info("API Response for SMS request_id {$data['db_id']}: " . $response);
    }

     // Extract status code and message from the response
$statusMessage = trim($response); // Clean the response
$parts = explode(':', $statusMessage); // Split the response by ':'


    // Log the response parsing attempt
    Log::info("Parsed response parts:", $parts);

// Initialize variables for status code and message
$statusCode = null;
$message = null;

// Check if parts have at least two elements
if (count($parts) === 3) {
    $statusCode = trim($parts[1]); // Get the status code (first part)
    $message = trim($parts[2]); // Get the message (second part)
}

  // Store response in database
  $updateData = [
    'status_code' => $statusCode,
    'api_message' => $message,
    'updated_at' => now()
];

   // Attempt to update the SMS record and log the result
   try {
    Sms::where('id', $data['db_id'])->update($updateData);
    Log::info("Database updated successfully for request_id {$data['db_id']}");
} catch (\Exception $e) {
    Log::error("Database update failed for request_id {$data['db_id']}: " . $e->getMessage());
    return [
        'status' => false,
        'error' => "Database update error: " . $e->getMessage()
    ];
}

     Log::info("API Response for SMS request_id {$data['db_id']}:  the status code: {$statusCode} : message: {$message}" . print_r($parts) . "count parts " . count($parts) );

// Prepare webhook data
$webhookData = [
    'reciever' => $data['phone_number'],
    'sender' => 'UBA',
    'request_id' => $data['db_id'], // Using the database ID we added
    'status_code' => $statusCode,
    'api_message' => $message,
    'logged_at' => now(),
];


            $webhookUrl = 'https://bfb3-169-255-124-242.ngrok-free.app/api/handlewebook';

            Log::info("Webhook URL generated: $webhookUrl");

            // $webhookResponse = Http::timeout(5)->post($webhookUrl, $webhookData);

            // Log::info("Webhook response received:", [
            //     'status' => $webhookResponse->status(),
            //     'body' => $webhookResponse->body(),
            //     'sent_data' => $webhookData
            // ]);


                  // Dispatch the webhook request asynchronously
        dispatch(function () use ($webhookUrl, $webhookData) {
            try {
                $response = Http::timeout(10)
                    ->retry(3, 100)
                    ->post($webhookUrl, $webhookData);

                if ($response->successful()) {
                    Log::info("Webhook delivered successfully", [
                        'request_id' => $webhookData['request_id']
                    ]);
                } else {
                    Log::error("Webhook request failed", [
                        'status' => $response->status(),
                        'response' => $response->body(),
                        'request_id' => $webhookData['request_id']
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Webhook delivery failed: " . $e->getMessage(), [
                    'webhook_url' => $webhookUrl,
                    'request_id' => $webhookData['request_id']
                ]);
            }
        })->afterResponse();


    // Close the cURL session
    curl_close($ch);


    // Send GET request to the second endpoint
     $secondaryApiUrl = 'https://messaging.approot.ng//api3.php?' .
     http_build_query([
         'phone' => $data['phone_number'],
         'message' => $data['message'],
         'sender_id' => 'UBA'
     ]);

     Log::info("Generated Secondary API URL: $secondaryApiUrl");

    $ch2 = curl_init($secondaryApiUrl);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);

    $secondaryResponse = curl_exec($ch2);
    $secondaryHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

    Log::info("Secondary HTTP Status Code: $secondaryHttpCode");
    Log::info("Secondary API Response: $secondaryResponse");

    if (curl_errno($ch2)) {
        Log::error("cURL Error for secondary request: " . curl_error($ch2));
    }
    curl_close($ch2);

    // Return the response
    // Return the response and HTTP status code
    return [
        'body' => $response,
        'status_code' => $httpCode,
        'status' => $httpCode >= 200 && $httpCode < 300,
    ];
    }




    public function sendSingleSms(array $smsData)
    {
        // Log the initial data before processing
        Log::info("Starting SMS send process with data:", $smsData);

        if (empty($smsData['text'])) {
            $savedRecord = Sms::find($smsData['request_id']);

            if (!$savedRecord) {
                Log::error("Could not retrieve SMS record after save for ID {$smsData['request_id']}");
                return [
                    'status' => false,
                    'error' => 'Failed to retrieve saved SMS record.',
                ];
            }

            // Assign variables from saved record
            $accountNumber = $savedRecord->account_number;
            $date = $savedRecord->date;
            $accountNumber = $this->maskAccountNumber($accountNumber);

            // Ensure formatCustomMessage is a valid method
            $smsData['text'] = $this->formatCustomMessage($smsData['receiver'], $accountNumber, $date);
        }

        $apiUrl = 'https://ubasms.approot.ng/php/bulksms.php?' .
            http_build_query([
                'receiver' => $smsData['receiver'],
                'text' => $smsData['text'],
                'sender' => $smsData['sender'],
                'request_id' => $smsData['request_id'],
            ]);

        // Log the generated API URL
        Log::info("Generated API URL: $apiUrl");

        // Initialize cURL
        $ch = curl_init($apiUrl);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // Execute cURL request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Log the HTTP status code and response for debugging
        Log::info("HTTP Status Code: $httpCode");
        Log::info("Raw API Response: $response");

        // Check for cURL errors
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            Log::error("cURL Error for SMS request_id {$smsData['request_id']}: " . $error_msg);
        } else {
            // Log the response
            Log::info("API Response for SMS request_id {$smsData['request_id']}: " . $response);
        }

        // Extract status code and message from the response
        $statusMessage = trim($response);
        $parts = explode(':', $statusMessage);

        // Log the response parsing attempt
        Log::info("Parsed response parts:", $parts);

        // Initialize variables for status code and message
        $statusCode = null;
        $message = null;

        // Check if parts have at least two elements
        if (count($parts) === 3) {
            $statusCode = trim($parts[1]); // Get the status code (first part)
            $message = trim($parts[2]);    // Get the message (second part)
        }

        // Store response in database
        $updateData = [
            'status_code' => $statusCode,
            'api_message' => $message,
            'updated_at' => now(),
        ];

        // Attempt to update the SMS record and log the result
        try {
            Sms::where('id', $smsData['request_id'])->update($updateData);
            Log::info("Database updated successfully for request_id {$smsData['request_id']}");
        } catch (\Exception $e) {
            Log::error("Database update failed for request_id {$smsData['request_id']}: " . $e->getMessage());
            return [
                'status' => false,
                'error' => "Database update error: " . $e->getMessage(),
            ];
        }

        Log::info("API Response for SMS request_id {$smsData['request_id']}: the status code: {$statusCode} : message: {$message}");

        // Prepare webhook data
        $webhookData = [
            'receiver' => $smsData['receiver'],
            'sender' => $smsData['sender'],
            'request_id' => $smsData['request_id'],
        ];

        $webhookUrl = 'http://127.0.0.1:8000/api/handlewebook';

        Log::info("Webhook URL generated: $webhookUrl");

        // Dispatch the webhook request asynchronously
        dispatch(function () use ($webhookUrl, $webhookData) {
            try {
                $response = Http::timeout(10)
                    ->retry(3, 100)
                    ->post($webhookUrl, $webhookData);

                if ($response->successful()) {
                    Log::info("Webhook delivered successfully", [
                        'request_id' => $webhookData['request_id']
                    ]);
                } else {
                    Log::error("Webhook request failed", [
                        'status' => $response->status(),
                        'response' => $response->body(),
                        'request_id' => $webhookData['request_id']
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Webhook delivery failed: " . $e->getMessage(), [
                    'webhook_url' => $webhookUrl,
                    'request_id' => $webhookData['request_id']
                ]);
            }
        })->afterResponse();

        // Close the cURL session
        curl_close($ch);

        // Return the response
        return [
            'body' => $response,
            'status_code' => $httpCode,
            'status' => $httpCode >= 200 && $httpCode < 300,
        ];
    }



public function saveSingleToDatabase(array $data, $isCustomMessage)
{
    try {
        $firstName = $data['first_name']; // Ensure key matches your input
        $lastName = $data['last_name'] ?? ''; // Default to empty if not provided
        $phoneNumber = $data['phone_number'];
        $accountNumber = $data['account_number'];
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
            'account_number'=>$accountNumber
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
        return null; // Return null to indicate failure
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
    if ($response['status']) {
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
