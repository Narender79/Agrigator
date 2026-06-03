<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Insurance\InsurerIntegration;
use Illuminate\Support\Facades\Crypt;

class TataAigMasterService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $clientId;
    protected string $clientSecret;
    protected string $productCode;
    protected string $tokenUrl;
    protected string $scope;
    protected int $timeout;
    protected int $insurerId;
    protected string $cacheKey;
    protected int $expiryBufferSeconds;
    protected ?string $accessToken = null;

    public function __construct()
    {
        $currentEnv = config('app.env', 'uat');

        // 1. Try to find the record matching the current environment
        $integration = InsurerIntegration::where('insurer_id', 1)
            ->where('environment', $currentEnv)
            ->first();

        // 2. Fallback to any first matching insurer_id = 1 configuration if environment is different (e.g. local)
        if (!$integration) {
            $integration = InsurerIntegration::where('insurer_id', 1)->first();
        }

        if ($integration) {
            $this->insurerId = $integration->insurer_id;
            // Decrypt standard database columns
            $this->clientId = Crypt::decryptString($integration->client_id);
            $this->clientSecret = Crypt::decryptString($integration->client_secret);

            // Read custom values from dynamic JSON attributes
            $attrs = $integration->other_attributes ?? [];
            $this->tokenUrl = $attrs['token_url'] ?? $integration->api_base_url;
            $this->scope = $attrs['scope'] ?? 'https://api.iorta.in/write';
            $this->timeout = (int) ($attrs['timeout'] ?? 15);

            // Cache attributes
            $this->cacheKey = $attrs['cache_key'] ?? sprintf('insurer:%d:oauth2:access_token', $this->insurerId);
            $this->expiryBufferSeconds = (int) ($attrs['expiry_buffer_seconds'] ?? 60);

            // Read Resource API endpoint and API Key dynamically from DB, fallback to .env via config()
            $this->baseUrl = $attrs['resource_base_url'] ?? config('services.tata_aig.base_url', '');
            $this->apiKey = $attrs['api_key'] ?? config('services.tata_aig.api_key', '');
            $this->productCode = $attrs['product_code'] ?? config('services.tata_aig.product_code', '');
        } else {
            // 3. Complete fallback — read everything from .env via config() (no hardcoded secrets!)
            $this->insurerId = 1;
            $this->clientId = (string) config('services.tata_aig.client_id', '');
            $this->clientSecret = (string) config('services.tata_aig.client_secret', '');
            $this->tokenUrl = config('services.tata_aig.base_url', '') . '/access-gateway/oauth2/token';
            $this->scope = 'https://api.iorta.in/write';
            $this->timeout = 15;
            $this->baseUrl = (string) config('services.tata_aig.base_url', '');
            $this->apiKey = (string) config('services.tata_aig.api_key', '');
            $this->productCode = (string) config('services.tata_aig.product_code', '');
            $this->cacheKey = 'insurer:1:oauth2:access_token';
            $this->expiryBufferSeconds = 60;
        }
    }

    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }
        $response = Http::asForm()
            ->timeout($this->timeout)
            ->post($this->tokenUrl, [
                'grant_type'    => 'client_credentials',
                'scope'         => $this->scope,
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);
        if ($response->failed()) {
            Log::error('Tata AIG DB-backed auth failed', [
                'url'    => $this->tokenUrl,
                'status' => $response->status(),
                'body'   => $response->body()
            ]);
        }
        $data = $response->json();
        $this->accessToken = $data['access_token'] ?? null;
        if (!$this->accessToken) {
            throw new \Exception('No access token found in Tata AIG response.');
        }
        return $this->accessToken;
    }

    /**
     * Send quote request to Tata AIG Quote API.
     *
     * @param array $payload
     * @return array
     */

    public function fetchQuote(array $payload)
    {
        $token = $this->getAccessToken();

        $url = rtrim($this->baseUrl, '/') . '/motor/v1/quote';
        $payload = $this->sanitizePayload($payload);
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'x-api-key'     => $this->apiKey,
            'Content-Type'  => 'application/json'
        ])->timeout($this->timeout)->post($url, $payload);
        if ($response->failed()) {
            Log::error('Tata AIG Quote API Failure', [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve quote from Tata AIG.',
                'errors'  => $response->json()
            ];
        }
        return [
            'success' => true,
            'data'    => $response->json()
        ];
    }

    /**
     * Recursively convert any null values to empty strings to bypass Laravel's empty-string-to-null middleware.
     */
    private function sanitizePayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if ($value === null) {
                $payload[$key] = '';
            } elseif (is_array($value)) {
                $payload[$key] = $this->sanitizePayload($value);
            }
        }
        return $payload;
    }
}
