<?php

namespace Tests\Unit;

use App\Services\AiBudgetManager;
use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiBudgetManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_spend_returns_true_when_under_thresholds()
    {
        Cache::flush();
        config(['services.ai.budget_threshold' => 10.00]);
        config(['services.ai.daily_budget_threshold' => 1.00]);
        config(['services.ai.user_daily_budget_threshold' => 0.25]);
        $this->assertTrue(AiBudgetManager::canSpend(0.01, 1));
    }

    public function test_can_spend_returns_false_when_over_monthly()
    {
        Cache::flush();
        config(['services.ai.budget_threshold' => 0.01]);
        AiUsageLog::factory()->create(['estimated_cost' => 0.02]);
        $this->assertFalse(AiBudgetManager::canSpend(0.01));
    }

    public function test_increment_monthly_cost_increases_cache()
    {
        Cache::flush();
        AiBudgetManager::incrementMonthlyCost(1.23, 1);
        $this->assertGreaterThanOrEqual(1.23, AiBudgetManager::getMonthlyCost());
        $this->assertGreaterThanOrEqual(1.23, AiBudgetManager::getDailyCost());
        $this->assertGreaterThanOrEqual(1.23, AiBudgetManager::getUserDailyCost(1));
    }

    public function test_get_formatted_total_cost_matches_monthly()
    {
        Cache::flush();
        AiUsageLog::factory()->create(['estimated_cost' => 2.34]);
        $this->assertEquals(AiBudgetManager::getMonthlyCost(), AiBudgetManager::getFormattedTotalCost());
    }
}
