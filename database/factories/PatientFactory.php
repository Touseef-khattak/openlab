<?php

namespace Database\Factories;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        $gender = fake()->randomElement([Gender::Male, Gender::Female]);

        return [
            'name' => $gender === Gender::Male ? fake()->name('male') : fake()->name('female'),
            'cnic' => fake()->numerify('#####-#######-#'),
            'phone' => fake()->numerify('03#########'),
            'gender' => $gender,
            'dob' => fake()->dateTimeBetween('-80 years', '-1 year')->format('Y-m-d'),
        ];
    }
}
