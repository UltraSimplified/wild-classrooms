<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SpottingController;
use App\Http\Controllers\TreeController;
use App\Http\Controllers\UnitController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
|
| The URLs below deliberately match the old site's, including the {slug}
| segments the old routes carried. Those links are in print, in emails and
| in search results, so the shapes are kept even where the slug is
| decorative and never read.
|
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/about', [PageController::class, 'about'])->name('pages.about');
Route::get('/terms-conditions', [PageController::class, 'terms'])->name('pages.terms');
Route::get('/cookie-policy', [PageController::class, 'cookiePolicy'])->name('pages.cookie-policy');

// Challenges. /spottings/{challenge}/{slug} is the public challenge page.
Route::get('/challenges', [ChallengeController::class, 'index'])->name('challenges.index');
Route::get('/month/{month}', [ChallengeController::class, 'month'])->name('challenges.month');
Route::get('/spottings/{challenge}/{slug?}', [ChallengeController::class, 'show'])
    ->whereNumber('challenge')
    ->name('challenges.show');

// Gallery
Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
Route::post('/gallery/{spotting}/like', [GalleryController::class, 'store'])->name('gallery.like');

// Dig Deeper
Route::get('/dig-deeper', [PostController::class, 'index'])->name('posts.index');
Route::get('/dig-deeper/view/{post}/{slug?}', [PostController::class, 'show'])->name('posts.show');

// Groups and members
Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
Route::get('/groups/woodland/{group}/{slug?}', [GroupController::class, 'woodland'])->name('groups.woodland');
Route::get('/tree/{unit}/{slug?}/{month?}', [UnitController::class, 'tree'])
    ->whereNumber('unit')
    ->name('units.tree');
Route::get('/challenges/{unit}/{month?}', [UnitController::class, 'challenges'])
    ->whereNumber('unit')
    ->name('units.challenges');

// The woodland artwork, served as images.
Route::get('/tree.svg/{unit?}', [TreeController::class, 'tree'])->name('svg.tree');

/*
|--------------------------------------------------------------------------
| Signed in
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function (): void {
    // Onboarding a new group.
    Route::get('/profiles/onboarding', [OnboardingController::class, 'group'])->name('onboarding.group');
    Route::post('/profiles/onboarding', [OnboardingController::class, 'storeGroup'])->name('onboarding.group.store');
    Route::get('/profiles/onboarding-members', [OnboardingController::class, 'members'])->name('onboarding.members');
    Route::post('/profiles/onboarding-members', [OnboardingController::class, 'storeMembers'])->name('onboarding.members.store');

    // Your own group.
    Route::get('/my-woodland', [GroupController::class, 'mine'])->name('groups.mine');
    Route::get('/profile/edit', [GroupProfileController::class, 'edit'])->name('group.edit');
    Route::post('/profile/update', [GroupProfileController::class, 'update'])->name('group.update');

    // Spottings.
    Route::get('/spottings/add/{challenge?}', [SpottingController::class, 'create'])
        ->whereNumber('challenge')
        ->name('spottings.create');
    Route::post('/spottings/add', [SpottingController::class, 'store'])->name('spottings.store');

    Route::get('/spottings/edit/{spotting}', [SpottingController::class, 'edit'])
        ->middleware('can:update,spotting')
        ->name('spottings.edit');
    Route::post('/spottings/edit/{spotting}', [SpottingController::class, 'update'])
        ->middleware('can:update,spotting')
        ->name('spottings.update');
    Route::delete('/spottings/{spotting}', [SpottingController::class, 'destroy'])
        ->middleware('can:delete,spotting')
        ->name('spottings.destroy');

    // The account itself (Breeze).
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'can:manage,'.User::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

require __DIR__.'/auth.php';
