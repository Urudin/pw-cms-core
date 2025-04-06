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
            ['name' => 'footer-opening', 'value' => 'Glósz és Társa Kft. • 1051 Budapest, Arany János u. 15. III. lph III./5. • Telefon: (+36 1) 302 4443 •
            E-mail: <a href="mailto:glosz@glosz.hu?subject=info" class="underline hover:text-gray-100">glosz@glosz.hu</a> •
            <a href="http://www.glosz.hu" class="underline hover:text-gray-100" target="_blank">www.glosz.hu</a>'],
            ['name' => 'footer-closing', 'value' => '#'],
        ];

        foreach ($settings as $setting) {
            UserSetting::query()->firstOrCreate(['name' => $setting['name']], ['value' => $setting['value']]);
        }
    }
}
