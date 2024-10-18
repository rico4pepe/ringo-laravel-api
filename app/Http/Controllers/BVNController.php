<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Bvn\BVNVerificationService;
use App\Models\User;
use App\Models\BvnVerification;
use Illuminate\Support\Facades\DB;

class BVNController extends Controller
{
    //

    protected $bvnService;

    public function __construct(BVNVerificationService $bvnService)
    {
        $this->bvnService = $bvnService;
    }

    public function verify($userId, Request $request)
    {
        // Validate input (ensure BVN is passed in the request)
        $request->validate([
            'bvn' => 'required|digits:11',  // Assuming BVN is 11 digits
        ]);

        // Fetch user
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

         // Capture the BVN from the request
         $bvn = $request->input('bvn');

        // Call the BVN verification service
        $bvnData = $this->bvnService->verifyBVN($bvn);

        if (!$bvnData) {
            return response()->json(['error' => 'BVN verification failed'], 500);
        }

        // Validate if the BVN data matches the user's credentials
        $bvnMatch = $bvnData['FirstName'] === $user->firstname &&
                    $bvnData['LastName'] === $user->lastname &&
                    $bvnData['BVN'] === $bvn;  

        if ($bvnMatch) {
            // Use DB transaction to update both tables atomically
            DB::transaction(function () use ($user, $bvnData) {
                // Update user table - mark BVN as verified and store BVN number
                $user->update([
                    'bvn_verified' => 1, // Assuming true means verified
                    //'bvn' => $bvnData['BVN'],
                ]);

                // Update bvn_verification_table
                BvnVerification::create([
                    'user_id' => $user->id,
                    'first_name' => $bvnData['FirstName'],
                    'middlename' => $bvnData['MiddleName'],
                    'lastname' => $bvnData['LastName'],
                    'bvn' => $bvnData['BVN'],
                    'status' => 1,
                ]);
            });

            return response()->json(['message' => 'BVN verified and records updated successfully']);
        }

        return response()->json(['error' => 'BVN data does not match user credentials'], 400);
    }
}
