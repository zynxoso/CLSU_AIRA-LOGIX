<?php

namespace Tests\Unit;

use App\Services\IctTemplateService;
use App\Models\IctServiceRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Mockery;

class IctTemplateServiceTest extends TestCase
{
    public function test_generate_docx_throws_if_template_missing()
    {
        $request = Mockery::mock(IctServiceRequest::class);
        $service = new IctTemplateService();
        $this->expectException(\Exception::class);
        $service->generateDocx($request);
    }

    public function test_generate_bulk_docx_zip_throws_if_empty()
    {
        $service = new IctTemplateService();
        $this->expectException(\Exception::class);
        $service->generateBulkDocxZip(Collection::make([]));
    }
}
