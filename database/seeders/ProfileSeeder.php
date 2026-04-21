<?php

namespace Database\Seeders;

use App\Models\Profile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $query      =   Profile::query();

        // get the file to seed data from
        $seed_file  =   database_path('seeders/seed_profiles.json');

        if(file_exists($seed_file)){
            $profiles   =   json_decode(file_get_contents($seed_file), true);

            foreach($profiles['profiles'] as $profile){
                $query->updateOrCreate(['name'  =>  $profile['name']], [
                    'gender'                =>  $profile['gender'],
                    'gender_probability'    =>  $profile['gender_probability'],
                    'age'                   =>  $profile['age'],
                    'age_group'             =>  $profile['age_group'],
                    'country_id'            =>  $profile['country_id'],
                    'country_name'          =>  $profile['country_name'],
                    'country_probability'   =>  $profile['country_probability'],
                    'created_at'            =>  now()
                ]);
            }

            echo("see_profiles.json has been seeded successfully.");
        }
        else{
            dd("seed_profiles.json does not exist in ".database_path()."\seeders");
        }
    }
}
