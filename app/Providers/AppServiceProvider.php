<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Http\Livewire\MediaPicker;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::component('media-picker', MediaPicker::class);

        FilamentAsset::register([
            Css::make('custom', __DIR__ . '/../../resources/css/custom.css'),
            Js::make('custom', __DIR__ . '/../../resources/js/custom.js'),
        ]);
    }
}
