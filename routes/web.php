<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoShopController;

Route::get('/', [PageController::class, 'show'])->name('home');


//Route::get('/tanfolyami-resztvevo-hallgatoink', fn() => view('references'));

use App\Http\Controllers\CourseApplicationController;

Route::get('/innovaciomenedzsment-tanfolyamok', [CourseApplicationController::class, 'index'])->name('course-applications.index');
Route::get('/jelentkezes-tanfolyamra', [CourseApplicationController::class, 'create'])->name('course-applications.create');
Route::post('/jelentkezes', [CourseApplicationController::class, 'store'])->name('course-applications.store');
Route::get('/videos', [VideoShopController::class, 'index'])->name('videos.index');

Route::get('/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::post('/contact', [\App\Http\Controllers\ContactController::class, 'submit'])->name('contact')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

require __DIR__.'/auth.php';
