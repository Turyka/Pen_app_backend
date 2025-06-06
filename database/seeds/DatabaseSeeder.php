<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
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
            'name' => 'Gunár',
            'teljes_nev' => 'Mike Barnabás',
            'password' => Hash::make('asd'),
            'szak' => 'GTK',
            'titulus' => 'Referens'
        ]);

        User::create([
            'name' => 'Doki',
            'teljes_nev' => 'Kocsis Dominik',
            'password' => Hash::make('asd'),
            'szak' => 'MIK (gigachad)',
            'titulus' => 'Referens'
        ]);

        User::create([
            'name' => 'Luca',
            'teljes_nev' => 'Kóti Luca',
            'password' => Hash::make('asd'),
            'szak' => 'GTK',
            'titulus' => 'Referens'
        ]);

        User::create([
            'name' => 'Kornél',
            'teljes_nev' => 'Büki Kornél',
            'password' => Hash::make('asd'),
            'szak' => 'MIK',
            'titulus' => 'Referens'
        ]);
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
