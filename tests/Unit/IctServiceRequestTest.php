<?php

namespace Tests\Unit;

use App\Models\IctServiceRequest;
use App\Models\IctSearchIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IctServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_fields()
    {
        $data = [
            'control_no' => '123',
            'name' => 'John Doe',
            'position' => 'Manager',
            'office_unit' => 'IT',
            'contact_no' => '1234567890',
            'request_description' => 'Test request',
        ];
        $model = new IctServiceRequest($data);
        foreach ($data as $key => $value) {
            $this->assertEquals($value, $model->$key);
        }
    }

    public function test_search_indexes_relationship()
    {
        $request = IctServiceRequest::factory()->create();
        $index = $request->searchIndexes()->create(['hash' => 'abc123']);
        $this->assertTrue($request->searchIndexes->contains($index));
    }

    public function test_generate_name_index()
    {
        $index = IctServiceRequest::generateNameIndex('John Doe');
        $this->assertNotNull($index);
    }
}
