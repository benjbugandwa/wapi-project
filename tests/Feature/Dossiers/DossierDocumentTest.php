<?php

declare(strict_types=1);

namespace Tests\Feature\Dossiers;

use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\Cabinet;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('local');
});

test('authorized user can upload a dossier document', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $dossier = Dossier::factory()->for($cabinet)->create();

    $this->actingAs($user);

    Volt::test('dossiers.show', ['dossier' => $dossier])
        ->set('document_file', UploadedFile::fake()->create('conclusions.pdf', 96, 'application/pdf'))
        ->set('categorie', 'conclusions')
        ->call('uploadDocument')
        ->assertHasNoErrors();

    $document = Document::query()->where('dossier_id', $dossier->id)->firstOrFail();

    Storage::disk('local')->assertExists($document->chemin_acces);

    expect($document->cabinet_id)->toBe($cabinet->id)
        ->and($document->client_id)->toBeNull()
        ->and($document->uploaded_by)->toBe($user->id)
        ->and($document->taille_octets)->toBe(96 * 1024)
        ->and(AuditLog::query()->where('action', 'document.uploaded')->where('auditable_id', $document->id)->exists())->toBeTrue();
});

test('authorized user can download own cabinet dossier document', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $dossier = Dossier::factory()->for($cabinet)->create();
    $document = Document::factory()->forDossier()->create([
        'dossier_id' => $dossier->id,
        'cabinet_id' => $cabinet->id,
        'uploaded_by' => $user->id,
        'chemin_acces' => 'dossier-documents/test.pdf',
    ]);

    Storage::disk('local')->put($document->chemin_acces, 'contenu');

    $this->actingAs($user)
        ->get(route('documents.download', $document))
        ->assertOk();

    expect(AuditLog::query()->where('action', 'document.downloaded')->where('auditable_id', $document->id)->exists())->toBeTrue();
});

test('user cannot download another cabinet dossier document', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $document = Document::factory()->forDossier()->create();

    Storage::disk('local')->put($document->chemin_acces, 'contenu');

    $this->actingAs($user)
        ->get(route('documents.download', $document))
        ->assertForbidden();
});

test('admin can soft delete a dossier document', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Admin->value);
    $dossier = Dossier::factory()->for($cabinet)->create();
    $document = Document::factory()->forDossier()->create([
        'dossier_id' => $dossier->id,
        'cabinet_id' => $cabinet->id,
        'uploaded_by' => $user->id,
    ]);

    $this->actingAs($user);

    Volt::test('dossiers.show', ['dossier' => $dossier])
        ->call('deleteDocument', $document->id)
        ->assertHasNoErrors();

    $this->assertSoftDeleted($document);
    expect(AuditLog::query()->where('action', 'document.deleted')->where('auditable_id', $document->id)->exists())->toBeTrue();
});
