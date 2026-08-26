<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\OfferController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('companies/board', [CompanyController::class, 'board'])->name('companies.board');
    Route::patch('companies/{company}/status', [CompanyController::class, 'updateStatus'])->name('companies.status');
    Route::resource('companies', CompanyController::class);

    Route::get('contacts/board', [ContactController::class, 'board'])->name('contacts.board');
    Route::patch('contacts/{contact}/status', [ContactController::class, 'updateStatus'])->name('contacts.status');
    Route::resource('contacts', ContactController::class);

    Route::get('deals/board', [DealController::class, 'board'])->name('deals.board');
    Route::patch('deals/{deal}/status', [DealController::class, 'updateStatus'])->name('deals.status');
    Route::resource('deals', DealController::class);

    Route::get('offers/board', [OfferController::class, 'board'])->name('offers.board');
    Route::patch('offers/{offer}/status', [OfferController::class, 'updateStatus'])->name('offers.status');
    Route::get('offers/{offer}/pdf', [OfferController::class, 'pdf'])->name('offers.pdf');
    Route::resource('offers', OfferController::class);
});

require __DIR__.'/settings.php';
