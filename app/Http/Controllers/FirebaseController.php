<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shared\Notification\TopicSubscribeRequest;
use App\Http\Requests\Shared\Notification\WebTokenSaveRequest;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebaseController extends Controller
{
    private const SUBSCRIBED_RESULT = 'OK';

    public function subscribeToTopic(TopicSubscribeRequest $request): JsonResponse
    {
        $token = $request->input('token');
        $topics = $request->getTopics();

        $customer = Auth::guard('customer')->user();
        if ($customer && $customer->cm_firebase_token_web !== $token) {
            User::where('id', $customer->id)->update(['cm_firebase_token_web' => $token]);
        }

        try {
            $results = app('firebase.messaging')->subscribeToTopics($topics, $token);
        } catch (Throwable $exception) {
            Log::warning('FCM topic subscription failed', ['topics' => $topics, 'error' => $exception->getMessage()]);

            return response()->json(['message' => 'Topic subscription failed'], 500);
        }

        $failures = $this->failedTopicSubscriptions(results: $results);

        if ($failures !== []) {
            Log::warning('FCM topic subscription rejected', ['failures' => $failures]);

            return response()->json(['message' => 'Topic subscription failed', 'errors' => $failures], 422);
        }

        return response()->json(['message' => 'Successfully subscribed to topic']);
    }

    public function saveSellerWebToken(WebTokenSaveRequest $request): JsonResponse
    {
        $seller = Auth::guard('seller')->user();
        if ($seller) {
            Seller::where('id', $seller->id)->update(['cm_firebase_token_web' => $request->input('token')]);
        }

        return response()->json(['message' => 'Token saved'], 200);
    }

    /** @return array<string, string> */
    private function failedTopicSubscriptions(array $results): array
    {
        $failures = [];

        foreach ($results as $topic => $topicResult) {
            if (is_string($topicResult)) {
                $failures[(string) $topic] = $topicResult;

                continue;
            }

            foreach ((array) $topicResult as $tokenResult) {
                if ($tokenResult !== self::SUBSCRIBED_RESULT) {
                    $failures[(string) $topic] = (string) $tokenResult;
                }
            }
        }

        return $failures;
    }
}
