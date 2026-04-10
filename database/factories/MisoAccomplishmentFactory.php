<?php

namespace Database\Factories;

use App\Models\MisoAccomplishment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MisoAccomplishment>
 */
class MisoAccomplishmentFactory extends Factory
{
    protected $model = MisoAccomplishment::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(6);
        $lead = $this->faker->name();
        $period = $this->faker->randomElement(['Q1 2026', 'Q2 2026', 'Q3 2026', 'Q4 2026']);

        return [
            'category' => $this->faker->randomElement([
                MisoAccomplishment::CATEGORY_DATA_MANAGEMENT,
                MisoAccomplishment::CATEGORY_NETWORK,
                MisoAccomplishment::CATEGORY_SYSTEMS_DEVELOPMENT,
            ]),
            'source_file' => 'docs/sample.csv',
            'source_row' => $this->faker->numberBetween(2, 300),
            'source_hash' => hash('sha256', strtolower($title.'|'.$lead.'|'.$period.'|'.$this->faker->uuid())),
            'record_no' => (string) $this->faker->numberBetween(1, 200),
            'project_title' => $title,
            'project_lead' => $lead,
            'project_members' => $this->faker->name()."\n".$this->faker->name(),
            'budget_cost' => (string) $this->faker->numberBetween(5000, 500000),
            'implementing_unit' => $this->faker->randomElement(['MISO', 'MISO x OAD', 'MISO x HRMO']),
            'target_activities' => $this->faker->sentence(10),
            'intended_duration' => $this->faker->randomElement(['3 months', '6 months', '1 year']),
            'start_date' => $this->faker->monthName.' '.$this->faker->year(),
            'target_end_date' => $this->faker->monthName.' '.$this->faker->year(),
            'reporting_period' => $period,
            'completion_percentage' => $this->faker->randomElement(['10%', '25%', '50%', '75%', '100%']),
            'overall_status' => $this->faker->randomElement(['On Track', 'Delayed', 'Completed', 'Cancelled', 'On-hold']),
            'remarks' => $this->faker->sentence(12),
        ];
    }
}
