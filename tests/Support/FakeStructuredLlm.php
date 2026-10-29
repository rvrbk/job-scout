<?php

namespace Tests\Support;

use App\Services\Llm\StructuredLlm;
use Closure;
use PHPUnit\Framework\Assert;

/**
 * Stands in for Claude in tests: returns queued responses in order and records every call.
 */
class FakeStructuredLlm implements StructuredLlm
{
    /** @var list<array{system: string, prompt: string, schema: array<string, mixed>}> */
    public array $calls = [];

    /**
     * @param  list<array<string, mixed>|Closure(string $prompt): array<string, mixed>>  $responses
     */
    public function __construct(private array $responses = []) {}

    /**
     * {@inheritDoc}
     */
    public function generate(string $system, string $prompt, array $schema): array
    {
        $this->calls[] = ['system' => $system, 'prompt' => $prompt, 'schema' => $schema];

        Assert::assertNotEmpty($this->responses, 'Unexpected call to the LLM.');

        $response = array_shift($this->responses);

        return $response instanceof Closure ? $response($prompt) : $response;
    }

    /**
     * Pull the JSON payload between <tag> and </tag> out of a prompt.
     *
     * @return array<mixed>
     */
    public static function payload(string $prompt, string $tag): array
    {
        preg_match("/<{$tag}>\n(.*)\n<\/{$tag}>/s", $prompt, $matches);

        return json_decode($matches[1], true);
    }
}
