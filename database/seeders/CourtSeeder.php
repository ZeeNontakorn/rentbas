<?php

namespace Database\Seeders;

use App\Models\Court;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 5) as $i) {
            Court::updateOrCreate(
                ['name' => "Court {$i}"],
                ['court_status' => 'open']
            );
        }
    }
}
