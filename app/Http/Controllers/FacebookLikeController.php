<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;


class FacebookLikeController extends Controller
{
    //

    public function likePost(Request $request){
        $request->validate([
            'post_url' => 'required|url',
        ]);

        // Extract the Facebook post ID from the URL
        $postUrl = $request->input('post_url');
        $facebookPostId = $this->extractPostIdFromUrl($postUrl);

        if (!$facebookPostId) {
            return response()->json(['status' => 'error', 'message' => 'Invalid Facebook post URL.'], 400);
        }

        // Retrieve up to 15 users with valid Facebook tokens
        $users = User::whereNotNull('facebook_token')
            ->inRandomOrder()
            ->take(15)
            ->get();

        if ($users->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No users with valid Facebook tokens found.'], 400);
        }

        $successfulLikes = 0;
        $failedLikes = 0;

        foreach ($users as $user) {
            try {
                $response = Http::post("https://graph.facebook.com/{$facebookPostId}/likes", [
                    'access_token' => $user->facebook_token,
                ]);

                if ($response->successful()) {
                    $successfulLikes++;
                } else {
                    $failedLikes++;
                }
            } catch (Exception $e) {
                $failedLikes++;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Liking process completed.',
            'successful_likes' => $successfulLikes,
            'failed_likes' => $failedLikes,
        ]);
    }

    private function extractPostIdFromUrl($url){
        // Extract the Facebook post ID from the URL using a regex pattern
        // This example assumes a common pattern for Facebook post URLs.
        // You may need to adjust this regex based on the actual URL structure.
        preg_match('/\/posts\/(\d+)/', $url, $matches);
        return $matches[1] ?? null;
    }
}
