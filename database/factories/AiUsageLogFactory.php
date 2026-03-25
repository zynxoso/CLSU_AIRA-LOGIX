<?php

namespace Database\Factories;

use App\Models\AiUsageLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiUsageLogFactory extends Factory
{
    protected $model = AiUsageLog::class;

    public function definition(): array
    {
        return [
            'service' => $this->faker->randomElement(['vision_extraction', 'text_parsing']),
            'model' => $this->faker->word(),
            'user_id' => User::factory(),
            'extraction_method' => $this->faker->word(),
            'source_file_type' => $this->faker->fileExtension(),
            'prompt_tokens' => $this->faker->numberBetween(10, 100),
            'completion_tokens' => $this->faker->numberBetween(10, 100),
            'total_tokens' => $this->faker->numberBetween(20, 200),
            'estimated_cost' => $this->faker->randomFloat(6, 0, 1),
            'metadata' => ['foo' => 'bar'],
        ];
    }
}
