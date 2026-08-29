<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Exception;

class OpenAIProvider implements AIProviderInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?? config('ai.providers.openai.api_key', '');
        $this->model  = $model ?? config('ai.providers.openai.model', 'gpt-4o-mini');
    }

    public function generateResponse(array $messages, array $tools = [], array $options = []): array
    {
        if (empty($this->apiKey)) {
            // Fallback to LocalRuleProvider if API key is not configured
            return (new LocalRuleProvider())->generateResponse($messages, $tools, $options);
        }

        $payload = [
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => $options['temperature'] ?? 0.2,
        ];

        if (!empty($tools)) {
            $payload['tools'] = array_map(function ($tool) {
                return [
                    'type'     => 'function',
                    'function' => [
                        'name'        => $tool['name'],
                        'description' => $tool['description'],
                        'parameters'  => $tool['parameters'] ?? new \stdClass(),
                    ],
                ];
            }, $tools);
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if (!$response->successful()) {
            throw new Exception('OpenAI API request failed: ' . $response->body());
        }

        $data = $response->json();
        $choice = $data['choices'][0]['message'] ?? [];

        $toolCalls = [];
        if (!empty($choice['tool_calls'])) {
            foreach ($choice['tool_calls'] as $call) {
                $toolCalls[] = [
                    'id'        => $call['id'],
                    'name'      => $call['function']['name'],
                    'arguments' => json_decode($call['function']['arguments'] ?? '{}', true) ?: [],
                ];
            }
        }

        return [
            'content'    => $choice['content'] ?? '',
            'tool_calls' => $toolCalls,
            'usage'      => $data['usage'] ?? [],
        ];
    }
}
