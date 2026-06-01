<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Holiday::insert([
            [
                'date' => '2026-01-01',
                'name' => 'New Year'
            ],
            [
                'date' => '2026-12-25',
                'name' => 'Christmas'
            ]
        ]);
    }
}
