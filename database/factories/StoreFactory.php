<?php

namespace Database\Factories;

use App\Enums\StoreStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StoreFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'user_id' => User::factory()->state(['role' => UserRole::Vendor->value]),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->sentence(),
            'status' => StoreStatus::Approved->value,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => StoreStatus::Pending->value]);
    }
}
