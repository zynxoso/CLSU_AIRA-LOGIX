<?php

namespace App\Services\Extraction;

use Illuminate\Support\Facades\Log;
use ZipArchive;
use DOMDocument;
use DOMXPath;

class DocxCheckboxExtractor
{
    private const OPTIONS = [
        'ICT Technical Support' => 'ICT Technical Support',
        'System Development'    => 'System Development/Enhancement',
        'Network/Internet'      => 'Network/Internet Connection',
        'Others'                => 'Others',
    ];

    /**
     * Tries XML first, falls back to text regex.
     */
    public function determineRequestType(string $filePath, string $extractedText): ?string
    {
        $xmlResult = $this->extractFromXml($filePath);

        if ($xmlResult !== null) {
            Log::debug("DocxCheckboxExtractor: XML checkbox result: {$xmlResult}");
            return $xmlResult;
        }

        $regexResult = $this->preParseFromText($extractedText);
        Log::debug("DocxCheckboxExtractor: Regex checkbox result: " . ($regexResult ?? 'null'));

        return $regexResult;
    }

    protected function extractFromXml(string $filePath): ?string
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            Log::warning('DocxCheckboxExtractor: could not open DOCX as zip');
            return null;
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$xml) {
            Log::warning('DocxCheckboxExtractor: document.xml not found in DOCX');
            return null;
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w',   'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('w14', 'http://schemas.microsoft.com/office/word/2010/wordml');

        $checkedSdts = $xpath->query('//w:sdt[.//w14:checkbox[w14:checked[@w14:val="1"]]]');

        if ($checkedSdts === false || $checkedSdts->length === 0) {
            Log::debug('DocxCheckboxExtractor: no w14:checkbox checked=1 found in XML');
            return null;
        }

        foreach ($checkedSdts as $sdt) {
            $textNodes = $xpath->query('.//w:t', $sdt);
            $label = '';
            foreach ($textNodes as $t) {
                $label .= $t->textContent;
            }
            $label = trim($label);

            foreach (self::OPTIONS as $keyword => $fullLabel) {
                if (stripos($label, $keyword) !== false) {
                    return $fullLabel;
                }
            }
        }

        return null;
    }

    protected function preParseFromText(string $text): ?string
    {
        foreach (self::OPTIONS as $keyword => $label) {
            if (preg_match('/☒[^☐☒]*' . preg_quote($keyword, '/') . '/u', $text)) {
                return $label;
            }
        }
        return null;
    }
}
