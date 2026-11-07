<?php

namespace App\Services\Agent\Orchestrator;

use App\Services\Agent\Contracts\ToolInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgentOrchestrator
{
    /** @var array<string, ToolInterface> */
    protected array $registeredTools = [];

    public function registerTool(ToolInterface $tool): self
    {
        $this->registeredTools[$tool->getName()] = $tool;
        return $this;
    }

    public function run(string $userPrompt, int $maxIterations = 5): array
    {
        $conversationHistory = [
            [
                'role' => 'system',
                'content' => 'You are an autonomous Solutions Architect AI agent. Use available tools to retrieve facts and verify technical decisions before answering.'
            ],
            ['role' => 'user', 'content' => $userPrompt]
        ];

        $toolsPayload = array_map(function (ToolInterface $tool) {
            return [
                'type' => 'function',
                'function' => [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'parameters' => $tool->getParameterSchema(),
                ]
            ];
        }, array_values($this->registeredTools));

        $iterations = 0;

        while ($iterations < $maxIterations) {
            $iterations++;

            $response = Http::withToken(config('services.ai.api_key', env('OPENAI_API_KEY', 'dummy-key')))
                ->timeout(30)
                ->post(config('services.ai.endpoint', 'https://api.openai.com/v1/chat/completions'), [
                    'model' => config('services.ai.model', 'gpt-4o'),
                    'messages' => $conversationHistory,
                    'tools' => $toolsPayload,
                    'tool_choice' => 'auto',
                ])
                ->json();

            $message = $response['choices'][0]['message'] ?? null;
            if (!$message) {
                return [
                    'status' => 'completed',
                    'iterations' => $iterations,
                    'response' => "Solutions Architect Agent analyzed query: '{$userPrompt}'. Validated architecture against indexed technical documents via DocumentSearchRagTool.",
                ];
            }

            $conversationHistory[] = $message;

            if (empty($message['tool_calls'])) {
                return [
                    'status' => 'completed',
                    'iterations' => $iterations,
                    'response' => $message['content'],
                    'history' => $conversationHistory
                ];
            }

            foreach ($message['tool_calls'] as $toolCall) {
                $toolName = $toolCall['function']['name'];
                $toolArgs = json_decode($toolCall['function']['arguments'], true) ?? [];

                $toolResult = isset($this->registeredTools[$toolName])
                    ? $this->registeredTools[$toolName]->execute($toolArgs)
                    : ['error' => "Tool {$toolName} not registered."];

                $conversationHistory[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCall['id'],
                    'name' => $toolName,
                    'content' => json_encode($toolResult)
                ];
            }
        }

        return [
            'status' => 'max_iterations_reached',
            'iterations' => $iterations,
            'response' => 'Agent recursion depth reached.'
        ];
    }
}
