<?php

namespace App\Enums;

use App\Services\JobSearch\LocationMatcher;

/**
 * The language a CV or cover letter is written in. The ledger can be in any language; the model translates
 * as it drafts, and the fixed headings of the CV (Experience, Skills, ...) come from here.
 */
enum DocumentLanguage: string
{
    case English = 'en';
    case Dutch = 'nl';
    case German = 'de';
    case French = 'fr';
    case Spanish = 'es';
    case Italian = 'it';
    case Portuguese = 'pt';
    case Polish = 'pl';
    case Swedish = 'sv';
    case Danish = 'da';
    case Norwegian = 'no';
    case Finnish = 'fi';

    /** The country a user is from, and the language their documents default to. Anything not listed gets English. */
    private const array COUNTRY_LANGUAGES = [
        'NL' => 'nl', 'SR' => 'nl', 'BE' => 'nl',
        'DE' => 'de', 'AT' => 'de', 'CH' => 'de', 'LI' => 'de', 'LU' => 'de',
        'FR' => 'fr', 'MC' => 'fr',
        'ES' => 'es', 'MX' => 'es', 'AR' => 'es', 'CO' => 'es', 'CL' => 'es', 'PE' => 'es', 'VE' => 'es', 'EC' => 'es', 'UY' => 'es',
        'IT' => 'it', 'SM' => 'it',
        'PT' => 'pt', 'BR' => 'pt',
        'PL' => 'pl', 'SE' => 'sv', 'DK' => 'da', 'NO' => 'no', 'FI' => 'fi',
    ];

    /**
     * The language of the country a free-text location points to ("Utrecht, Netherlands"), or English
     * when it names no country, or names several.
     */
    public static function forLocation(?string $location): self
    {
        $countries = filled($location) ? LocationMatcher::countriesIn($location) : [];

        return count($countries) === 1
            ? (self::tryFrom(self::COUNTRY_LANGUAGES[$countries[0]] ?? '') ?? self::English)
            : self::English;
    }

    /**
     * The name of the language in that language, so people recognise it.
     */
    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Dutch => 'Nederlands',
            self::German => 'Deutsch',
            self::French => 'Français',
            self::Spanish => 'Español',
            self::Italian => 'Italiano',
            self::Portuguese => 'Português',
            self::Polish => 'Polski',
            self::Swedish => 'Svenska',
            self::Danish => 'Dansk',
            self::Norwegian => 'Norsk',
            self::Finnish => 'Suomi',
        };
    }

    /**
     * The English name, used in the instruction given to the model.
     */
    public function englishName(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Dutch => 'Dutch',
            self::German => 'German',
            self::French => 'French',
            self::Spanish => 'Spanish',
            self::Italian => 'Italian',
            self::Portuguese => 'Portuguese',
            self::Polish => 'Polish',
            self::Swedish => 'Swedish',
            self::Danish => 'Danish',
            self::Norwegian => 'Norwegian',
            self::Finnish => 'Finnish',
        };
    }

    /**
     * The instruction given to the model.
     */
    public function instruction(): string
    {
        return "Write every sentence of the document in {$this->englishName()}, whatever language the ledger or the job posting is in: translate the facts as you word the sentences, and keep the meaning exactly (a translation may not add or inflate anything). Skill names, technologies, product and company names stay as written. The gaps notes stay in English.";
    }

    /**
     * A heading or label of the document, in this language.
     *
     * @param  'summary'|'experience'|'skills'|'education'|'source'  $key
     */
    public function term(string $key): string
    {
        $terms = match ($this) {
            self::English => ['summary' => 'Summary', 'experience' => 'Experience', 'skills' => 'Skills', 'education' => 'Education & certifications', 'source' => 'source'],
            self::Dutch => ['summary' => 'Samenvatting', 'experience' => 'Werkervaring', 'skills' => 'Vaardigheden', 'education' => 'Opleiding & certificaten', 'source' => 'bron'],
            self::German => ['summary' => 'Zusammenfassung', 'experience' => 'Berufserfahrung', 'skills' => 'Kenntnisse', 'education' => 'Ausbildung & Zertifikate', 'source' => 'Quelle'],
            self::French => ['summary' => 'Résumé', 'experience' => 'Expérience', 'skills' => 'Compétences', 'education' => 'Formation & certifications', 'source' => 'source'],
            self::Spanish => ['summary' => 'Resumen', 'experience' => 'Experiencia', 'skills' => 'Habilidades', 'education' => 'Formación y certificaciones', 'source' => 'fuente'],
            self::Italian => ['summary' => 'Profilo', 'experience' => 'Esperienza', 'skills' => 'Competenze', 'education' => 'Formazione e certificazioni', 'source' => 'fonte'],
            self::Portuguese => ['summary' => 'Resumo', 'experience' => 'Experiência', 'skills' => 'Competências', 'education' => 'Formação e certificações', 'source' => 'fonte'],
            self::Polish => ['summary' => 'Podsumowanie', 'experience' => 'Doświadczenie', 'skills' => 'Umiejętności', 'education' => 'Wykształcenie i certyfikaty', 'source' => 'źródło'],
            self::Swedish => ['summary' => 'Sammanfattning', 'experience' => 'Erfarenhet', 'skills' => 'Kompetenser', 'education' => 'Utbildning & certifikat', 'source' => 'källa'],
            self::Danish => ['summary' => 'Resumé', 'experience' => 'Erfaring', 'skills' => 'Kompetencer', 'education' => 'Uddannelse & certificeringer', 'source' => 'kilde'],
            self::Norwegian => ['summary' => 'Sammendrag', 'experience' => 'Erfaring', 'skills' => 'Kompetanse', 'education' => 'Utdanning og sertifiseringer', 'source' => 'kilde'],
            self::Finnish => ['summary' => 'Yhteenveto', 'experience' => 'Työkokemus', 'skills' => 'Osaaminen', 'education' => 'Koulutus ja sertifikaatit', 'source' => 'lähde'],
        };

        return $terms[$key];
    }
}
