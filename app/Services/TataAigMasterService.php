<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Insurance\InsurerIntegration;
use Exception;
use Illuminate\Support\Facades\Crypt;
use GuzzleHttp\Client;

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

        // 1. Try to find the record matching the current environment
        $integration = InsurerIntegration::where('insurer_id', 1)->first();
        $this->loadFormData($integration);
    }

    protected function loadFormData(InsurerIntegration $integration)
    {
        try {
            if (!$integration) {
                throw new \RuntimeException("Tata AIG integration record not found in database.");
            }
            $this->insurerId = $integration->insurer_id;
            $this->clientId = Crypt::decryptString($integration->client_id);
            $this->clientSecret = Crypt::decryptString($integration->client_secret);
            $this->baseUrl = $integration->api_base_url;
            $attrs = $integration->other_attributes ?? [];
            if (empty($attrs)) {
                throw new \InvalidArgumentException("Integration 'other_attributes' is empty or missing from the database record.");
            }
            $this->tokenUrl = $attrs['token_url'];
            $this->scope = $attrs['scope'];
            $this->timeout = (int) ($attrs['timeout'] ?? 15);
            $this->cacheKey = $attrs['cache_key'];
            $this->expiryBufferSeconds = (int) $attrs['expiry_buffer_seconds'] ?? 60;
            $this->apiKey = (string) config('services.tata_aig.api_key', '');
            $this->productCode = $attrs['product_code'] ?? config('services.tata_aig.product_code', '3187');
        } catch (\Exception $e) {
            Log::error('Tata AIG configuration load failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            throw $e;
        }
    }

    protected function getAccessToken(): string
    {
        try {
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
                throw new \RuntimeException('Tata AIG authentication failed with status: ' . $response->status());
            }

            $data = $response->json();
            $this->accessToken = $data['access_token'] ?? null;
            if (!$this->accessToken) {
                throw new \Exception('No access token found in Tata AIG response.');
            }

            return $this->accessToken;
        } catch (Exception $e) {
            Log::error('Tata AIG access token retrieval failed: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Send quote request to Tata AIG Quote API.
     *
     * @param array $payload
     * @return array
     */

    public function fetchQuote(array $payload)
    {
        try {
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
        } catch (Exception $e) {
            Log::error('Tata AIG fetchQuote exception: ' . $e->getMessage(), ['exception' => $e]);
            return [
                'success' => false,
                'message' => 'An error occurred while fetching quote: ' . $e->getMessage()
            ];
        }
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

    public function fetchRto($rtoCode = '', $rtoCity = '')
    {
        try {
            $rtoUrl = '/motor-master/v1/pcimportmaster/get_pcdropdowns/rto/' . $rtoCode . '/' . $rtoCity;

            $client = new Client(array(
                'base_uri' => rtrim($this->baseUrl, '/'),
                'timeout' => $this->timeout ?? 30
            ));

            return $client->get($rtoUrl, [
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'x-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ),
            ]);
        } catch (\Throwable $th) {
            return array('code' => 'failed', 'message' => $th->getMessage() ?? 'Something went wrong');
        }
    }
}
