<?php


namespace App\Bvn;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BVNVerificationService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'http://169.255.124.242/KYCAPI'; // Replace IP_ADDRESS with the actual IP
    }


     /**
     * Retrieve API authorization token.
     */
    public function getToken()
    {

         // Check if the token is already cached
         if (Cache::has('authorization_token')) {
            return Cache::get('authorization_token');
        }
         // If the token is not cached, request a new one from the /token endpoint
        $response = Http::post("{$this->baseUrl}/token");

       // dd($response);

       //dd($response->headers());

        if ($response->successful()) {
            // The token is retrieved from the response header
            $token = $response->header('Authorization-Token');

            if($token){
                // Cache the token for 24 hours (1440 minutes)
                Cache::put('authorization_token', $token, now()->addMinutes(1440));
                return $token;
            }
        }

        return false;  // Token retrieval failed
    }

     /**
     * Verify BVN using the retrieved authorization token.
     */
    public function verifyBVN($bvn)
    {
        // Call getToken() to retrieve the authorization token
        $token = $this->getToken();

        if (!$token) {
            return false;  // Token retrieval failed
        }

        // Make a request to the BVN verification endpoint with the token
        $response = Http::withHeaders([
            'Authorization' => $token,
        ])->post("{$this->baseUrl}/bvn/validation", [
            'bvn' => $bvn,
        ]);

        if ($response->successful()) {
            return $response->json();  // Return the BVN data
        }

        return false;  // API request failed
    }


}