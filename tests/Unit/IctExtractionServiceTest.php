<?php

namespace Tests\Unit;

use Mockery;

use App\Services\IctExtractionService;
use App\Services\Extraction\SpreadsheetExtractor;
use App\Services\Extraction\DocxTextExtractor;
use App\Services\Extraction\DocxCheckboxExtractor;
use App\Services\Extraction\AiParserService;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class IctExtractionServiceTest extends TestCase
{
    public function test_extract_from_file_spreadsheet()
    {
        $spreadsheetExtractor = Mockery::mock(SpreadsheetExtractor::class);
        $spreadsheetExtractor->shouldReceive('extract')->andReturn([['row1'], ['row2']]);
        $service = new IctExtractionService(
            $spreadsheetExtractor,
            Mockery::mock(DocxTextExtractor::class),
            Mockery::mock(DocxCheckboxExtractor::class),
            Mockery::mock(AiParserService::class)
        );
        $result = $service->extractFromFile('file.xlsx');
        $this->assertEquals([['row1'], ['row2']], $result);
    }

    public function test_extract_from_file_unsupported_format()
    {
        $this->expectException(\Exception::class);
        $service = new IctExtractionService(
            Mockery::mock(SpreadsheetExtractor::class),
            Mockery::mock(DocxTextExtractor::class),
            Mockery::mock(DocxCheckboxExtractor::class),
            Mockery::mock(AiParserService::class)
        );
        $service->extractFromFile('file.txt');
    }
}
