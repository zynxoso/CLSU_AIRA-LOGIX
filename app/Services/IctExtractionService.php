<?php

namespace App\Services;

use App\Services\Extraction\SpreadsheetExtractor;
use App\Services\Extraction\DocxTextExtractor;
use App\Services\Extraction\DocxCheckboxExtractor;
use App\Services\Extraction\AiParserService;
use App\Support\SupportedFileExtensions;
use Illuminate\Support\Facades\Log;
use Exception;

class IctExtractionService
{
    private SpreadsheetExtractor $spreadsheetExtractor;
    private DocxTextExtractor $docxTextExtractor;
    private DocxCheckboxExtractor $docxCheckboxExtractor;
    private AiParserService $aiParserService;

    public function __construct(
        SpreadsheetExtractor $spreadsheetExtractor,
        DocxTextExtractor $docxTextExtractor,
        DocxCheckboxExtractor $docxCheckboxExtractor,
        AiParserService $aiParserService
    ) {
        $this->spreadsheetExtractor = $spreadsheetExtractor;
        $this->docxTextExtractor = $docxTextExtractor;
        $this->docxCheckboxExtractor = $docxCheckboxExtractor;
        $this->aiParserService = $aiParserService;
    }

    /**
     * Extract data from a filled ICT Service Request Form
     */
    public function extractFromFile(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        Log::info("IctExtractionService: Starting extraction for [{$extension}] file.");

        if (in_array($extension, SupportedFileExtensions::SPREADSHEETS, true)) {
            Log::info("IctExtractionService: Identified as spreadsheet. Redirecting to spreadsheet extractor.");
            $rows = $this->spreadsheetExtractor->extract($filePath);
            return $rows; // Return all rows for spreadsheet preview
        }

        if (in_array($extension, SupportedFileExtensions::IMAGES, true)) {
             Log::info("IctExtractionService: Identified as image. Redirecting to image extraction service.");
             return app(IctImageExtractionService::class)->extract($filePath);
        }

        if (!in_array($extension, SupportedFileExtensions::DOCS, true)) {
            Log::warning("IctExtractionService: Attempted to extract unsupported format [{$extension}]");
            throw new Exception("Unsupported file format. Please use DOCX, CSV, XLSX, or an image.");
        }

        Log::info("IctExtractionService: Identified as DOCX. Extracting text via PhpWord.");
        $text = $this->docxTextExtractor->extractText($filePath);
        Log::debug("IctExtractionService: Extracted text length: " . strlen($text) . " chars.");

        Log::info("IctExtractionService: Scanning for DOCX Checkboxes.");
        $checkboxResult = $this->docxCheckboxExtractor->determineRequestType($filePath, $text);

        Log::info("IctExtractionService: Proceeding to AI parsing.");
        return $this->aiParserService->parse($text, $checkboxResult);
    }
}
