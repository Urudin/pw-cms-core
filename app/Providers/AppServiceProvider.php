<?php

namespace App\Providers;

use App\Http\Livewire\MediaPicker;
use App\Http\Responses\SimplePayBackResponse;
use App\Models\ActualCourse;
use App\Models\Course;
use App\Observers\ActualCourseObserver;
use App\Observers\CourseObserver;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Netipar\SimplePay\Contracts\BackUrlResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ActualCourse::observe(ActualCourseObserver::class);
        Course::observe(CourseObserver::class);

        Livewire::component('media-picker', MediaPicker::class);

        $this->app->singleton(BackUrlResponse::class, SimplePayBackResponse::class);

        \URL::forceScheme('https');

        FilamentAsset::register([
            Css::make('custom', __DIR__.'/../../resources/css/custom.css'),
            Js::make('custom', __DIR__.'/../../resources/js/custom.js'),
        ]);
    }
}
