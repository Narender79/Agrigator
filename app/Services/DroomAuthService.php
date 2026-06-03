<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis as LRedis;
use Exception;

class DroomAuthService
{
    /**
     * Get the OAuth Token from Redis cache or generate a new one.
     *
     * @param bool $forceRefresh
     * @return array
     */
    public function getOAuthToken(bool $forceRefresh = false): array
    {
        try {
            $redis = LRedis::connection();

            // Get credentials from config
            $clientId = config('services.droom.oauth_client_id', '');
            $clientSecret = config('services.droom.oauth_client_secret', '');
            $username = config('services.droom.oauth_username', '');
            $password = config('services.droom.oauth_password', '');
            $baseUrl = config('services.droom.gateway_url', '');

            $cacheKey = 'dss_oauth_token';

            // Check Redis cache first if not forcing refresh
            if (!$forceRefresh) {
                $cachedToken = $redis->get($cacheKey);
                if (!empty($cachedToken)) {
                    return ['code' => 'success', 'data' => ['token' => $cachedToken]];
                }
            }

            Log::info('Generating new OAuth token', ['force_refresh' => $forceRefresh]);

            $url = !empty($baseUrl) ? rtrim($baseUrl, '/') . '/dss/v1/oauth/token' : '';

            if (empty($url) || empty($clientId) || empty($clientSecret) || empty($username) || empty($password)) {
                return ['code' => 'failed', 'errors' => ['OAuth configuration missing'], 'data' => ['token' => null]];
            }

            $postData = [
                'grant_type' => 'password',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => '*',
                'username' => $username,
                'password' => $password
            ];
            // Make POST request using Laravel's standard Http client
            $response = Http::asForm()
                ->timeout(30)
                ->post($url, $postData);
            if (!$response->successful()) {
                Log::error('OAuth token request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['code' => 'failed', 'errors' => ['Failed to fetch OAuth token'], 'data' => ['token' => null]];
            }

            $tokenResponse = $response->json();
            if ($tokenResponse === null) {
                return ['code' => 'failed', 'errors' => ['Invalid OAuth token response'], 'data' => ['token' => null]];
            }

            $accessToken = $tokenResponse['access_token'] ?? null;
            $expiresIn = $tokenResponse['expires_in'] ?? 3600;

            if (empty($accessToken)) {
                return ['code' => 'failed', 'errors' => ['Missing access token'], 'data' => ['token' => null]];
            }

            // Cache the token in Redis with expiration
            $redis->set($cacheKey, $accessToken);
            $redis->expire($cacheKey, max(1, $expiresIn - 60));

            Log::info('OAuth token obtained and cached', ['expires_in' => $expiresIn]);
            return ['code' => 'success', 'data' => ['token' => $accessToken]];

        } catch (Exception $ex) {
            Log::error('Exception in getOAuthToken', [
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);
            return ['code' => 'failed', 'errors' => [$ex->getMessage()], 'data' => ['token' => null]];
        }
    }
}
