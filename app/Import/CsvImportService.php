<?php

namespace App\Import;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CsvImportService
{
    protected $modelClass;
    protected $apiUrl; // External API endpoint
    protected $chunkSize = 1000; // Number of rows to insert at once
    protected $header = [];

    public function __construct($modelClass, $apiUrl)
    {
        $this->modelClass = $modelClass;
        $this->apiUrl = $apiUrl;
    }

    public function import($filePath)
    {
        // Get the table name from the model class
        $model = new $this->modelClass;
        $tableName = $model->getTable();

        // Get the column names from the database table
        $tableColumns = Schema::getColumnListing($tableName);

        // Open the CSV file for reading
        if (($handle = fopen($filePath, 'r')) !== false) {
            $batch = [];
            $rowCount = 0;

            // Loop through the CSV rows
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (empty($this->header)) {
                    // Store the header row
                    $this->header = $row;

                    // Validate the header against the table columns
                    $invalidColumns = $this->validateHeader($this->header, $tableColumns);

                    if (!empty($invalidColumns)) {
                        // Log and fail the process if there are invalid columns
                        Log::error('Invalid CSV columns detected: ' . implode(', ', $invalidColumns));
                        fclose($handle); // Close the file
                        return false;
                    }

                    continue;
                }

                // Combine header and row data
                $rowData = array_combine($this->header, $row);

                // Filter data to match only columns that exist in the table
                $filteredData = array_intersect_key($rowData, array_flip($tableColumns));

                // Add filtered data to the batch
                $batch[] = $filteredData;
                $rowCount++;

                // Process in chunks
                if ($rowCount % $this->chunkSize === 0) {
                    $this->processBatch($batch);
                    $batch = []; // Clear the batch to free memory
                    $this->clearMemory(); // Optimize memory usage
                }
            }

            // Process any remaining data
            if (count($batch) > 0) {
                $this->processBatch($batch);
            }

            fclose($handle); // Close the file handle

            return true;
        }

        Log::error('Unable to open the file for import.');
        return false;
    }

    protected function validateHeader(array $header, array $tableColumns)
    {
        // Find columns in the CSV header that don't exist in the table
        return array_diff($header, $tableColumns);
    }

    protected function processBatch(array $batch)
    {
        // Send data to external API and insert response into the database
        $apiResponse = $this->sendToApi($batch);

        if ($apiResponse['status'] === 'success') {
            $this->insertDataIntoDatabase($batch, $apiResponse['response']);
        } else {
            Log::error('Error sending data to API: ' . $apiResponse['error']);
        }
    }

    protected function sendToApi(array $batch)
    {
        try {
            // Send HTTP POST request to the external API
            $response = Http::post($this->apiUrl, ['data' => $batch]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'response' => $response->json(), // Capture API response
                ];
            }

            return [
                'status' => 'error',
                'error' => $response->body(), // Capture API error
            ];
        } catch (\Exception $e) {
            Log::error('Error connecting to API: ' . $e->getMessage());

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function insertDataIntoDatabase(array $batch, $apiResponse)
    {
        DB::beginTransaction();

        try {
            $modelClass = $this->modelClass;

            foreach ($batch as $index => $row) {
                // Insert row into the database along with API response data
                $modelClass::create(array_merge($row, [
                    'api_request_data' => json_encode($row), // Store the request data
                    'api_response_data' => json_encode($apiResponse[$index] ?? []), // Store the API response
                ]));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error inserting data into the database: ' . $e->getMessage());
        }
    }

    protected function clearMemory()
    {
        // Clear unused variables and free up memory
        gc_collect_cycles(); // Explicit garbage collection
    }
}
