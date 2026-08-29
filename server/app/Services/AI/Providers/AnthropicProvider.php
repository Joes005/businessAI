<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Exception;

class AnthropicProvider implements AIProviderInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?? config('ai.providers.anthropic.api_key', '');
        $this->model  = $model ?? config('ai.providers.anthropic.model', 'claude-3-5-sonnet-20241022');
    }

    public function generateResponse(array $messages, array $tools = [], array $options = []): array
    {
        if (empty($this->apiKey)) {
            return (new LocalRuleProvider())->generateResponse($messages, $tools, $options);
        }

        $systemPrompt = $options['system_prompt'] ?? '';
        $formattedMessages = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemPrompt .= "\n" . $msg['content'];
                continue;
            }
            $formattedMessages[] = [
                'role'    => $msg['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $msg['content'],
            ];
        }

        $payload = [
            'model'      => $this->model,
            'max_tokens' => 2048,
            'system'     => trim($systemPrompt),
            'messages'   => $formattedMessages,
        ];

        if (!empty($tools)) {
            $payload['tools'] = array_map(function ($tool) {
                return [
                    'name'         => $tool['name'],
                    'description'  => $tool['description'],
                    'input_schema' => $tool['parameters'] ?? ['type' => 'object', 'properties' => new \stdClass()],
                ];
            }, $tools);
        }

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])
        ->timeout(30)
        ->post('https://api.anthropic.com/v1/messages', $payload);

        if (!$response->successful()) {
            throw new Exception('Anthropic API request failed: ' . $response->body());
        }

        $data = $response->json();
        $content = '';
        $toolCalls = [];

        if (isset($data['content']) && is_array($data['content'])) {
            foreach ($data['content'] as $block) {
                if ($block['type'] === 'text') {
                    $content .= $block['text'];
                } elseif ($block['type'] === 'tool_use') {
                    $toolCalls[] = [
                        'id'        => $block['id'],
                        'name'      => $block['name'],
                        'arguments' => $block['input'] ?? [],
                    ];
                }
            }
        }

        return [
            'content'    => $content,
            'tool_calls' => $toolCalls,
            'usage'      => $data['usage'] ?? [],
        ];
    }
}
