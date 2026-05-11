<?php

use App\Http\Controllers\LegalContentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SimplePayIpnController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoShopController;

Route::get('/', [PageController::class, 'show'])->name('home');
Route::get('/adatkezelesi-tajekoztato-kepzes', [LegalContentController::class, 'dataHandlingCourse'])->name('data-handling-courses');
Route::get('/innovacio-menedzsment-aszf', [LegalContentController::class, 'aszf'])->name('aszf');


//Route::get('/tanfolyami-resztvevo-hallgatoink', fn() => view('references'));

use App\Http\Controllers\CourseApplicationController;

Route::get('/innovaciomenedzsment-tanfolyamok', [CourseApplicationController::class, 'index'])->name('course-applications.index');
Route::get('/jelentkezes-tanfolyamra', [CourseApplicationController::class, 'create'])->name('course-applications.create');
Route::post('/jelentkezes', [CourseApplicationController::class, 'store'])->name('course-applications.store');
Route::get('/online-tartalmak', [VideoShopController::class, 'index'])->name('videos.index');
Route::get('/penztar', [VideoShopController::class, 'checkout'])->name('checkout');
Route::post('/rendeles', [VideoShopController::class, 'placeOrder'])->name('placeOrder');
Route::get('privacy-policy', fn() => 'TODO')->name('privacy-policy');
Route::get('terms', fn() => 'TODO')->name('terms');
Route::get('/sikeres-rendeles', [VideoShopController::class, 'orderSuccessful'])
    ->name('order-successful');
//Route::get('/sikertelen-rendeles', [VideoShopController::class, 'orderFailed'])->name('order-failed');
Route::get('/video/{video}', [VideoShopController::class, 'show'])
    ->middleware('video.auth')
    ->name('video.show');

Route::get('/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/payment/failed', [VideoShopController::class, 'paymentFailed'])
    ->name('payment-failed');

Route::get('/order/success/{orderRef}', [VideoShopController::class, 'orderSuccessfulByOrderRef'])
    ->name('order-successful-by-order-ref');

// SimplePay merchant dashboard is configured to post IPN messages to this custom route.
Route::post('/simplepay/ipn-glosz', [SimplePayIpnController::class, 'handle'])
    ->middleware(\App\Http\Middleware\VerifySimplePaySignature::class)
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
    ->name('simplepay.ipn.glosz');

Route::post('/contact', [\App\Http\Controllers\ContactController::class, 'submit'])->name('contact')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

require __DIR__.'/auth.php';
