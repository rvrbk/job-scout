<?php

namespace App\Services\JobSearch\Sources;

use App\Models\JobSearchPreference;
use App\Services\JobSearch\FoundJob;
use App\Services\JobSearch\JobSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

/**
 * Any RSS 2.0 or Atom feed of job postings.
 */
class RssSource implements JobSource
{
    public function __construct(private string $url, private string $name = 'rss') {}

    public function available(): bool
    {
        return true;
    }

    public function fetch(JobSearchPreference $preferences): array
    {
        $body = Http::timeout(20)->get($this->url)->throw()->body();

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            throw new RuntimeException("Feed {$this->url} is not valid XML.");
        }

        $entries = isset($xml->channel->item) ? $xml->channel->item : ($xml->entry ?? []);
        $jobs = [];

        foreach ($entries as $entry) {
            $title = trim((string) $entry->title);
            $link = (string) ($entry->link['href'] ?? $entry->link);

            if ($title === '' || $link === '') {
                continue;
            }

            $company = null;

            // "Company: Job title" is how We Work Remotely and several others format titles.
            if (preg_match('/^([^:]{2,60}):\s+(.+)$/u', $title, $parts)) {
                [, $company, $title] = $parts;
            }

            $jobs[] = new FoundJob(
                source: $this->name,
                externalId: (string) ($entry->guid ?? $entry->id ?? $link),
                title: $title,
                company: $company,
                location: null,
                remote: null,
                url: $link,
                description: FoundJob::plainText((string) ($entry->description ?? $entry->content ?? $entry->summary ?? '')),
                postedAt: $this->date((string) ($entry->pubDate ?? $entry->updated ?? $entry->published ?? '')),
            );
        }

        return $jobs;
    }

    private function date(string $value): ?Carbon
    {
        try {
            return $value === '' ? null : Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
