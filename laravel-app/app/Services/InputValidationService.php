<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InputValidationService
{
    protected string $endpoint;
    protected string $model = 'gemma4:31b-cloud';

    public function __construct()
    {
        $this->endpoint = config('services.ollama.url', 'http://localhost:11434/api/generate');
    }

    public function validateInput(string $payload): array
    {
        $systemPrompt = "You are a strict data integrity gateway for DesiFlow. Evaluate the user payload input string. If it contains keyboard smashes, gibberish (e.g., 'asdf', 'qwerty', '12345'), profanity, or malicious scripts, classify it as malicious. You must respond ONLY with this JSON signature: {\"isValid\": false, \"cleanReason\": \"Brief explanation of the failure rule\"}. If the structure is authentic, return {\"isValid\": true, \"cleanReason\": \"\"}. To prevent data stream parsing errors, do not output internal thought tags (thought) or reasoning steps. Output ONLY raw JSON. No conversational filler or markdown markers.";

        try {
            $response = Http::timeout(10)->post($this->endpoint, [
                'model' => $this->model,
                'prompt' => "System: {$systemPrompt}\n\nUser Input: \"{$payload}\"",
                'options' => [
                    'temperature' => 1.0,
                    'top_p' => 0.95,
                ],
                'stream' => false,
            ]);

            if ($response->failed()) {
                throw new \Exception('Ollama API response failed: ' . $response->status());
            }

            $body = $response->json();
            $text = $body['response'] ?? '';

            // Strip potential markdown code blocks if the model ignored instructions
            $jsonText = preg_replace('/^```json\s*|```$/m', '', trim($text));

            return json_decode($jsonText, true) ?: [
                'isValid' => false,
                'cleanReason' => 'Invalid response format from validation service.'
            ];

        } catch (\Exception $e) {
            Log::error('InputValidationService Error: ' . $e->getMessage());
            // Fail-safe: we allow the input but log the error, or we could block it.
            // For a "Guardrail", failing open might be risky, but failing closed
            // blocks all entries when the API is down. We'll fail open but log.
            return [
                'isValid' => true,
                'cleanReason' => ''
            ];
        }
    }
}
