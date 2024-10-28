<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Otp;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class LoginController extends Controller
{
    public function signin(Request $request)
    {
        // Validate request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        // Check user credentials
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Clear any existing or expired OTPs for this user
            Otp::where('user_id', $user->id)->delete();

            try {
                // Generate OTP and expiry
                $otp = random_int(100000, 999999);
                $expiry = now()->addMinutes(5);

                // Create new OTP
                Otp::create([
                    'user_id' => $user->id,
                    'otp_code' => $otp,
                    'expires_at' => $expiry,
                ]);

                // Send OTP via email
                Mail::to($user->email)->send(new OtpMail($otp));

                Log::info("OTP sent to user ID {$user->id}");

                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent to your email.',
                    'otp_required' => true,
                ]);

            } catch (Exception $e) {
                Log::error("Error sending OTP email to user ID {$user->id}: " . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP. Please try again later.',
                ], 500);
            }
        }

        return response()->json(['success' => false, 'message' => 'Login failed.'], 401);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $otp = Otp::where('user_id', $user->id)
            ->where('otp_code', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 400);
        }

        // Delete used OTP
        $otp->delete();

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Delete existing or expired OTPs for this user
        Otp::where('user_id', $user->id)->delete();

        try {
            // Create new OTP
            $otp = Otp::create([
                'user_id' => $user->id,
                'otp_code' => random_int(100000, 999999),
                'expires_at' => now()->addMinutes(5),
            ]);

            // Send new OTP
            Mail::to($user->email)->send(new OtpMail($otp->otp_code));

            Log::info("Resent OTP to user ID {$user->id}");

            return response()->json([
                'success' => true,
                'message' => 'New OTP sent to your email',
            ]);

        } catch (Exception $e) {
            Log::error("Error resending OTP email to user ID {$user->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend OTP. Please try again later.',
            ], 500);
        }
    }
}
