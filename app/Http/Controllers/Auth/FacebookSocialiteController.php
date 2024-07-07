<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\FacebookProvider;

class FacebookSocialiteController extends Controller
{
    /**
     * Handle Facebook login callback
     */
    public function handleCallback(Request $request)
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        try {
            // Retrieve the access token from the request
            $accessToken = $request->input('access_token');
            Log::info('Received access token: ' . $accessToken);

            // Use the access token to get the Facebook user
            $facebookUser = Socialite::driver('facebook')->userFromToken($accessToken);
            Log::info('Facebook user: ' . json_encode($facebookUser));

            // Check if the email is verified
            if (!$facebookUser->email) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email not verified on Facebook.'
                ], 400);
            }

            // Check if the user already exists
            $existingUser = User::where('social_id', $facebookUser->id)->first();

            if ($existingUser) {
                // User found, generate token
                $tokenResult = $existingUser->createToken('authToken')->accessToken;
                return response()->json([
                    'status' => 'success',
                    'user' => $existingUser,
                    'token' => $tokenResult
                ], 200);
            } else {
                // User not found, create new user
                $newUser = User::create([
                    'name' => $facebookUser->name,
                    'email' => $facebookUser->email,
                    'social_id' => $facebookUser->id,
                    'social_type' => 'facebook',
                    'password' => Hash::make(uniqid()) // Generate a random password
                ]);

                $tokenResult = $newUser->createToken('authToken')->accessToken;
                return response()->json([
                    'status' => 'success',
                    'user' => $newUser,
                    'token' => $tokenResult
                ], 200);
            }
        } catch (Exception $e) {
            Log::error('Error in handleCallback: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to authenticate user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
