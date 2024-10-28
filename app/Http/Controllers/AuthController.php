<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    //

    // Show the login form
    public function showLoginForm()
    {
        return view('auth.login'); // Adjust the path as necessary
    }
    

     // Handle the login request
     public function login(Request $request)
     {
         $request->validate([
             'email' => 'required|email',
             'password' => 'required',
         ]);


           // Prepare the credentials
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
            'device_info' => '45gg',
        ];


         // Send the credentials to the external API
         $response = Http::post('https://www.api.ringo.ng/api/appmidLogininin', $credentials);

         // Log the response
         Log::info('API Response', ['status' => $response->status(), 'body' => $response->body()]);


         if ($response->successful()) {
            // Assuming the response contains user information, extract it
            $userData = $response->json();

            // Return a success response with user data
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => $userData,
            ]);
        }


          // Handle authentication failure
          Log::warning('Authentication failed', ['response' => $response->body()]);
          return response()->json([
            'success' => false,
            'message' => 'Authentication failed with the external API.',
        ], 401); // 401 Unauthorized

         // Attempt to log the user in
        //  if (Auth::attempt($request->only('email', 'password'))) {
        //      // Redirect to the intended page after login
        //      //return redirect()->intended('dashboard');
        //      $user = Auth::user();

        //        // Return a JSON response
        //     return response()->json([
        //         'success' => true,
        //         'message' => 'Login successful',
        //         'user' => $user, // Optionally include user data
        //     ]);
        //  }

         // Return back with an error message
        //  return back()->withErrors([
        //      'email' => 'The provided credentials do not match our records.',
        //  ]);
     }
}
