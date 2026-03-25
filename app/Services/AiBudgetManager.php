<?php

namespace App\Services;

use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Cache;

class AiBudgetManager
{
    public static function canSpend(float $estimatedCost = 0.0, ?int $userId = null): bool
    {
        $monthlyThreshold = (float) config('services.ai.budget_threshold', 10.00);
        $dailyThreshold = (float) config('services.ai.daily_budget_threshold', 1.00);
        $userDailyThreshold = (float) config('services.ai.user_daily_budget_threshold', 0.25);

        $monthlyTotal = self::getMonthlyCost();
        $dailyTotal = self::getDailyCost();

        if (($monthlyTotal + max(0.0, $estimatedCost)) > $monthlyThreshold) {
            return false;
        }

        if (($dailyTotal + max(0.0, $estimatedCost)) > $dailyThreshold) {
            return false;
        }

        if ($userId !== null) {
            $userDailyTotal = self::getUserDailyCost($userId);
            if (($userDailyTotal + max(0.0, $estimatedCost)) > $userDailyThreshold) {
                return false;
            }
        }

        return true;
    }

    public static function incrementMonthlyCost(float $cost, ?int $userId = null): void
    {
        $amount = max(0.0, $cost);
        if ($amount <= 0) {
            return;
        }

        $monthKey = "ai_monthly_cost_" . now()->format('Y-m');
        $dayKey = "ai_daily_cost_" . now()->format('Y-m-d');

        Cache::put($monthKey, self::getMonthlyCost() + $amount, now()->addHours(6));
        Cache::put($dayKey, self::getDailyCost() + $amount, now()->addHours(6));

        if ($userId !== null) {
            $userDayKey = "ai_daily_cost_user_{$userId}_" . now()->format('Y-m-d');
            Cache::put($userDayKey, self::getUserDailyCost($userId) + $amount, now()->addHours(6));
        }
    }

    public static function getMonthlyCost(): float
    {
        $monthKey = "ai_monthly_cost_" . now()->format('Y-m');

        return (float) Cache::remember($monthKey, now()->addHours(6), function () {
            return (float) AiUsageLog::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('estimated_cost');
        });
    }

    public static function getDailyCost(): float
    {
        $dayKey = "ai_daily_cost_" . now()->format('Y-m-d');

        return (float) Cache::remember($dayKey, now()->addHours(6), function () {
            return (float) AiUsageLog::whereDate('created_at', now()->toDateString())
                ->sum('estimated_cost');
        });
    }

    public static function getUserDailyCost(int $userId): float
    {
        $userDayKey = "ai_daily_cost_user_{$userId}_" . now()->format('Y-m-d');

        return (float) Cache::remember($userDayKey, now()->addHours(6), function () use ($userId) {
            return (float) AiUsageLog::where('user_id', $userId)
                ->whereDate('created_at', now()->toDateString())
                ->sum('estimated_cost');
        });
    }

    public static function getFormattedTotalCost(): float
    {
        return self::getMonthlyCost();
    }
}