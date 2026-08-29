<?php

namespace App\Services\AI\Contracts;

interface AIProviderInterface
{
    /**
     * Complete a prompt / conversation with system instructions, context, memories, tools, and RAG knowledge.
     *
     * @param array $messages Message history [{'role': 'user'|'assistant'|'system', 'content': string}]
     * @param array $tools Available tool definitions
     * @param array $options Additional options (temperature, system_prompt, etc.)
     * @return array Standardized AI response ['content' => string, 'tool_calls' => array, 'usage' => array]
     */
    public function generateResponse(array $messages, array $tools = [], array $options = []): array;
}
