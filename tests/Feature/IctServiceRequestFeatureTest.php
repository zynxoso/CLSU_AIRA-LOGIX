<?php

namespace Tests\Feature;

use App\Models\IctServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IctServiceRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_ict_service_request()
    {
        $data = [
            'control_no' => '123',
            'name' => 'John Doe',
            'position' => 'Manager',
            'office_unit' => 'IT',
            'contact_no' => '1234567890',
            'request_description' => 'Test request',
        ];
        $request = IctServiceRequest::create($data);
        $this->assertDatabaseHas('ict_service_requests', ['id' => $request->id, 'control_no' => '123']);
    }

    public function test_list_ict_service_requests()
    {
        IctServiceRequest::factory()->count(2)->create();
        $this->assertEquals(2, IctServiceRequest::count());
    }
}
