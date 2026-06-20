<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiLog;
use App\Models\Store;
use App\Models\User;

class LogApiRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        try {
            $duration = round((microtime(true) - $startTime) * 1000);

            // Determine user_id and store_id from API key
            $userId = null;
            $storeId = null;

            $apiKey = $request->header('X-API-KEY') ?? $request->input('api_key');

            if ($apiKey) {
                // Check if it's a store key (starts with st_ or store_key_)
                $store = Store::where('api_key', $apiKey)->first();
                if ($store) {
                    $storeId = $store->id;
                    $userId = $store->user_id;
                } else {
                    // Check if it's a user/merchant key
                    $user = User::where('api_key', $apiKey)->first();
                    if ($user) {
                        $userId = $user->id;
                    }
                }
            }

            // Clean headers (remove sensitive ones if needed)
            $headers = $request->headers->all();
            unset($headers['authorization']);

            // Get response content
            $resContent = $response->getContent();
            $decodedResponse = json_decode($resContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decodedResponse = ['raw' => substr($resContent, 0, 1000)];
            }

            // Create Log entry
            ApiLog::create([
                'user_id' => $userId,
                'store_id' => $storeId,
                'method' => $request->method(),
                'url' => $request->getRequestUri(),
                'ip_address' => $request->ip(),
                'request_headers' => $headers,
                'request_body' => $request->all(),
                'response_status' => $response->getStatusCode(),
                'response_body' => $decodedResponse,
                'duration' => $duration,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('API Logger error: ' . $e->getMessage());
        }

        return $response;
    }
}
