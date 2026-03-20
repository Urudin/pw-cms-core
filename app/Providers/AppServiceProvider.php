<?php

namespace App\Providers;

use App\Http\Responses\SimplePayBackResponse;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Http\Livewire\MediaPicker;
use Netipar\SimplePay\Contracts\BackUrlResponse;
use App\Http\Middleware\VerifySimplePaySignature as AppVerifySimplePaySignature;
use Netipar\SimplePay\Http\Middleware\VerifySimplePaySignature as PackageVerifySimplePaySignature;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::component('media-picker', MediaPicker::class);

        $this->app->singleton(BackUrlResponse::class, SimplePayBackResponse::class);

        \URL::forceScheme('https');

        FilamentAsset::register([
            Css::make('custom', __DIR__ . '/../../resources/css/custom.css'),
            Js::make('custom', __DIR__ . '/../../resources/js/custom.js'),
        ]);
    }
}
