<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Import\CsvImportService;
use App\Models\CsvImportLog; // target model
use App\Models\User; 
use Illuminate\Support\Facades\Validator;



class CsvImportController extends Controller
{
    //
    public function upload(Request $request)
    {  
          

        // Validate the file upload request
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => CsvImportLog::STATUS_FAILED,
                'message' => 'Invalid file type. Please upload a valid CSV file.',
                'errors' => $validator->errors()
            ], 400);
        }

        // Handle the file upload
        if ($request->hasFile('csv_file')) {
            $file = $request->file('csv_file');
            $filePath = $file->getRealPath(); // Get the file path

            //  // Simulate the pending status before starting
            //  $response = [
            //     'status' => 0, // Pending
            //     'message' => 'File upload received, processing started.',
            // ];

            // External API URL (modify to your target API endpoint)
            $apiUrl = 'https://example.com/api/endpoint'; // Replace with the actual API URL


             // Create a new log entry for the import
             $csvImportLog = CsvImportLog::create([
                'api_request_data' => ['file_name' => $file->getClientOriginalName()],
                'status' => CsvImportLog::STATUS_PENDING,
            ]);

            // Call the import service and pass the file and API URL
            $importService = new CsvImportService(CsvImportLog::class, $apiUrl); // Pass the target model here (e.g., User::class)
            
            // Mark as processing before calling the service
            $csvImportLog->status = CsvImportLog::STATUS_PROCESSING;
            $csvImportLog->save(); // Update status to "processing"

            $result = $importService->import($filePath);

            if ($result) {
                // If successful
                  $csvImportLog->status = CsvImportLog::STATUS_SUCCESS;
                $csvImportLog->save(); // Mark as success
                return response()->json([
                    'status' => CsvImportLog::STATUS_SUCCESS,
                    'message' => 'CSV file successfully processed and sent to API.'
                ], 200);
            } else {
                // If failed
                $csvImportLog->status = CsvImportLog::STATUS_FAILED;
                $csvImportLog->save(); // Mark as failed
                return response()->json([
                    'status' => CsvImportLog::STATUS_FAILED,
                    'message' => 'Failed to process the CSV file.'
                ], 500);
            }
        }

        return response()->json([
            'status' => 2, // Failed
            'message' => 'No file uploaded.'
        ], 400);
    }
}
