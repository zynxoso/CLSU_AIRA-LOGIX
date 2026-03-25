<?php

namespace Database\Factories;

use App\Models\IctServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class IctServiceRequestFactory extends Factory
{
    protected $model = IctServiceRequest::class;

    public function definition(): array
    {
        return [
            'control_no' => $this->faker->unique()->numerify('CTRL-####'),
            'timestamp_str' => now()->toDateTimeString(),
            'client_feedback_no' => $this->faker->optional()->numerify('FB-####'),
            'name' => $this->faker->name(),
            'name_index' => $this->faker->word(),
            'position' => $this->faker->jobTitle(),
            'office_unit' => $this->faker->company(),
            'contact_no' => $this->faker->phoneNumber(),
            'date_of_request' => $this->faker->date(),
            'requested_completion_date' => $this->faker->optional()->date(),
            'request_type' => $this->faker->word(),
            'location_venue' => $this->faker->address(),
            'request_description' => $this->faker->sentence(),
            'received_by' => $this->faker->name(),
            'receive_date_time' => $this->faker->optional()->dateTime(),
            'action_taken' => $this->faker->optional()->sentence(),
            'recommendation_conclusion' => $this->faker->optional()->sentence(),
            'status' => 'Open',
            'date_time_started' => $this->faker->optional()->dateTime(),
            'date_time_completed' => $this->faker->optional()->dateTime(),
            'conducted_by' => $this->faker->optional()->name(),
            'noted_by' => $this->faker->optional()->name(),
        ];
    }
}
