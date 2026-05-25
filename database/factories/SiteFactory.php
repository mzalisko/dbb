<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => $this->faker->domainWord() . ' Site',
            'url' => 'https://' . $this->faker->domainName(),
            'wp_version' => $this->faker->randomElement(['6.4', '6.5', '6.6', '6.7']),
            'php_version' => $this->faker->randomElement(['8.1', '8.2', '8.3']),
            'status' => $this->faker->randomElement(['active', 'maintenance', 'offline']),
            'last_checked_at' => $this->faker->optional()->dateTimeThisMonth(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
