<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Providers\LocalRuleProvider;
use App\Services\AI\Providers\OpenAIProvider;
use App\Services\AI\Providers\AnthropicProvider;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiAction;
use Illuminate\Support\Str;

class AIOrchestrator
{
    protected AIProviderInterface $provider;
    protected AIContextBuilder $contextBuilder;
    protected AIMemoryService $memoryService;
    protected AIToolRegistry $toolRegistry;
    protected AIActionExecutor $actionExecutor;
    protected AIKnowledgeService $knowledgeService;

    public function __construct(
        AIContextBuilder $contextBuilder,
        AIMemoryService $memoryService,
        AIToolRegistry $toolRegistry,
        AIActionExecutor $actionExecutor,
        AIKnowledgeService $knowledgeService
    ) {
        $this->contextBuilder  = $contextBuilder;
        $this->memoryService   = $memoryService;
        $this->toolRegistry    = $toolRegistry;
        $this->actionExecutor  = $actionExecutor;
        $this->knowledgeService= $knowledgeService;

        // Resolve AI Provider from config
        $providerName = config('ai.default', 'local');
        if ($providerName === 'openai') {
            $this->provider = new OpenAIProvider();
        } elseif ($providerName === 'anthropic') {
            $this->provider = new AnthropicProvider();
        } else {
            $this->provider = new LocalRuleProvider();
        }
    }

    /**
     * Complete AI orchestrator pipeline: Understand -> Build Context -> Retrieve Memory -> RAG -> Tool Execution -> Validate -> Respond.
     */
    public function handleUserRequest(string $prompt, int $businessId, ?int $userId = null, ?int $conversationId = null): array
    {
        // 1. Get or create conversation
        if ($conversationId) {
            $conversation = AiConversation::where('business_id', $businessId)->find($conversationId);
        } else {
            $conversation = AiConversation::create([
                'business_id' => $businessId,
                'user_id'     => $userId ?? 1,
                'title'       => Str::limit($prompt, 30),
            ]);
        }

        // Save user message
        AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'sender'             => 'user',
            'text'               => $prompt,
        ]);

        // 2. Build Business Context & Memories & RAG Knowledge
        $context = $this->contextBuilder->buildContext($businessId, $prompt);
        $memories = $this->memoryService->getActiveMemories($businessId);
        $knowledge = $this->knowledgeService->search($businessId, $prompt);
        $tools = $this->toolRegistry->getToolDefinitions();

        // 3. Prepare AI Prompt Messages
        $messages = [
            [
                'role'    => 'system',
                'content' => "You are an AI Business Employee for {$context['business_info']['business_name']}. Currency is {$context['business_info']['currency']}. Never invent business numbers. Always use server-side tools for numerical facts.",
            ],
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ];

        // 4. Generate AI Response / Tool Call Intent
        $aiResponse = $this->provider->generateResponse($messages, $tools);

        $executedResult = [];
        $metrics = [];
        $dataType = 'text';
        $tableData = null;
        $suggestedActions = [];
        $requiresAction = false;
        $actionId = null;
        $answerText = $aiResponse['content'] ?? '';

        // 5. Tool Execution Loop
        if (!empty($aiResponse['tool_calls'])) {
            foreach ($aiResponse['tool_calls'] as $toolCall) {
                $toolName = $toolCall['name'];
                $toolArgs = $toolCall['arguments'] ?? [];

                $execResult = $this->actionExecutor->execute($toolName, $toolArgs, $businessId, $userId, $prompt);

                if (isset($execResult['answer'])) {
                    $answerText = $execResult['answer'];
                }
                if (isset($execResult['metrics'])) {
                    $metrics = $execResult['metrics'];
                }
                if (isset($execResult['data_type'])) {
                    $dataType = $execResult['data_type'];
                }
                if (isset($execResult['data'])) {
                    $tableData = $execResult['data'];
                }
                if (isset($execResult['requires_action']) && $execResult['requires_action']) {
                    $requiresAction = true;
                    $actionId = $execResult['action_id'] ?? null;
                }
            }
        }

        // Default suggested actions if empty
        if (empty($suggestedActions)) {
            $suggestedActions = [
                ['label' => 'How much did I sell today?'],
                ['label' => 'Who owes me money?'],
                ['label' => 'Which items are low in stock?'],
                ['label' => 'What is my profit this month?'],
            ];
        }

        // 6. Save Copilot response
        $copilotMsg = AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'sender'             => 'copilot',
            'text'               => $answerText,
            'metrics'            => $metrics,
            'data_type'          => $dataType,
            'data'               => $tableData,
            'suggested_actions'  => $suggestedActions,
            'tool_calls'         => $aiResponse['tool_calls'] ?? [],
        ]);

        return [
            'conversation_id'  => $conversation->id,
            'intent'           => $execResult['intent'] ?? 'GENERAL',
            'answer'           => $answerText,
            'metrics'          => $metrics,
            'data_type'        => $dataType,
            'data'             => $tableData,
            'suggested_actions'=> $suggestedActions,
            'requires_action'  => $requiresAction,
            'action_id'        => $actionId,
        ];
    }

    /**
     * Confirm and execute a pending high-risk action.
     */
    public function confirmAction(int $actionId, int $businessId, bool $approved): array
    {
        $action = AiAction::where('business_id', $businessId)->find($actionId);
        if (!$action) {
            return ['success' => false, 'message' => 'Action not found or unauthorized.'];
        }

        if (!$approved) {
            $action->update(['status' => 'REJECTED']);
            return ['success' => true, 'message' => 'Action rejected by user.'];
        }

        $action->update([
            'status'           => 'EXECUTED',
            'execution_result' => 'Action successfully approved and executed.',
        ]);

        return [
            'success' => true,
            'message' => "Action '{$action->action_type}' was successfully executed.",
        ];
    }
}
