<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ElectricityPaymentController extends Controller
{
    //
    public function makeElectricityPayment(Request $request){

         // Validate the incoming request
         $validatedData = $request->validate([
            'product_id' => 'required',
            'mobile' => 'required',
            'amount' => 'required|numeric',
            'params.opt3' => 'required',   // Params like meter/account number
        ]);


          // Generate a unique 16-digit reference number
          $reference = $this->generateEncryptedReference();

          // Prepare the request payload
          $requestPayload = [
              'reference' => $reference,
              'request' => json_encode([
                  [
                      'product_id' => $validatedData['product_id'],
                      'mobile' => $validatedData['mobile'],
                      'amount' => $validatedData['amount'],
                      'params' => [
                          'opt3' => $validatedData['params']['opt3']
                      ],
                      'plan_params' => ''
                  ]
              ])
          ];


            // Set up the headers for the external API request
        $headers = [
            'token' => '6234bb3a-1138-4bb9-9825-4c63ac10',
            'authtoken' => 'Elg2P0hV8thhGRPQsBj5/ijF4xdnpr1xLh/atAFr9JPToynvl2/QYYivHz763zTUwJJsYFSt+u64JqRC61H/qA==',
        ];


        // Make the request to the external API
        $response = Http::withHeaders($headers)
            ->asForm()
            ->post('https://api.onecardnigeria.com/rest/doPayment', $requestPayload);

        // Check if the request was successful
        if ($response->successful()) {
            return response()->json($response->json(), 200);
        } else {
            return response()->json(['error' => 'External API request failed', 'details' => $response->json()], $response->status());
        }





    }


    private function generateEncryptedReference()
    {
        // Generate a unique 16-digit number
        $reference = Str::random(length: 16);

        // In a real-world scenario, you would encrypt this reference
        // For this example, we'll just return the plain reference
        // You should implement proper encryption based on your requirements
        return $reference;
    }
}
