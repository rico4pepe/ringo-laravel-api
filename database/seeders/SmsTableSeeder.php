<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class SmsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $faker = Faker::create();

        for ($i = 0; $i < 2000; $i++) { // Generate 2000 records
            DB::table('sms')->insert([
                'firstname' => $faker->firstName,
                'lastname' => $faker->lastName,
                'phone_number' => $faker->phoneNumber,
                'message' => $faker->text,
                'date' => $faker->date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
