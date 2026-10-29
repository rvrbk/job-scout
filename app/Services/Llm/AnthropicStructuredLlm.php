<?php

namespace App\Services\Llm;

use Anthropic\Beta\Messages\BetaOutputConfig\Effort;
use Anthropic\Beta\Messages\BetaTextBlock;
use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;

class AnthropicStructuredLlm implements StructuredLlm
{
    public function __construct(
        private Client $client,
        private string $model,
        private Effort $effort,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function generate(string $system, string $prompt, array $schema): array
    {
        try {
            $message = $this->client->beta->messages->create(
                model: $this->model,
                maxTokens: 16000,
                system: $system,
                messages: [['role' => 'user', 'content' => $prompt]],
                outputConfig: [
                    'effort' => $this->effort,
                    'format' => ['type' => 'json_schema', 'schema' => $schema],
                ],
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
            );
        } catch (APIException $exception) {
            throw new LlmException('Claude API request failed: '.$exception->getMessage(), previous: $exception);
        }

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
