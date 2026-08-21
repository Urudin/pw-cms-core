<?php

namespace Tests\Feature;

use App\Models\LegalContent;
use App\Models\Page;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegalContentRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_legal_content_resolves_by_url(): void
    {
        $this->setUpLayoutDependencies();

        LegalContent::query()->create([
            'name' => 'ASZF',
            'url' => 'innovacio-menedzsment-aszf',
            'content' => '<main>Legal content body</main>',
        ]);

        $response = $this->get(route('legal-content', ['url' => 'innovacio-menedzsment-aszf']));

        $response
            ->assertOk()
            ->assertSee('Legal content body', false);
    }

    public function test_page_slug_still_resolves_when_no_legal_content_matches(): void
    {
        $this->setUpLayoutDependencies();

        Page::query()->create([
            'name' => 'Regular page',
            'title' => 'Regular page',
            'slug' => 'regular-page',
            'meta_title' => 'Regular Page Meta Title',
        ]);

        $response = $this->get(route('pages.show', ['slug' => 'regular-page']));

        $response
            ->assertOk()
            ->assertSee('Regular Page Meta Title');
    }

    private function setUpLayoutDependencies(): void
    {
        UserSetting::query()->create(['name' => 'startingPage', 'value' => 'home']);
        UserSetting::query()->create(['name' => 'header-background', 'value' => 'missing.jpg']);
        UserSetting::query()->create(['name' => 'header-sub-title-row-1', 'value' => 'Header row 1']);
        UserSetting::query()->create(['name' => 'header-sub-title-row-2', 'value' => 'Header row 2']);

        DB::table('menus')->insert([
            'id' => 1,
            'name' => 'Main menu',
            'slug' => 'main-menu',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
