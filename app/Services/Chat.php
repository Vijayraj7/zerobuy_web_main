<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class Chat
{
    protected array $messages = [];

    public function systemMessage(string $message): static
    {
        $this->messages[] = [
            'role' => 'system',
            'content' => $message
        ];

        return $this;
    }

    public function send(string $message): ?string
    {
        $this->messages[] = [
            'role' => 'user',
            'content' => $message
        ];

        $provider = strtolower((string) config('services.ai.provider', 'openai'));

        if ($provider === 'gemini') {
            return $this->sendWithGemini();
        }

        return $this->sendWithOpenAI();
    }

    protected function sendWithOpenAI(): ?string
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $this->messages,
            'temperature' => 0.7
        ])->choices[0]->message->content;

        if ($response) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => $response
            ];
        }

        return $response;
    }

    protected function sendWithGemini(): ?string
    {
        $apiKey = (string) config('services.gemini.api_key');
        $model = $this->normalizeGeminiModel((string) config('services.gemini.model', 'gemini-1.5-flash'));

        if ($apiKey === '') {
            throw new Exception('Gemini API key is not configured.');
        }

        $conversation = collect($this->messages)
            ->map(function ($message) {
                return strtoupper((string) ($message['role'] ?? 'user')) . ': ' . (string) ($message['content'] ?? '');
            })
            ->implode("\n\n");

        $apiVersions = ['v1', 'v1beta'];
        $fallbackModels = [
            $model,
            'gemini-2.0-flash',
            'gemini-1.5-flash-latest',
            'gemini-1.5-flash',
        ];

        $assistantResponse = null;
        $lastError = null;

        foreach ($apiVersions as $version) {
            foreach (array_unique($fallbackModels) as $candidateModel) {
                $attempt = $this->requestGeminiGenerateContent($apiKey, $version, $candidateModel, $conversation);
                if ($attempt['ok']) {
                    $assistantResponse = $attempt['text'];
                    break 2;
                }

                $lastError = $attempt['error'];
            }
        }

        if (! is_string($assistantResponse) || $assistantResponse === '') {
            $discoveredModels = $this->discoverGeminiGenerateContentModels($apiKey, $apiVersions);

            foreach ($discoveredModels as $candidate) {
                $attempt = $this->requestGeminiGenerateContent($apiKey, $candidate['version'], $candidate['model'], $conversation);
                if ($attempt['ok']) {
                    $assistantResponse = $attempt['text'];
                    break;
                }

                $lastError = $attempt['error'];
            }
        }

        if (! is_string($assistantResponse) || $assistantResponse === '') {
            throw new Exception($lastError ?: 'Gemini request failed.');
        }

        if ($assistantResponse) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => $assistantResponse
            ];
        }

        return $assistantResponse;
    }

    protected function normalizeGeminiModel(string $model): string
    {
        $normalized = trim($model);
        $normalized = preg_replace('#^models/#', '', $normalized) ?? $normalized;

        return $normalized !== '' ? $normalized : 'gemini-1.5-flash';
    }

    protected function requestGeminiGenerateContent(string $apiKey, string $version, string $model, string $conversation): array
    {
        $response = Http::timeout(90)
            ->post('https://generativelanguage.googleapis.com/' . $version . '/models/' . urlencode($this->normalizeGeminiModel($model)) . ':generateContent?key=' . $apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $conversation],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                ],
            ]);

        if (! $response->successful()) {
            return [
                'ok' => false,
                'text' => null,
                'error' => (string) data_get($response->json(), 'error.message', 'Gemini request failed.'),
            ];
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            return [
                'ok' => false,
                'text' => null,
                'error' => 'Gemini returned an empty response.',
            ];
        }

        return [
            'ok' => true,
            'text' => $text,
            'error' => null,
        ];
    }

    protected function discoverGeminiGenerateContentModels(string $apiKey, array $versions): array
    {
        $models = [];

        foreach ($versions as $version) {
            $response = Http::timeout(30)
                ->get('https://generativelanguage.googleapis.com/' . $version . '/models?key=' . $apiKey);

            if (! $response->successful()) {
                continue;
            }

            $list = (array) data_get($response->json(), 'models', []);

            foreach ($list as $item) {
                $supportedMethods = (array) data_get($item, 'supportedGenerationMethods', []);
                if (! in_array('generateContent', $supportedMethods, true)) {
                    continue;
                }

                $name = (string) data_get($item, 'name', '');
                $name = $this->normalizeGeminiModel($name);

                if ($name === '') {
                    continue;
                }

                $models[] = [
                    'version' => $version,
                    'model' => $name,
                ];
            }
        }

        usort($models, function ($a, $b) {
            $aFlash = str_contains($a['model'], 'flash') ? 0 : 1;
            $bFlash = str_contains($b['model'], 'flash') ? 0 : 1;

            if ($aFlash === $bFlash) {
                return strcmp($a['model'], $b['model']);
            }

            return $aFlash <=> $bFlash;
        });

        return array_values(array_unique($models, SORT_REGULAR));
    }

    public function reply(string $message): ?string
    {
        return $this->send($message);
    }

    public function messages(): array
    {
        return $this->messages;
    }
}
