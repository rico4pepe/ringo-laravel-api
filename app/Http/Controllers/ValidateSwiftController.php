<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\PlanService;

class ValidateSwiftController extends Controller
{


    protected $planService;

    // Inject the PlanService
    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }
    //

    public function validateSwift(Request $request)
    {


        //      dd("welcome"); 
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required',  // Assuming you have a 'customers' table
        ]);

        if ($validator->fails()) {
            // Return validation errors if the input is invalid
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 400);
        }

        $customerId = $request->input('customer_id');

        // Step 3: Set up the cURL request to the external endpoint
        $url = "http://10.142.0.2/billers/billers-new/swift/verify.php?customer_id=$customerId";  // Replace with your actual endpoint
        $ch = curl_init();  // Initialize cURL session

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);  // Set the URL to send the request to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // Return the response as a string
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Follow redirects if needed
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // Optionally skip SSL certificate verification (not recommended for production)
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);  // Set a timeout limit for the request

        // Step 4: Execute the cURL request
        $response = curl_exec($ch);
	if (curl_errno($ch)) {
            $errorMessage = curl_error($ch);
            curl_close($ch);  // Close the cURL session

            return response()->json([
                'status' => '300',
                'message' => 'Failed',
            ]);
        }

        // Get HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);  // Close the cURL session

        // Step 5: Handle and return the response
        if ($httpCode === 200) {

            $xmlResponse = simplexml_load_string($response);

            if ($xmlResponse === false) {
                return response()->json([
                    'status' => '300',
                    'message' => 'Failed',
                ]);
            }

            $responseArray = json_decode(json_encode($xmlResponse), true);

            // Extract relevant data
            $statusCode = $responseArray['Customer']['StatusCode'] ?? null;
            $statusDescription = $responseArray['Customer']['StatusDescription'] ?? null;
            $customerId = $responseArray['Customer']['CustomerId'] ?? null;
            $firstName = $responseArray['Customer']['FirstName'] ?? null;
            $lastName = $responseArray['Customer']['LastName'] ?? null;

            if ($statusCode == 0) {

                 // Use the plan service to get the data
                 $plans = $this->planService->getPlans();

                return response()->json([
                    'status' => '200',
                    'customer_id' => $customerId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'status_code' => $statusCode,
		       'status_description' => $statusDescription,
               'plans' => $plans,  // Data from PlanService
                ]);
            } else {
                // Handle invalid customer
                return response()->json([
                    'status' => '300',
                    'message' => $statusDescription,
                ]);
            }
        } else {
            return response()->json([
                'status' => '300',
                'message' => 'Failed to validate customer',
            ]);
        }
    }
}
