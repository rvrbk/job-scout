<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Rebuilds the country and city lookup that LocationMatcher uses, from GeoNames (CC BY 4.0, geonames.org).
 */
#[Signature('locations:build')]
#[Description('Rebuild resources/data/countries.json and cities.json from GeoNames')]
class BuildLocationData extends Command
{
    private const string CITIES_URL = 'https://download.geonames.org/export/dump/cities15000.zip';

    private const string COUNTRIES_URL = 'https://download.geonames.org/export/dump/countryInfo.txt';

    /** Words that are also city names somewhere but, in a posting, mean something else. */
    private const array STOP_WORDS = [
        'remote', 'hybrid', 'onsite', 'anywhere', 'worldwide', 'global', 'europe', 'asia', 'africa', 'america',
        'americas', 'emea', 'apac', 'latam', 'office', 'region', 'united', 'central', 'north', 'south', 'east', 'west',
    ];

    public function handle(): int
    {
        ini_set('memory_limit', '512M');

        $countries = $this->countries();
        $cities = $this->cities(array_flip(array_map(fn (array $country) => Str::ascii(mb_strtolower($country[0])), $countries)));

        file_put_contents(resource_path('data/countries.json'), json_encode($countries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        file_put_contents(resource_path('data/cities.json'), json_encode($cities, JSON_UNESCAPED_SLASHES));

        $this->info(count($countries).' countries, '.count($cities).' city names written.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, array{string, string}> ISO code => [country name, continent code]
     */
    private function countries(): array
    {
        $countries = [];

        foreach (explode("\n", Http::timeout(60)->get(self::COUNTRIES_URL)->throw()->body()) as $line) {
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $columns = explode("\t", $line);
            $countries[$columns[0]] = [$columns[4], $columns[8]];
        }

        return $countries;
    }

    /**
     * @param  array<string, int>  $countryNames
     * @return array<string, string> lowercase ascii city name => ISO code of the most populous city with that name
     */
    private function cities(array $countryNames): array
    {
        $zip = tempnam(sys_get_temp_dir(), 'cities');
        file_put_contents($zip, Http::timeout(120)->get(self::CITIES_URL)->throw()->body());

        $archive = new ZipArchive;
        $contents = $archive->open($zip) === true ? $archive->getFromName('cities15000.txt') : false;
        $archive->close();
        unlink($zip);

        if ($contents === false) {
            throw new RuntimeException('Could not read the GeoNames cities file.');
        }

        $skip = array_flip(self::STOP_WORDS) + $countryNames;

        $primary = [];
        $alternates = [];

        foreach (explode("\n", $contents) as $line) {
            if ($line === '') {
                continue;
            }

            $row = explode("\t", $line);
            $population = (int) $row[14];

            foreach ([$row[1], $row[2]] as $name) {
                $this->keep($primary, $name, $row[8], $population, $skip, 3);
            }

            // Well-known exonyms and local spellings (Den Haag, Nuremberg) only for the bigger cities, to keep noise out.
            if ($population >= 100000) {
                foreach (explode(',', $row[3]) as $name) {
                    if (preg_match("/^[A-Za-z][A-Za-z '\\-]{3,}$/", $name)) {
                        $this->keep($alternates, $name, $row[8], $population, $skip, 4);
                    }
                }
            }
        }

        $cities = array_map(fn (array $city) => $city[1], $primary + $alternates);
        ksort($cities);

        return $cities;
    }

    /**
     * @param  array<string, array{int, string}>  $into
     * @param  array<string, mixed>  $skip
     */
    private function keep(array &$into, string $name, string $country, int $population, array $skip, int $minLength): void
    {
        $key = Str::ascii(mb_strtolower(trim($name)));

        if (mb_strlen($key) < $minLength || isset($skip[$key])) {
            return;
        }

        if (! isset($into[$key]) || $population > $into[$key][0]) {
            $into[$key] = [$population, $country];
        }
    }
}
