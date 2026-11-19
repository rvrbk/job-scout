<?php

namespace App\Services\Llm;

use Anthropic\Beta\Messages\BetaOutputConfig\Effort;
use Anthropic\Beta\Messages\BetaTextBlock;
use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use Illuminate\Support\Facades\Log;

class AnthropicStructuredLlm implements StructuredLlm
{
    /**
     * @param  array<string, array{model?: string, effort?: string}>  $tasks  per-task overrides of the default model and effort
     */
    public function __construct(
        private Client $client,
        private string $model,
        private Effort $effort,
        private array $tasks = [],
    ) {}

    /**
     * {@inheritDoc}
     */
    public function generate(string $system, string $prompt, array $schema, string $task = 'default', ?string $sharedContext = null): array
    {
        $model = $this->tasks[$task]['model'] ?? $this->model;
        $effort = isset($this->tasks[$task]['effort']) ? Effort::from($this->tasks[$task]['effort']) : $this->effort;

        $content = $sharedContext === null
            ? $prompt
            : [
                ['type' => 'text', 'text' => $sharedContext, 'cache_control' => ['type' => 'ephemeral']],
                ['type' => 'text', 'text' => $prompt],
            ];

        $request = [
            'model' => $model,
            'maxTokens' => 16000,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $content]],
            'outputConfig' => [
                'effort' => $effort,
                'format' => ['type' => 'json_schema', 'schema' => $schema],
            ],
        ];

        // Haiku has no server-side refusal fallback.
        if (! str_starts_with($model, 'claude-haiku')) {
            $request += ['fallbacks' => 'default', 'betas' => ['server-side-fallback-2026-07-01']];
        }

        try {
            $message = $this->client->beta->messages->create(...$request);
        } catch (APIException $exception) {
            throw new LlmException('Claude API request failed: '.$exception->getMessage(), previous: $exception);
        }

        // Token counts per call, so spend can be traced to a task.
        Log::info('llm.usage', [
            'task' => $task,
            'model' => $model,
            'effort' => $effort->value,
            'input_tokens' => $message->usage->inputTokens,
            'cache_read_tokens' => $message->usage->cacheReadInputTokens ?? 0,
            'cache_write_tokens' => $message->usage->cacheCreationInputTokens ?? 0,
            'output_tokens' => $message->usage->outputTokens,
        ]);

        if ($message->stopReason === 'refusal') {
            throw new LlmException('Claude declined the request.');
        }

        if ($message->stopReason === 'max_tokens') {
            throw new LlmException('Claude ran out of output tokens before finishing.');
        }

        foreach ($message->content as $block) {
            if ($block instanceof BetaTextBlock) {
                $decoded = json_decode($block->text, true);

                if (! is_array($decoded)) {
                    throw new LlmException('Claude returned output that is not valid JSON.');
                }

                return $decoded;
            }
        }

        throw new LlmException('Claude returned no text output.');
    }
}
