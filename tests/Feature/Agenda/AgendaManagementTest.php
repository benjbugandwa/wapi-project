<?php

declare(strict_types=1);

namespace Tests\Feature\Agenda;

use App\Enums\EvenementStatusEnum;
use App\Enums\EvenementTypeEnum;
use App\Enums\RoleEnum;
use App\Jobs\CancelExternalCalendarEventJob;
use App\Jobs\SyncEvenementToExternalCalendarJob;
use App\Models\AuditLog;
use App\Models\Cabinet;
use App\Models\Dossier;
use App\Models\Evenement;
use App\Models\User;
use App\Notifications\AgendaEventNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Notification::fake();
    Queue::fake();
});

test('authorized user can create an event linked to a same cabinet dossier and participants', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $participant = User::factory()->for($cabinet)->create();
    $dossier = Dossier::factory()->for($cabinet)->create();

    $this->actingAs($user);

    Volt::test('agenda.index')
        ->set('titre', 'Audience civile')
        ->set('type_evenement', EvenementTypeEnum::Audience->value)
        ->set('dossier_id', $dossier->id)
        ->set('date_debut', '2026-07-20T09:00')
        ->set('date_fin', '2026-07-20T10:00')
        ->set('participant_ids', [$participant->id])
        ->call('save')
        ->assertHasNoErrors();

    $evenement = Evenement::query()->where('titre', 'Audience civile')->firstOrFail();

    expect($evenement->cabinet_id)->toBe($cabinet->id)
        ->and($evenement->dossier_id)->toBe($dossier->id)
        ->and($evenement->participants()->whereKey($user->id)->exists())->toBeTrue()
        ->and($evenement->participants()->whereKey($participant->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'agenda.created')->where('auditable_id', $evenement->id)->exists())->toBeTrue();

    Notification::assertSentTo([$user, $participant], AgendaEventNotification::class);
    Queue::assertPushed(SyncEvenementToExternalCalendarJob::class);
});

test('event listing is isolated by cabinet and participant visibility', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $visible = Evenement::factory()->create([
        'cabinet_id' => $cabinet->id,
        'created_by' => $user->id,
        'titre' => 'Visible agenda',
    ]);

    Evenement::factory()->create(['titre' => 'Invisible agenda']);

    $this->actingAs($user);

    $this->get(route('evenements.index'))
        ->assertOk()
        ->assertSee('Visible agenda')
        ->assertDontSee('Invisible agenda');

    Volt::test('agenda.index')
        ->set('search', 'Visible')
        ->assertSee($visible->titre);
});

test('event cannot be linked to another cabinet dossier', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $otherDossier = Dossier::factory()->create();

    $this->actingAs($user);

    Volt::test('agenda.index')
        ->set('titre', 'Audience invalide')
        ->set('type_evenement', EvenementTypeEnum::Audience->value)
        ->set('dossier_id', $otherDossier->id)
        ->set('date_debut', '2026-07-20T09:00')
        ->set('date_fin', '2026-07-20T10:00')
        ->call('save')
        ->assertHasErrors('dossier_id');
});

test('authorized user can cancel an event through soft delete', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $user->assignRole(RoleEnum::Avocat->value);
    $evenement = Evenement::factory()->create([
        'cabinet_id' => $cabinet->id,
        'created_by' => $user->id,
    ]);
    $evenement->participants()->attach($user->id, [
        'id' => (string) str()->uuid(),
        'cabinet_id' => $cabinet->id,
    ]);

    $this->actingAs($user);

    Volt::test('agenda.index')
        ->call('cancel', $evenement->id)
        ->assertHasNoErrors();

    $evenement = Evenement::withTrashed()->findOrFail($evenement->id);

    expect($evenement->trashed())->toBeTrue()
        ->and($evenement->statut_evenement)->toBe(EvenementStatusEnum::Annule)
        ->and($evenement->cancelled_by)->toBe($user->id)
        ->and(AuditLog::query()->where('action', 'agenda.cancelled')->where('auditable_id', $evenement->id)->exists())->toBeTrue();

    Queue::assertPushed(CancelExternalCalendarEventJob::class);
});
