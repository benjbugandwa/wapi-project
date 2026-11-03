<?php

declare(strict_types=1);

namespace Tests\Feature\Clients;

use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\Cabinet;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('local');
});

test('authorized user can upload a client document', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $client = Client::factory()->for($cabinet)->create();

    $this->actingAs($user);

    Volt::test('clients.show', ['client' => $client])
        ->set('document_file', UploadedFile::fake()->create('passeport.pdf', 128, 'application/pdf'))
        ->set('categorie', 'passeport')
        ->call('uploadDocument')
        ->assertHasNoErrors();

    $document = Document::query()->where('client_id', $client->id)->firstOrFail();

    Storage::disk('local')->assertExists($document->chemin_acces);

    expect($document->cabinet_id)->toBe($client->cabinet_id)
        ->and($document->uploaded_by)->toBe($user->id)
        ->and($document->taille_octets)->toBe(128 * 1024)
        ->and($document->type_mime)->toBe('application/pdf')
        ->and(AuditLog::query()->where('action', 'document.uploaded')->where('auditable_id', $document->id)->exists())->toBeTrue();
});

test('authorized user can download own cabinet document', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $client = Client::factory()->for($cabinet)->create();
    $document = Document::factory()->for($client)->create([
        'cabinet_id' => $cabinet->id,
        'uploaded_by' => $user->id,
        'chemin_acces' => 'client-documents/test.pdf',
    ]);

    Storage::disk('local')->put($document->chemin_acces, 'contenu');

    $this->actingAs($user)
        ->get(route('documents.download', $document))
        ->assertOk();

    expect(AuditLog::query()->where('action', 'document.downloaded')->where('auditable_id', $document->id)->exists())->toBeTrue();
});

test('user cannot download another cabinet document', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $document = Document::factory()->create();

    Storage::disk('local')->put($document->chemin_acces, 'contenu');

    $this->actingAs($user)
        ->get(route('documents.download', $document))
        ->assertForbidden();
});

test('admin can soft delete a client document', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Admin->value);
    $client = Client::factory()->for($cabinet)->create();
    $document = Document::factory()->for($client)->create([
        'cabinet_id' => $cabinet->id,
        'uploaded_by' => $user->id,
    ]);

    $this->actingAs($user);

    Volt::test('clients.show', ['client' => $client])
        ->call('deleteDocument', $document->id)
        ->assertHasNoErrors();

    $this->assertSoftDeleted($document);
    expect(AuditLog::query()->where('action', 'document.deleted')->where('auditable_id', $document->id)->exists())->toBeTrue();
});
