<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\IctServiceRequest;
use App\Models\MisoAccomplishment;
use App\Models\User;

class DashboardControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_page_loads_for_authorized_user()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['dashboard'],
        ]);
        IctServiceRequest::factory()->count(2)->create(['status' => 'Open']);
        IctServiceRequest::factory()->count(1)->create(['status' => 'Resolved']);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('dashboard'); // Inertia page name
    }

    public function test_dashboard_page_forbidden_for_unauthorized_user()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => [],
        ]);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(403);
    }

    public function test_dashboard_miso_tab_loads_for_authorized_user(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['dashboard'],
        ]);

        MisoAccomplishment::factory()->create([
            'category' => MisoAccomplishment::CATEGORY_DATA_MANAGEMENT,
            'overall_status' => 'On Track',
            'implementing_unit' => 'MISO',
            'project_lead' => 'Lead One',
        ]);

        MisoAccomplishment::factory()->create([
            'category' => MisoAccomplishment::CATEGORY_NETWORK,
            'overall_status' => 'Delayed',
            'implementing_unit' => 'MISO-UNOC',
            'project_lead' => 'Lead Two',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', [
            'tab' => 'miso-data',
            'status' => 'On Track',
        ]));

        $response->assertStatus(200);
        $response->assertSee('dashboard');
    }

    public function test_miso_intake_page_loads_for_dashboard_user(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['dashboard'],
        ]);

        $response = $this->actingAs($user)->get(route('miso.create', [
            'tab' => 'miso-data',
        ]));

        $response->assertStatus(200);
        $response->assertSee('miso-intake');
    }

    public function test_miso_smart_scan_and_batch_import_flow(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['smart_scan'],
        ]);

        $scanPage = $this->actingAs($user)->get(route('miso.smart-scan', [
            'tab' => 'miso-data',
        ]));

        $scanPage->assertStatus(200);
        $scanPage->assertSee('miso-smart-scan');

        $response = $this->actingAs($user)->postJson(route('api.miso.store-batch'), [
            'category' => MisoAccomplishment::CATEGORY_DATA_MANAGEMENT,
            'requests' => [
                [
                    'record_no' => '1',
                    'project_title' => 'Automated MISO Import',
                    'project_lead' => 'Test Lead',
                    'overall_status' => 'On Track',
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('miso_accomplishments', [
            'category' => MisoAccomplishment::CATEGORY_DATA_MANAGEMENT,
            'project_title' => 'Automated MISO Import',
            'project_lead' => 'Test Lead',
        ]);
    }

    public function test_miso_csv_and_xlsx_exports_for_dashboard_user(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['dashboard'],
        ]);

        MisoAccomplishment::factory()->create([
            'category' => MisoAccomplishment::CATEGORY_DATA_MANAGEMENT,
            'record_no' => '12',
            'project_title' => 'Exportable MISO Project',
            'project_lead' => 'Export Lead',
        ]);

        $csvResponse = $this->actingAs($user)->get(route('miso.export-csv', [
            'tab' => 'miso-data',
            'search' => 'Exportable',
        ]));

        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('content-type', 'text/csv; charset=utf-8');
        $csvContent = $csvResponse->streamedContent();
        $this->assertStringContainsString('Project Title', $csvContent);
        $this->assertStringContainsString('Exportable MISO Project', $csvContent);

        $xlsxResponse = $this->actingAs($user)->get(route('miso.export-xlsx', [
            'tab' => 'miso-data',
        ]));

        $xlsxResponse->assertStatus(200);
        $xlsxResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
