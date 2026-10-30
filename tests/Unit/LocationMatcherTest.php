<?php

namespace Tests\Unit;

use App\Services\JobSearch\LocationMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocationMatcherTest extends TestCase
{
    /**
     * @return array<string, array{string, list<string>, bool}>
     */
    public static function cases(): array
    {
        return [
            'dutch city for the Netherlands' => ['Utrecht', ['Netherlands'], true],
            'local spelling of a dutch city' => ['Den Haag', ['Netherlands'], true],
            'city and country' => ['Amsterdam, Netherlands', ['Netherlands'], true],
            'country short code' => ['Eindhoven', ['NL'], true],
            'ugandan city for Uganda' => ['Kampala', ['Uganda'], true],
            'another ugandan city' => ['Entebbe', ['Uganda'], true],
            'german city is not the Netherlands' => ['Berlin', ['Netherlands'], false],
            'named country wins over a city of the same name' => ['Amsterdam, NY', ['Netherlands'], false],
            'us state code' => ['Austin, TX', ['USA'], true],
            'country alias' => ['Bulgaria, Poland, UK, USA', ['United Kingdom'], true],
            'any of several listed places' => ['Berlin', ['Netherlands', 'Germany'], true],
            'unplaceable location' => ['Somewhere nice', ['Netherlands'], false],
            'a listed city matches as text' => ['Heerenveen, Friesland', ['Heerenveen'], true],
            'a listed city does not match a longer word' => ['Hamburger Hill', ['Hamburg'], false],
            'a region contains the listed country' => ['Europe', ['Netherlands'], true],
            'a region does not contain a foreign country' => ['Europe', ['Uganda'], false],
            'emea covers africa' => ['EMEA', ['Uganda'], true],
            'emea covers europe' => ['EMEA', ['Netherlands'], true],
            'work mode words are ignored' => ['Remote - Europe', ['Netherlands'], true],
            'dach is not the Netherlands' => ['DACH', ['Netherlands'], false],
            'benelux contains the Netherlands' => ['Benelux', ['Netherlands'], true],
            'apac is not the Netherlands' => ['APAC', ['Netherlands'], false],
            'north america' => ['North America', ['USA'], true],
            'latin america' => ['LATAM', ['Brazil'], true],
            'worldwide fits anywhere' => ['Worldwide', ['Uganda'], true],
            'a listed region matches cities in it' => ['Utrecht', ['Europe'], true],
            'a listed region does not match cities outside it' => ['Kampala', ['Europe'], false],
            'a city beats a region in the same posting' => ['Berlin, Europe', ['France'], false],
            'accents are ignored' => ['München', ['Germany'], true],
        ];
    }

    /**
     * @param  list<string>  $locations
     */
    #[DataProvider('cases')]
    public function test_it_matches_postings_to_the_places_the_user_listed(string $place, array $locations, bool $expected)
    {
        $this->assertSame($expected, LocationMatcher::matches($place, $locations));
    }
}
