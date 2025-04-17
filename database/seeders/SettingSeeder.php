<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\UserSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['name' => 'header-background', 'value' => '#'],
            ['name' => 'header-sub-title-row-1', 'value' => 'INNOVÁCIÓMENEDZSMENT'],
            ['name' => 'header-sub-title-row-2', 'value' => 'SZOLGÁLTATÁSOK'],
            ['name' => 'startingPage', 'value' => '#'],
            ['name' => 'contact-form-block-id', 'value' => 1],
        ];

        foreach ($settings as $setting) {
            UserSetting::query()->firstOrCreate(['name' => $setting['name']], ['value' => $setting['value']]);
        }
    }
}
