<?php

namespace App\Services\Llm;

interface StructuredLlm
{
    /**
     * Ask the model for a JSON object that conforms to the given JSON schema.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     *
     * @throws LlmException
     */
    public function generate(string $system, string $prompt, array $schema): array;
}
