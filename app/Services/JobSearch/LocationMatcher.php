<?php

namespace App\Services\JobSearch;

use Illuminate\Support\Str;

/**
 * Decides whether a posting's location is one the user listed.
 *
 * Postings often name only a city, so a listed country also matches the cities in it, and postings
 * often name a region ("Europe", "EMEA"), which matches every country in it. The lookup comes from
 * GeoNames (see `locations:build`). Anything else a user lists, such as a specific city, is matched as plain text.
 */
class LocationMatcher
{
    /** Names and spellings that GeoNames does not list as the country's name. */
    private const array ALIASES = [
        'usa' => 'US', 'u.s.a.' => 'US', 'u.s.' => 'US', 'united states of america' => 'US',
        'uk' => 'GB', 'u.k.' => 'GB', 'great britain' => 'GB', 'england' => 'GB', 'scotland' => 'GB', 'wales' => 'GB', 'northern ireland' => 'GB',
        'holland' => 'NL', 'the netherlands' => 'NL', 'nederland' => 'NL',
        'deutschland' => 'DE', 'osterreich' => 'AT', 'schweiz' => 'CH', 'suisse' => 'CH', 'svizzera' => 'CH',
        'espana' => 'ES', 'italia' => 'IT', 'belgie' => 'BE', 'belgique' => 'BE', 'polska' => 'PL', 'sverige' => 'SE',
        'norge' => 'NO', 'danmark' => 'DK', 'suomi' => 'FI', 'eire' => 'IE', 'czech republic' => 'CZ', 'uae' => 'AE',
        'russian federation' => 'RU', 'korea' => 'KR', 'turkiye' => 'TR', 'brasil' => 'BR',
    ];

    private const array US_STATES = [
        'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO',
        'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY', 'DC',
    ];

    private const array EU = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
    ];

    private const array MIDDLE_EAST = ['AE', 'SA', 'QA', 'KW', 'BH', 'OM', 'YE', 'IQ', 'IR', 'IL', 'PS', 'JO', 'LB', 'SY', 'TR', 'EG'];

    private const array NORTH_AFRICA = ['DZ', 'EG', 'LY', 'MA', 'TN'];

    /** Region names and the countries they stand for. Continents come from the data, see regions(). */
    private const array FIXED_REGIONS = [
        'eu' => self::EU, 'european union' => self::EU,
        'eea' => [...self::EU, 'IS', 'LI', 'NO'],
        'dach' => ['DE', 'AT', 'CH'],
        'benelux' => ['BE', 'NL', 'LU'],
        'nordics' => ['SE', 'NO', 'DK', 'FI', 'IS'], 'nordic' => ['SE', 'NO', 'DK', 'FI', 'IS'], 'scandinavia' => ['SE', 'NO', 'DK'],
        'uk and ireland' => ['GB', 'IE'], 'uk & ireland' => ['GB', 'IE'], 'uk/ireland' => ['GB', 'IE'],
        'middle east' => self::MIDDLE_EAST,
        'mena' => [...self::MIDDLE_EAST, ...self::NORTH_AFRICA],
        'north america' => ['US', 'CA', 'MX'],
    ];

    /** Words that only describe how a job is done, not where. */
    private const string WORK_MODE = '/\b(remote|hybrid|on-?site|based|in)\b/';

    /** @var array<string, array{string, string}>|null ISO code => [country name, continent code] */
    private static ?array $countries = null;

    /** @var array<string, list<string>>|null normalised region name => ISO codes */
    private static ?array $regions = null;

    /** @var array<string, string>|null normalised country name or alias => ISO code */
    private static ?array $countryNames = null;

    /** @var array<string, string>|null normalised city name => ISO code */
    private static ?array $cities = null;

    /**
     * @param  list<string>  $locations
     */
    public static function matches(string $place, array $locations): bool
    {
        $countriesInPlace = null;

        foreach ($locations as $location) {
            if (trim($location) === '') {
                continue;
            }

            $countries = self::countriesFor($location);

            if ($countries === null) {
                if (self::mentions($place, $location)) {
                    return true;
                }

                continue;
            }

            $countriesInPlace ??= self::countriesIn($place);

            if (array_intersect($countries, $countriesInPlace) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * The countries a posting's location points to: the ones it names, otherwise the ones its cities are in, otherwise the regions it names.
     *
     * @return list<string> ISO codes
     */
    public static function countriesIn(string $place): array
    {
        $tokens = preg_split('/[,;\/|()]+|\s[-–—]\s/u', $place) ?: [];
        $tokens = array_values(array_filter(array_map('trim', [...$tokens, $place])));

        $named = [];
        $regions = [];
        $cities = [];

        foreach ($tokens as $token) {
            if (preg_match('/^[A-Z]{2}$/', $token) === 1) {
                if (in_array($token, self::US_STATES, true)) {
                    $named[] = 'US';

                    continue;
                }

                if (isset(self::countries()[$token])) {
                    $named[] = $token;

                    continue;
                }
            }

            // "Remote Europe" and "Hybrid, Berlin" say how, not where, so retry without those words.
            foreach (array_unique([self::normalise($token), trim(preg_replace(self::WORK_MODE, '', self::normalise($token)) ?? '')]) as $key) {
                if ($key === '') {
                    continue;
                }

                $named[] = self::countryNames()[$key] ?? null;
                $regions = [...$regions, ...(self::regions()[$key] ?? [])];
                $cities[] = self::cities()[$key] ?? null;
            }
        }

        foreach ([$named, $cities, $regions] as $found) {
            $found = array_values(array_unique(array_filter($found)));

            if ($found !== []) {
                return $found;
            }
        }

        return [];
    }

    /**
     * The countries a user's listed location stands for, or null when it is something else (a city).
     *
     * @return list<string>|null
     */
    private static function countriesFor(string $location): ?array
    {
        $location = trim($location);

        if (preg_match('/^[A-Za-z]{2}$/', $location) === 1 && isset(self::countries()[strtoupper($location)])) {
            return [strtoupper($location)];
        }

        $key = self::normalise($location);

        if (isset(self::countryNames()[$key])) {
            return [self::countryNames()[$key]];
        }

        return self::regions()[$key] ?? null;
    }

    private static function mentions(string $place, string $location): bool
    {
        return preg_match('/(?<![\p{L}])'.preg_quote(self::normalise($location), '/').'(?![\p{L}])/u', self::normalise($place)) === 1;
    }

    private static function normalise(string $text): string
    {
        return trim(mb_strtolower(Str::ascii($text)));
    }

    /**
     * @return array<string, array{string, string}>
     */
    private static function countries(): array
    {
        return self::$countries ??= json_decode(file_get_contents(resource_path('data/countries.json')) ?: '[]', true);
    }

    /**
     * @return array<string, string>
     */
    private static function countryNames(): array
    {
        if (self::$countryNames === null) {
            self::$countryNames = self::ALIASES;

            foreach (self::countries() as $code => [$name]) {
                $name = self::normalise($name);

                self::$countryNames[$name] = $code;
                self::$countryNames[preg_replace('/^the /', '', $name)] = $code;
            }
        }

        return self::$countryNames;
    }

    /**
     * @return array<string, list<string>>
     */
    private static function regions(): array
    {
        if (self::$regions !== null) {
            return self::$regions;
        }

        $in = fn (string ...$continents) => array_keys(array_filter(self::countries(), fn (array $country) => in_array($country[1], $continents, true)));

        $europe = $in('EU');
        $latinAmerica = [...$in('SA'), ...array_diff($in('NA'), ['US', 'CA', 'GL', 'BM', 'PM'])];

        return self::$regions = [
            ...self::FIXED_REGIONS,
            'europe' => $europe, 'european' => $europe,
            'emea' => array_values(array_unique([...$europe, ...$in('AF'), ...self::MIDDLE_EAST])),
            'africa' => $in('AF'),
            'asia' => $in('AS'), 'apac' => [...$in('AS', 'OC')], 'asia pacific' => [...$in('AS', 'OC')],
            'oceania' => $in('OC'),
            'latam' => $latinAmerica, 'latin america' => $latinAmerica,
            'south america' => $in('SA'),
            'americas' => $in('NA', 'SA'),
            // Worldwide postings fit anywhere.
            'worldwide' => array_keys(self::countries()), 'global' => array_keys(self::countries()),
            'anywhere' => array_keys(self::countries()), 'international' => array_keys(self::countries()),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function cities(): array
    {
        return self::$cities ??= json_decode(file_get_contents(resource_path('data/cities.json')) ?: '[]', true);
    }
}
