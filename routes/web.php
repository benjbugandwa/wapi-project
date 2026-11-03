<?php

use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\FacturePdfController;
use App\Http\Controllers\PaiementReceiptController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Volt::route('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'tenant.active'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth', 'tenant.active'])
    ->name('profile');

Route::middleware(['auth', 'verified', 'tenant.active'])->group(function () {
    Volt::route('clients', 'clients.index')
        ->name('clients.index');

    Volt::route('clients/create', 'clients.create')
        ->name('clients.create');

    Volt::route('clients/{client}', 'clients.show')
        ->name('clients.show');

    Volt::route('clients/{client}/edit', 'clients.edit')
        ->name('clients.edit');

    Volt::route('dossiers', 'dossiers.index')
        ->name('dossiers.index');

    Volt::route('dossiers/create', 'dossiers.create')
        ->name('dossiers.create');

    Volt::route('dossiers/{dossier}', 'dossiers.show')
        ->name('dossiers.show');

    Volt::route('dossiers/{dossier}/edit', 'dossiers.edit')
        ->name('dossiers.edit');

    Volt::route('evenements', 'agenda.index')
        ->name('evenements.index');

    Volt::route('evenements/{evenement}', 'agenda.show')
        ->name('evenements.show');

    Volt::route('factures', 'factures.index')
        ->name('factures.index');

    Volt::route('factures/{facture}', 'factures.show')
        ->name('factures.show');

    Route::get('factures/{facture}/pdf', FacturePdfController::class)
        ->name('factures.pdf');

    Volt::route('paiements', 'paiements.index')
        ->name('paiements.index');

    Route::get('paiements/{paiement}/receipt', PaiementReceiptController::class)
        ->name('paiements.receipt');

    Volt::route('comptabilite/rapports', 'comptabilite.rapports')
        ->name('comptabilite.rapports');

    Route::get('documents/{document}/download', DocumentDownloadController::class)
        ->name('documents.download');

    Volt::route('admin/cabinets', 'admin.cabinets.index')
        ->name('admin.cabinets.index');

    Volt::route('admin/cabinets/create', 'admin.cabinets.create')
        ->name('admin.cabinets.create');

    Volt::route('admin/users', 'admin.users.index')
        ->name('admin.users.index');
});

require __DIR__.'/auth.php';
