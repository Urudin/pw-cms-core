<?php

namespace Database\Seeders;

use App\Models\Block;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BlockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Block::query()->firstOrCreate([
            'name' => 'kapcsolat-form',
        ], [
            'content' => file_get_contents(database_path('seeders/htmls/contact.html')),
        ]);

        Block::query()->firstOrCreate([
            'name' => 'PreFooter',
        ], [
            'content' => file_get_contents(database_path('seeders/htmls/pre-footer.html')),
        ]);

        Block::query()->firstOrCreate([
            'name' => 'PostFooter',
        ], [
            'content' => file_get_contents(database_path('seeders/htmls/post-footer.html')),
        ]);

        Block::query()->firstOrCreate([
            'name' => 'Footer',
        ], [
            'content' => file_get_contents(database_path('seeders/htmls/footer.html')),
        ]);
    }
}
