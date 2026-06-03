<?php

namespace App\Http\Controllers;

use App\Models\Insurance\InsurerIntegration;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

abstract class Controller
{
    protected function resolveInsurerAccessToken(int $insurerId): string
    {
        $integration = InsurerIntegration::query()
            ->where('insurer_id', $insurerId)
            ->whereNull('deleted_at')
            ->first();

        if (! $integration) {
            throw new RuntimeException(sprintf('Insurer integration not found for insurer ID %d.', $insurerId));
        }

        return $this->resolveInsurerAccessTokenFromIntegration($integration);
    }

    protected function resolveInsurerAccessTokenFromIntegration(InsurerIntegration $integration): string
    {
        $insurerId = $integration->insurer_id;

        return match ($integration->auth_type) {
            'OAUTH2' => $this->resolveOauth2InsurerAccessToken($integration),
            'BASIC_AUTH', 'API_KEY' => throw new RuntimeException(sprintf(
                'Auth type %s is not implemented yet for insurer ID %d.',
                (string) $integration->auth_type,
                $insurerId
            )),
            default => throw new RuntimeException(sprintf(
                'Unsupported auth type %s for insurer ID %d.',
                (string) $integration->auth_type,
                $insurerId
            )),
        };
    }

    protected function resolveOauth2InsurerAccessToken(InsurerIntegration $integration): string
    {
        $otherAttributes = $this->normalizeOtherAttributes($integration->other_attributes);
        $cacheKey = (string) Arr::get($otherAttributes, 'cache_key', sprintf('insurer:%d:oauth2:access_token', $integration->insurer_id));
        $expiryBufferSeconds = (int) Arr::get($otherAttributes, 'expiry_buffer_seconds', 60);

        if ($cacheKey === '') {
            throw new InvalidArgumentException('Token cache key is required.');
        }

        $cachedToken = Cache::store('redis')->get($cacheKey);
        if (is_array($cachedToken) && !empty($cachedToken['access_token'])) {
            return (string) $cachedToken['access_token'];
        }

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $tokenUrl = (string) Arr::get($otherAttributes, 'token_url', $integration->api_base_url);
        $clientId = $this->decryptConfiguredValue($integration->client_id);
        $clientSecret = $this->decryptConfiguredValue($integration->client_secret);

        if ($tokenUrl === '' || $clientId === '' || $clientSecret === '') {
            throw new InvalidArgumentException(sprintf(
                'OAuth2 token URL, client ID, and client secret are required for insurer ID %d.',
                $integration->insurer_id
            ));
        }

        $tokenResponse = $this->requestOAuthClientCredentialsToken([
            'token_url' => $tokenUrl,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => Arr::get($otherAttributes, 'grant_type', 'client_credentials'),
            'scope' => Arr::get($otherAttributes, 'scope'),
            'timeout' => Arr::get($otherAttributes, 'timeout', 15),
        ]);
        $accessToken = (string) Arr::get($tokenResponse, 'access_token', '');

        if ($accessToken === '') {
            throw new RuntimeException('OAuth token response did not include an access token.');
        }

        $expiresIn = max(1, (int) Arr::get($tokenResponse, 'expires_in', 1800));
        $ttlSeconds = max(60, $expiresIn - max(1, $expiryBufferSeconds));

        Cache::store('redis')->put($cacheKey, [
            'access_token' => $accessToken,
            'token_type' => Arr::get($tokenResponse, 'token_type', 'Bearer'),
            'expires_in' => $expiresIn,
            'cached_at' => now()->toIso8601String(),
            'expires_at' => now()->addSeconds($ttlSeconds)->toIso8601String(),
        ], now()->addSeconds($ttlSeconds));

        return $accessToken;
    }

    protected function normalizeOtherAttributes(mixed $otherAttributes): array
    {
        if (is_array($otherAttributes)) {
            return $otherAttributes;
        }

        if (is_string($otherAttributes) && $otherAttributes !== '') {
            $decoded = json_decode($otherAttributes, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function decryptConfiguredValue(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    protected function requestOAuthClientCredentialsToken(array $authConfig): array
    {
        $tokenUrl = (string) Arr::get($authConfig, 'token_url', '');
        $clientId = (string) Arr::get($authConfig, 'client_id', '');
        $clientSecret = (string) Arr::get($authConfig, 'client_secret', '');

        if ($tokenUrl === '' || $clientId === '' || $clientSecret === '') {
            throw new InvalidArgumentException('OAuth token URL, client ID, and client secret are required.');
        }

        $payload = array_filter([
            'grant_type' => Arr::get($authConfig, 'grant_type', 'client_credentials'),
            'scope' => Arr::get($authConfig, 'scope'),
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ], static fn ($value) => $value !== null && $value !== '');

        $response = Http::asForm()
            ->acceptJson()
            ->timeout((int) Arr::get($authConfig, 'timeout', 15))
            ->post($tokenUrl, $payload);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'OAuth token request failed with status %s.',
                $response->status()
            ));
        }

        $tokenData = $response->json();

        if (! is_array($tokenData)) {
            throw new RuntimeException('OAuth token response was not valid JSON.');
        }

        return $tokenData;
    }
}
