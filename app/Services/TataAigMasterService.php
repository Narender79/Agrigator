<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
// use App\Models\InsurerIntegration; // Add this line
use App\Models\Insurance\InsurerIntegration;
use Illuminate\Support\Facades\Crypt; // Add this line

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

            // Read Resource API endpoint and API Key dynamically from DB if present
            $this->baseUrl = $attrs['resource_base_url'] ?? config('services.tata_aig.base_url', 'https://uatapigw.tataaig.com');
            $this->apiKey = $attrs['api_key'] ?? config('services.tata_aig.api_key', '5QerRezeZs3PrVdLQu79c1v9Nsh5S7BOan26zc7P');
            $this->productCode = $attrs['product_code'] ?? '3187';
        } else {
            // 3. Complete fallback if no DB integration record exists at all
            $this->clientId = (string) config('services.tata_aig.client_id');
            $this->clientSecret = (string) config('services.tata_aig.client_secret');
            $this->tokenUrl = config('services.tata_aig.base_url') . '/access-gateway/oauth2/token';
            $this->scope = 'https://api.iorta.in/write';
            $this->timeout = 15;
            $this->baseUrl = config('services.tata_aig.base_url', 'https://uatapigw.tataaig.com');
            $this->apiKey = config('services.tata_aig.api_key', '5QerRezeZs3PrVdLQu79c1v9Nsh5S7BOan26zc7P');
            $this->productCode = config('services.tata_aig.product_code', '3187');
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


    public function fetchMakes(): array
    {
        $token = $this->getAccessToken();
        $url = rtrim($this->baseUrl, '/') . "/motor-master/v1/tw-api/make/{$this->productCode}";
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'x-api-key' => $this->apiKey,
        ])->get($url);

        if ($response->failed()) {
            Log::error("Failed fetching make from Tata AIG", ['body' => $response->body()]);
            return [];
        }
        return $response->json('data') ?? [];
    }

    public function fetchModels(string $makeCode, string $makeName): array
    {
        $token = $this->getAccessToken();

        $encodedMakeName = rawurlencode($makeName);

        $url = rtrim($this->baseUrl, '/') . "/motor-master/v1/tw-api/model/{$makeCode}/{$encodedMakeName}/{$this->productCode}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'x-api-key' => $this->apiKey,
        ])->get($url);

        if ($response->failed()) {
            Log::error("Failed fetching models for make {$makeName} ({$makeCode}) from Tata AIG", ['body' => $response->body()]);
            return [];
        }

        return $response->json('data') ?? [];
    }

    public function fetchVariants(string $modelCode, string $modelName): array
    {
        $token = $this->getAccessToken();

        $encodedModelName = rawurlencode($modelName);

        $url = rtrim($this->baseUrl, '/') . "/motor-master/v1/tw-api/variant/{$modelCode}/{$encodedModelName}/{$this->productCode}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'x-api-key' => $this->apiKey,
        ])->get($url);

        if ($response->failed()) {
            Log::error("Failed fetching variants for model {$modelName} ({$modelCode}) from Tata AIG", ['body' => $response->body()]);
            return [];
        }

        return $response->json('data') ?? [];
    }
}
