<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Doctor>
 */
class DoctorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Dr. ' . fake()->name(),
            'specialty' => fake()->randomElement([
                'General Physician',
                'Cardiologist',
                'Gynecologist',
                'Pediatrician',
                'Urologist',
                'Dermatologist',
            ]),
            'phone' => fake()->numerify('03#########'),
            'commission_percent' => fake()->randomElement([0, 10, 15, 20]),
            'is_active' => true,
        ];
    }
}
