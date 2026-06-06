<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeminiVehicleMatcher
{
    private string $apiKey;
    private string $model;
    private int    $confidenceThreshold;
    private int    $batchSize;

    public function __construct()
    {
        $this->apiKey              = config('services.gemini.api_key');
        $this->model               = config('services.gemini.model', 'gemini-1.5-flash');
        $this->confidenceThreshold = config('services.gemini.confidence_threshold', 80);
        $this->batchSize           = config('services.gemini.batch_size', 50);
    }

    public function resolveMakes(array $unresolved, array $droomMakes): array
    {
        if (empty($unresolved)) return [];

        return $this->batchResolve(
            $unresolved,
            $droomMakes,
            'make',
            "You are a vehicle database expert. Match insurer make names to the correct Droom make."
        );
    }

    public function resolveModels(array $unresolved, array $droomModels): array
    {
        if (empty($unresolved)) return [];

        return $this->batchResolve(
            $unresolved,
            $droomModels,
            'model',
            "You are a vehicle database expert. Match insurer model names to the correct Droom model.
             Note: Insurer names often include the make name as a prefix (e.g. 'ATHER 450' maps to Droom model '450').
             Note: BS4/BS6/BSVI/BSIV suffixes should be ignored during matching."
        );
    }

    public function resolveTrims(array $unresolved, array $droomTrims): array
    {
        if (empty($unresolved)) return [];

        return $this->batchResolve(
            $unresolved,
            $droomTrims,
            'trim',
            "You are a vehicle database expert. Match insurer variant/trim names to the correct Droom trim.
             Note: STD = STANDARD, DLX = DELUXE, DISC = DISK, FI = FUEL INJECTION, ABS/CBS can be ignored.
             Note: BS4/BS6/BSVI emission tags should be ignored.
             Note: Insurer names may have different word orders or abbreviations for the same trim."
        );
    }

    private function batchResolve(
        array  $unresolved,
        array  $droomCandidates,
        string $level,
        string $systemContext
    ): array {
        $results = [];

        foreach (array_chunk($unresolved, $this->batchSize) as $batch) {
            $batchResults = $this->callGemini($batch, $droomCandidates, $level, $systemContext);
            $results      = array_merge($results, $batchResults);
        }

        return $results;
    }

    private function callGemini(
        array  $batch,
        array  $droomCandidates,
        string $level,
        string $systemContext
    ): array {
        $prompt     = $this->buildPrompt($batch, $droomCandidates, $level, $systemContext);
        $cacheKey   = 'gemini_vehicle_match_' . md5($prompt);

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(45)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature'     => 0,
                        'topK'            => 1,
                        'topP'            => 1,
                        'responseMimeType' => 'application/json',
                    ],
                ]
            );

            if ($response->failed()) {
                Log::error('Gemini API error', [
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                ]);
                return $this->nullResults($batch);
            }

            $raw     = $response->json();
            $text    = $raw['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $results = $this->parseResponse($text, $batch);

            Cache::put($cacheKey, $results, now()->addHours(24));

            return $results;
        } catch (\Throwable $e) {
            Log::error('Gemini call failed', ['error' => $e->getMessage()]);
            return $this->nullResults($batch);
        }
    }

    private function buildPrompt(
        array  $batch,
        array  $droomCandidates,
        string $level,
        string $systemContext
    ): string {
        $unresolvedJson  = json_encode($batch,          JSON_PRETTY_PRINT);
        $candidatesJson  = json_encode($droomCandidates, JSON_PRETTY_PRINT);

        return <<<PROMPT
{$systemContext}

TASK:
Match each item in the "TO_MATCH" list to the best entry in the "DROOM_CANDIDATES" list.

RULES:
- Return ONLY valid JSON, no markdown, no explanation outside the JSON.
- For each item in TO_MATCH, return the droom_id of the best match.
- If you are not confident enough (confidence < {$this->confidenceThreshold}), return null for droom_id.
- confidence is an integer from 0-100 representing how sure you are.
- reason is a short string explaining your choice (max 10 words).
- Ignore BS4/BS6/BSVI/BSIV/BS3 emission standard suffixes when matching.
- Ignore ABS/CBS safety feature tags when matching.
- STD = STANDARD, DLX = DELUXE, DISC = DISK, FI = FUEL INJECTION.
- Insurer {$level} names often contain the make name as a prefix; strip it when matching models.

TO_MATCH (insurer {$level}s that could not be auto-matched):
{$unresolvedJson}

DROOM_CANDIDATES (pick from these only — do NOT invent IDs):
{$candidatesJson}

REQUIRED OUTPUT FORMAT (strict JSON, nothing else):
{
  "matches": [
    {
      "insurer_code": <integer from TO_MATCH>,
      "droom_id": <integer from DROOM_CANDIDATES or null>,
      "confidence": <0-100>,
      "reason": "<short explanation>"
    }
  ]
}
PROMPT;
    }

    private function parseResponse(string $rawText, array $batch): array
    {
        $cleanText = preg_replace('/^```json\s*/m', '', $rawText);
        $cleanText = preg_replace('/^```\s*/m', '', $cleanText);
        $cleanText = trim($cleanText);

        try {
            $decoded = json_decode($cleanText, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::error('Gemini response JSON parse failed', [
                'raw'   => $rawText,
                'error' => $e->getMessage(),
            ]);
            return $this->nullResults($batch);
        }

        $results = [];

        foreach ($decoded['matches'] ?? [] as $match) {
            $code       = (int)  ($match['insurer_code'] ?? 0);
            $droomId    = isset($match['droom_id']) ? (int) $match['droom_id'] : null;
            $confidence = (int)  ($match['confidence'] ?? 0);

            if (!$code) continue;

            if ($droomId !== null && $confidence < $this->confidenceThreshold) {
                Log::info("Gemini low-confidence match skipped", [
                    'insurer_code' => $code,
                    'droom_id'     => $droomId,
                    'confidence'   => $confidence,
                    'reason'       => $match['reason'] ?? '',
                ]);
                $droomId = null;
            }

            $results[$code] = $droomId;
        }

        return $results;
    }

    private function nullResults(array $batch): array
    {
        $results = [];
        foreach ($batch as $item) {
            $results[(int) $item['code']] = null;
        }
        return $results;
    }
}
