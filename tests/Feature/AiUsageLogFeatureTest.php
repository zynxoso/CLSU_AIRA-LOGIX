<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageLogFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_ai_usage_log()
    {
        $user = User::factory()->create();
        $data = [
            'service' => 'vision_extraction',
            'model' => 'gpt-4',
            'user_id' => $user->id,
            'extraction_method' => 'ocr',
            'source_file_type' => 'pdf',
            'prompt_tokens' => 10,
            'completion_tokens' => 20,
            'total_tokens' => 30,
            'estimated_cost' => 0.123456,
            'metadata' => ['foo' => 'bar'],
        ];
        $log = AiUsageLog::create($data);
        $this->assertDatabaseHas('ai_usage_logs', ['id' => $log->id, 'service' => 'vision_extraction']);
    }

    public function test_list_ai_usage_logs()
    {
        AiUsageLog::factory()->count(3)->create();
        $this->assertEquals(3, AiUsageLog::count());
    }
}
