<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        foreach (RoleEnum::cases() as $role) {
            Role::query()->firstOrCreate([
                'name' => $role->value,
                'guard_name' => 'web',
            ]);
        }

        Role::findByName(RoleEnum::SystemAdmin->value)->syncPermissions(PermissionEnum::values());

        Role::findByName(RoleEnum::Admin->value)->syncPermissions([
            PermissionEnum::ViewUsers->value,
            PermissionEnum::InviteUsers->value,
            PermissionEnum::ApproveUsers->value,
            PermissionEnum::ActivateUsers->value,
            PermissionEnum::DeactivateUsers->value,
            PermissionEnum::AssignRoles->value,
            PermissionEnum::ManageRoles->value,
            PermissionEnum::ViewClients->value,
            PermissionEnum::CreateClients->value,
            PermissionEnum::UpdateClients->value,
            PermissionEnum::DeleteClients->value,
            PermissionEnum::ViewDossiers->value,
            PermissionEnum::CreateDossiers->value,
            PermissionEnum::UpdateDossiers->value,
            PermissionEnum::CloseDossiers->value,
            PermissionEnum::ArchiveDossiers->value,
            PermissionEnum::AssignDossiers->value,
            PermissionEnum::ViewAgenda->value,
            PermissionEnum::CreateAgenda->value,
            PermissionEnum::UpdateAgenda->value,
            PermissionEnum::CancelAgenda->value,
            PermissionEnum::ShareAgenda->value,
            PermissionEnum::ManageAgendaNotifications->value,
            PermissionEnum::SyncAgenda->value,
            PermissionEnum::ViewDocuments->value,
            PermissionEnum::UploadDocuments->value,
            PermissionEnum::DownloadDocuments->value,
            PermissionEnum::DeleteDocuments->value,
            PermissionEnum::ViewFactures->value,
            PermissionEnum::CreateFactures->value,
            PermissionEnum::UpdateFactures->value,
            PermissionEnum::SendFactures->value,
            PermissionEnum::CancelFactures->value,
            PermissionEnum::DownloadFacturePdf->value,
            PermissionEnum::ViewPaiements->value,
            PermissionEnum::CreatePaiements->value,
            PermissionEnum::DownloadPaiementReceipt->value,
            PermissionEnum::ViewComptabiliteReports->value,
        ]);

        Role::findByName(RoleEnum::Avocat->value)->syncPermissions([
            PermissionEnum::ViewClients->value,
            PermissionEnum::CreateClients->value,
            PermissionEnum::UpdateClients->value,
            PermissionEnum::ViewDossiers->value,
            PermissionEnum::CreateDossiers->value,
            PermissionEnum::UpdateDossiers->value,
            PermissionEnum::CloseDossiers->value,
            PermissionEnum::ArchiveDossiers->value,
            PermissionEnum::AssignDossiers->value,
            PermissionEnum::ViewAgenda->value,
            PermissionEnum::CreateAgenda->value,
            PermissionEnum::UpdateAgenda->value,
            PermissionEnum::CancelAgenda->value,
            PermissionEnum::ShareAgenda->value,
            PermissionEnum::ManageAgendaNotifications->value,
            PermissionEnum::SyncAgenda->value,
            PermissionEnum::ViewDocuments->value,
            PermissionEnum::UploadDocuments->value,
            PermissionEnum::DownloadDocuments->value,
        ]);

        Role::findByName(RoleEnum::Secretaire->value)->syncPermissions([
            PermissionEnum::ViewClients->value,
            PermissionEnum::CreateClients->value,
            PermissionEnum::UpdateClients->value,
            PermissionEnum::ViewDossiers->value,
            PermissionEnum::CreateDossiers->value,
            PermissionEnum::UpdateDossiers->value,
            PermissionEnum::AssignDossiers->value,
            PermissionEnum::ViewAgenda->value,
            PermissionEnum::CreateAgenda->value,
            PermissionEnum::UpdateAgenda->value,
            PermissionEnum::CancelAgenda->value,
            PermissionEnum::ShareAgenda->value,
            PermissionEnum::ManageAgendaNotifications->value,
            PermissionEnum::ViewDocuments->value,
            PermissionEnum::UploadDocuments->value,
            PermissionEnum::DownloadDocuments->value,
        ]);

        Role::findByName(RoleEnum::Comptable->value)->syncPermissions([
            PermissionEnum::ViewClients->value,
            PermissionEnum::ViewDossiers->value,
            PermissionEnum::ViewAgenda->value,
            PermissionEnum::ViewFactures->value,
            PermissionEnum::CreateFactures->value,
            PermissionEnum::UpdateFactures->value,
            PermissionEnum::SendFactures->value,
            PermissionEnum::CancelFactures->value,
            PermissionEnum::DownloadFacturePdf->value,
            PermissionEnum::ViewPaiements->value,
            PermissionEnum::CreatePaiements->value,
            PermissionEnum::DownloadPaiementReceipt->value,
            PermissionEnum::ViewComptabiliteReports->value,
        ]);

        Role::findByName(RoleEnum::Stagiaire->value)->syncPermissions([
            PermissionEnum::ViewClients->value,
            PermissionEnum::UpdateClients->value,
            PermissionEnum::ViewDossiers->value,
            PermissionEnum::UpdateDossiers->value,
            PermissionEnum::ViewAgenda->value,
            PermissionEnum::UpdateAgenda->value,
            PermissionEnum::ViewDocuments->value,
            PermissionEnum::UploadDocuments->value,
            PermissionEnum::DownloadDocuments->value,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
