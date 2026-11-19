<?php

namespace App\Services\Llm;

interface StructuredLlm
{
    /**
     * Ask the model for a JSON object that conforms to the given JSON schema.
     *
     * $task picks the model and effort from config('services.anthropic.tasks') and labels the usage log.
     * $sharedContext is a long block that stays identical across calls (such as a ledger); it is sent
     * first and cached, so repeat calls within a few minutes pay a fraction for it.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     *
     * @throws LlmException
     */
    public function generate(string $system, string $prompt, array $schema, string $task = 'default', ?string $sharedContext = null): array;
}
