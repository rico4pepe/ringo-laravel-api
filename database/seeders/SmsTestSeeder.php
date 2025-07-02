<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB; 
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Carbon\Carbon;



class SmsTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $now = Carbon::now();
        $records = [
            // MTN - Delivered
            ['phone_number' => '08031234567', 'status' => '1', 'status_code' => '1', 'err_code' => '000'],
            ['phone_number' => '07061234567', 'status' => '1', 'status_code' => '1', 'err_code' => '000'],

            // Airtel - Undelivered
            ['phone_number' => '08021234567', 'status' => '2', 'status_code' => '2', 'err_code' => '206'],
            ['phone_number' => '09011234567', 'status' => '2', 'status_code' => '2', 'err_code' => '085'],

            // Glo - Pending
            ['phone_number' => '08051234567', 'status' => '0', 'status_code' => '0', 'err_code' => null],
            ['phone_number' => '07051234567', 'status' => null, 'status_code' => null, 'err_code' => null],

            // 9mobile - Undelivered with blocked sender
            ['phone_number' => '08091234567', 'status' => '2', 'status_code' => '2', 'err_code' => '065'],

            // Unknown prefix - should default to MTN
            ['phone_number' => '07091234567', 'status' => '2', 'status_code' => '2', 'err_code' => '130'],

            // Inbox full
            ['phone_number' => '08101234567', 'status' => '2', 'status_code' => '2', 'err_code' => '254'],

            // Barred
            ['phone_number' => '08081234567', 'status' => '2', 'status_code' => '2', 'err_code' => '20d'],

            // Invalid numbers
            ['phone_number' => '08151234567', 'status' => '2', 'status_code' => '2', 'err_code' => '004'],
            ['phone_number' => '09061234567', 'status' => '2', 'status_code' => '2', 'err_code' => '215'],

            // MTN again - Delivered
            ['phone_number' => '09031234567', 'status' => '1', 'status_code' => '1', 'err_code' => '000'],

            // Additional unknowns
            ['phone_number' => '07091234567', 'status' => '0', 'status_code' => '0', 'err_code' => null],
            ['phone_number' => '07041234567', 'status' => '2', 'status_code' => '2', 'err_code' => '085'],
        ];

        foreach ($records as $record) {
            DB::table('sms')->insert([
                'firstname' => $faker->firstName,
                'lastname' => $faker->lastName,
                'phone_number' => $record['phone_number'],
                'message' => $faker->lastName . $record['phone_number'],
                'date' => $now->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
                'status' => $record['status'],
                'status_code' => $record['status_code'],
                'err_code' => $record['err_code'],
                'sender' => 'TestSender',
            ]);
        }

        echo "✔️ Seeded test SMS messages.\n";
    }
}
