<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionEnum: string
{
    case ViewCabinets = 'cabinets.view';
    case CreateCabinets = 'cabinets.create';
    case UpdateCabinets = 'cabinets.update';
    case SuspendCabinets = 'cabinets.suspend';
    case ActivateCabinets = 'cabinets.activate';

    case ViewUsers = 'users.view';
    case InviteUsers = 'users.invite';
    case ApproveUsers = 'users.approve';
    case ActivateUsers = 'users.activate';
    case DeactivateUsers = 'users.deactivate';
    case AssignRoles = 'users.assign_roles';

    case ViewClients = 'clients.view';
    case CreateClients = 'clients.create';
    case UpdateClients = 'clients.update';
    case DeleteClients = 'clients.delete';

    case ViewDossiers = 'dossiers.view';
    case CreateDossiers = 'dossiers.create';
    case UpdateDossiers = 'dossiers.update';
    case CloseDossiers = 'dossiers.close';
    case ArchiveDossiers = 'dossiers.archive';
    case AssignDossiers = 'dossiers.assign';

    case ViewAgenda = 'agenda.view';
    case CreateAgenda = 'agenda.create';
    case UpdateAgenda = 'agenda.update';
    case CancelAgenda = 'agenda.cancel';
    case ShareAgenda = 'agenda.share';
    case ManageAgendaNotifications = 'agenda.manage_notifications';
    case SyncAgenda = 'agenda.sync';

    case ViewDocuments = 'documents.view';
    case UploadDocuments = 'documents.upload';
    case DownloadDocuments = 'documents.download';
    case DeleteDocuments = 'documents.delete';

    case ViewFactures = 'factures.view';
    case CreateFactures = 'factures.create';
    case UpdateFactures = 'factures.update';
    case SendFactures = 'factures.send';
    case CancelFactures = 'factures.cancel';
    case DownloadFacturePdf = 'factures.download_pdf';
    case ViewPaiements = 'paiements.view';
    case CreatePaiements = 'paiements.create';
    case DownloadPaiementReceipt = 'paiements.download_receipt';
    case ViewComptabiliteReports = 'comptabilite.reports';

    case ManageRoles = 'roles.manage';
    case ManagePermissions = 'permissions.manage';

    public function label(): string
    {
        return match ($this) {
            self::ViewCabinets => 'Consulter les cabinets',
            self::CreateCabinets => 'Créer des cabinets',
            self::UpdateCabinets => 'Modifier des cabinets',
            self::SuspendCabinets => 'Suspendre des cabinets',
            self::ActivateCabinets => 'Réactiver des cabinets',
            self::ViewUsers => 'Consulter les utilisateurs',
            self::InviteUsers => 'Inviter des utilisateurs',
            self::ApproveUsers => 'Approuver des utilisateurs',
            self::ActivateUsers => 'Activer des utilisateurs',
            self::DeactivateUsers => 'Désactiver des utilisateurs',
            self::AssignRoles => 'Attribuer des rôles',
            self::ViewClients => 'Consulter les clients',
            self::CreateClients => 'Creer des clients',
            self::UpdateClients => 'Modifier des clients',
            self::DeleteClients => 'Supprimer des clients',
            self::ViewDossiers => 'Consulter les dossiers',
            self::CreateDossiers => 'Creer des dossiers',
            self::UpdateDossiers => 'Modifier des dossiers',
            self::CloseDossiers => 'Cloturer des dossiers',
            self::ArchiveDossiers => 'Archiver des dossiers',
            self::AssignDossiers => 'Affecter des collaborateurs aux dossiers',
            self::ViewAgenda => 'Consulter l agenda',
            self::CreateAgenda => 'Creer des evenements agenda',
            self::UpdateAgenda => 'Modifier des evenements agenda',
            self::CancelAgenda => 'Annuler des evenements agenda',
            self::ShareAgenda => 'Partager des evenements agenda',
            self::ManageAgendaNotifications => 'Gerer les notifications agenda',
            self::SyncAgenda => 'Synchroniser l agenda',
            self::ViewDocuments => 'Consulter les documents',
            self::UploadDocuments => 'Ajouter des documents',
            self::DownloadDocuments => 'Telecharger des documents',
            self::DeleteDocuments => 'Supprimer des documents',
            self::ViewFactures => 'Consulter les factures',
            self::CreateFactures => 'Creer des factures',
            self::UpdateFactures => 'Modifier des factures',
            self::SendFactures => 'Emettre des factures',
            self::CancelFactures => 'Annuler des factures',
            self::DownloadFacturePdf => 'Telecharger les factures PDF',
            self::ViewPaiements => 'Consulter les paiements',
            self::CreatePaiements => 'Enregistrer des paiements',
            self::DownloadPaiementReceipt => 'Telecharger les recus de paiement',
            self::ViewComptabiliteReports => 'Consulter les rapports comptables',
            self::ManageRoles => 'Gérer les rôles',
            self::ManagePermissions => 'Gérer les permissions',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $permission): string => $permission->value,
            self::cases(),
        );
    }
}
