<?php

namespace App\Services\Cv;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;
use ZipArchive;

/**
 * Pulls the plain text out of an uploaded CV: PDF, Word (.docx) or a text file.
 */
class CvTextExtractor
{
    public function extract(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        try {
            $text = match (strtolower($file->getClientOriginalExtension())) {
                'pdf' => (new Parser)->parseFile($path)->getText(),
                'docx' => $this->docx($path),
                default => (string) file_get_contents($path),
            };
        } catch (Throwable $exception) {
            throw new RuntimeException(__('That file could not be read.'), previous: $exception);
        }

        $text = trim((string) preg_replace(["/[ \t]+/", "/\n{3,}/"], [' ', "\n\n"], str_replace("\r", '', $text)));

        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }

        if ($text === '') {
            throw new RuntimeException(__('No text found in that file. A scanned CV needs to be run through OCR first.'));
        }

        return $text;
    }

    private function docx(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true || ($xml = $zip->getFromName('word/document.xml')) === false) {
            throw new RuntimeException('Not a Word document.');
        }

        $zip->close();

        $xml = preg_replace(['/<\/w:p>/', '/<w:(br|tab)\b[^>]*\/>/'], ["\n", ' '], $xml);

        return html_entity_decode(strip_tags((string) $xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
