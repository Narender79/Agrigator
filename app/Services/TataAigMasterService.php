<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\InsurerIntegration; // Add this line
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
    protected ?string $accessToken = null;
    public function __construct()
    {
        // 1. Fetch credentials dynamically from the database
        $integration = InsurerIntegration::where('insurer_id', 1)->first();
        if ($integration) {
            // Decrypt the encrypted database columns
            $this->clientId = Crypt::decryptString($integration->client_id);
            $this->clientSecret = Crypt::decryptString($integration->client_secret);

            // Read parameters from other_attributes array
            $attrs = $integration->other_attributes ?? [];
            $this->tokenUrl = $attrs['token_url'] ?? $integration->api_base_url;
            $this->scope = $attrs['scope'] ?? 'https://api.iorta.in/write';
            $this->timeout = $attrs['timeout'] ?? 15;
        } else {
            // Fallback to config files if the DB entry is missing
            $this->clientId = config('services.tata_aig.client_id');
            $this->clientSecret = config('services.tata_aig.client_secret');
            $this->tokenUrl = config('services.tata_aig.base_url') . '/access-gateway/oauth2/token';
            $this->scope = 'https://api.iorta.in/write';
            $this->timeout = 15;
        }
        // 2. Fetch the resource API endpoint and api_key
        $this->baseUrl = config('services.tata_aig.base_url', 'https://uatapigw.tataaig.com');
        $this->apiKey = config('services.tata_aig.api_key', '5QerRezeZs3PrVdLQu79c1v9Nsh5S7BOan26zc7P');
        $this->productCode = config('services.tata_aig.product_code', '3187');
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
