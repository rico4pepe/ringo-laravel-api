<?php



namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use App\Models\Sms;


ini_set('memory_limit', '2048M');
set_time_limit(0);


class SmsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

     public function run()
     {
         $faker = Faker::create();
         $chunkSize = 1000; // Number of records to insert in one batch
         $smsData = [];

        // Start a transaction for bulk insert
         DB::beginTransaction();

         try {

            for ($i = 0; $i < 2000000; $i++) {
                $smsData[] = [
                    'firstname' => $faker->firstName,
                    'lastname' => $faker->lastName,
                    'phone_number' => $faker->phoneNumber,
                    'message' => $faker->text,
                    'date' => $faker->date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // When the chunk is full, insert it and reset the array
                if (count($smsData) >= $chunkSize) {
                    DB::table('sms')->insert($smsData);
                    $smsData = []; // Reset the array for the next batch
                }
            }

            // Insert any remaining records
            if (!empty($smsData)) {
                DB::table('sms')->insert($smsData);
            }

            DB::commit();
         }catch(\Exception $e){
            DB::rollBack();
            throw $e;
         }


     }

}
