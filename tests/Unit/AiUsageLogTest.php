<?php

namespace Tests\Unit;

use App\Models\AiUsageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_fields()
    {
        $data = [
            'service' => 'vision_extraction',
            'model' => 'gpt-4',
            'user_id' => 1,
            'extraction_method' => 'ocr',
            'source_file_type' => 'pdf',
            'prompt_tokens' => 10,
            'completion_tokens' => 20,
            'total_tokens' => 30,
            'estimated_cost' => 0.123456,
            'metadata' => ['foo' => 'bar'],
        ];
        $log = new AiUsageLog($data);
        foreach ($data as $key => $value) {
            $this->assertEquals($value, $log->$key);
        }
    }

    public function test_user_relationship()
    {
        $user = User::factory()->create();
        $log = AiUsageLog::factory()->create(['user_id' => $user->id]);
        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($user->id, $log->user->id);
    }

    public function test_metadata_casting()
    {
        $log = AiUsageLog::factory()->create(['metadata' => ['foo' => 'bar']]);
        $this->assertIsArray($log->metadata);
        $this->assertEquals('bar', $log->metadata['foo']);
    }
}
