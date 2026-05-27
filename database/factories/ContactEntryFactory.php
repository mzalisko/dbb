<?php

namespace Database\Factories;

use App\Models\ContactEntry;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactEntry>
 */
class ContactEntryFactory extends Factory
{
    protected $model = ContactEntry::class;

    public function definition(): array
    {
        return [
            'site_id'    => Site::factory(),
            'type'       => 'phone',
            'kind'       => null,
            'value'      => $this->faker->numerify('+48 ## ### ## ##'),
            'label'      => $this->faker->word(),
            'role'       => 'primary',
            'geo_mode'   => 'all',
            'countries'  => null,
            'visible'    => true,
            'order'      => 1,
            'parent_id'  => null,
            'currency'   => null,
            'price'      => null,
            'old_price'  => null,
            'price_unit' => null,
            'sku'        => null,
        ];
    }

    public function phone(): static
    {
        return $this->state(['type' => 'phone', 'kind' => null]);
    }

    public function messenger(string $kind = 'telegram'): static
    {
        return $this->state(['type' => 'messenger', 'kind' => $kind, 'value' => '@' . $this->faker->userName()]);
    }

    public function price(): static
    {
        return $this->state([
            'type'       => 'price',
            'value'      => (string) $this->faker->randomFloat(2, 10, 999),
            'currency'   => 'EUR',
            'sku'        => $this->faker->bothify('SKU-####'),
            'price'      => $this->faker->randomFloat(2, 10, 999),
            'price_unit' => '/міс',
        ]);
    }

    public function backup(ContactEntry $parent): static
    {
        return $this->state([
            'role'      => 'backup',
            'parent_id' => $parent->id,
            'site_id'   => $parent->site_id,
            'type'      => $parent->type,
        ]);
    }
}
