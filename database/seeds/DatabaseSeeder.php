<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Eszkozok;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use App\Models\Naptar;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Admin',
            'teljes_nev' => 'Admin',
            'password' => Hash::make('Sajtsajt123'),
            'szak' => 'Admin',
            'titulus' => 'Admin'
        ]);

        User::create([
            'name' => 'Barni',
            'teljes_nev' => 'Mike Barnabás',
            'password' => Hash::make('asd'),
            'szak' => 'GTK',
            'titulus' => 'Elnök'
        ]);

        User::create([
            'name' => 'Doki',
            'teljes_nev' => 'Kocsis Dominik',
            'password' => Hash::make('asd'),
            'szak' => 'MIK',
            'titulus' => 'Képviselő'
        ]);

        User::create([
            'name' => 'Luca',
            'teljes_nev' => 'Kóti Luca',
            'password' => Hash::make('asd'),
            'szak' => 'GTK',
            'titulus' => 'Elnökhelyettes'
        ]);

        User::create([
            'name' => 'Kornél',
            'teljes_nev' => 'Büki Kornél',
            'password' => Hash::make('asd'),
            'szak' => 'MIK',
            'titulus' => 'Referens'
        ]);
        /*
        $brands = ['Samsung', 'iPhone', 'Redmi', 'Huawei', 'OnePlus', 'Nokia'];
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 30; $i++) {
            $brand = $faker->randomElement($brands);
            $model = strtoupper(Str::random(6)); // e.g. 'AB12CD'

            Eszkozok::create([
                'device_id'   => Str::uuid(),
                'device'      => "$brand $model",     // Full device string
                'os'          => $brand,              // Only the brand name
                'app_version' => '1.0.0 (' . $faker->numberBetween(1, 5) . ')',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
        /*
        $faker = Faker::create('hu_HU'); // Magyar lokalizáció

        $eventTypes = ['Előadás', 'Vizsga', 'Workshop', 'Gyakorlat', 'Rendezvény'];
        $statuses = ['Aktív', 'Törölve', 'Függőben', 'Lezárt'];

        for ($i = 0; $i < 40; $i++) {
            $date = $faker->dateTimeBetween('now', '+2 months');
            $start = $faker->time('H:i');
            $end = date('H:i', strtotime($start . ' + ' . rand(1, 4) . ' hours'));

            Naptar::create([
                'title' => $faker->sentence(3),
                'date' => $date->format('Y-m-d'),
                'start_time' => $start,
                'end_time' => $end,
                'event_type' => $faker->randomElement($eventTypes),
                'status' => $faker->randomElement($statuses),
                'created' => $faker->name,
                'edited' => $faker->name,
                'description' => $faker->optional()->paragraph,
            ]);
        }
            */
    }
}
