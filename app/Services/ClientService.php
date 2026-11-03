<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\CreateClientData;
use App\DTO\UpdateClientData;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function create(CreateClientData $data, User $actor): Client
    {
        $this->ensureActorHasCabinet($actor);

        return DB::transaction(function () use ($data, $actor): Client {
            $client = Client::query()->create([
                ...$data->toModelAttributes(),
                'cabinet_id' => $actor->cabinet_id,
                'created_by' => $actor->id,
            ]);

            $this->auditLogService->record(
                action: 'client.created',
                user: $actor,
                cabinet: $actor->cabinet,
                auditable: $client,
            );

            return $client;
        });
    }

    public function update(Client $client, UpdateClientData $data, User $actor): Client
    {
        return DB::transaction(function () use ($client, $data, $actor): Client {
            $client->fill([
                ...$data->toModelAttributes(),
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogService->record(
                action: 'client.updated',
                user: $actor,
                cabinet: $client->cabinet,
                auditable: $client,
            );

            return $client->refresh();
        });
    }

    public function delete(Client $client, User $actor): void
    {
        DB::transaction(function () use ($client, $actor): void {
            $client->delete();

            $this->auditLogService->record(
                action: 'client.deleted',
                user: $actor,
                cabinet: $client->cabinet,
                auditable: $client,
            );
        });
    }

    private function ensureActorHasCabinet(User $actor): void
    {
        if ($actor->cabinet_id !== null) {
            return;
        }

        throw ValidationException::withMessages([
            'cabinet' => __('Un client doit etre cree depuis un cabinet actif.'),
        ]);
    }
}
