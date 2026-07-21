# Roadmap

## Phase 0 - Cadrage Technique

- Initialisation Laravel 12.
- Configuration PostgreSQL, Redis, queues et Docker.
- Mise en place des standards : PSR-12, PHPStan/Pint, tests.
- Création des Enums, conventions UUID et structure applicative.
- Correction et maintien de la documentation projet.

## Phase 1 - Infrastructure, Authentification et Multi-Tenant

- Authentification Laravel Breeze.
- Email verification.
- Password reset.
- 2FA.
- Cabinets.
- Invitations cabinet.
- Utilisateurs.
- Rôles et policies.
- Isolation multi-tenant.
- Audit logs.

Etat implementation initiale :

- Authentification Breeze/Livewire installee et adaptee au champ `nom`.
- Inscription libre remplacee par l'acceptation d'une invitation cabinet.
- Middleware `tenant.active` ajoute pour bloquer comptes inactifs et cabinets suspendus.
- Services applicatifs ajoutes pour cabinets, invitations, utilisateurs et audit logs.
- Policies ajoutees pour cabinets, invitations et utilisateurs.
- Ecrans Livewire ajoutes pour administration cabinets, invitations et utilisateurs.
- Tests Feature ajoutes pour autorisation, isolation multi-tenant, activation, roles et audit logs.

## Phase 2 - Clients, Dossiers et Documents

- Clients.
- Dossiers.
- Affectations utilisateurs/dossiers.
- Parties adverses.
- Documents.
- Autorisation de téléchargement.
- Tests d'isolation cabinet.

Decision implementation module Clients :

- La validation des comptes reste rattachee au module Authentification et Utilisateurs.
- Les documents client sont implementes avant le module Dossiers avec `documents.client_id` contraint et `documents.dossier_id` nullable sans FK.
- La contrainte FK `documents.dossier_id` sera ajoutee dans le module Dossiers.

Etat implementation module Dossiers :

- CRUD dossiers Livewire implemente avec filtres, pagination, modales scrollables et isolation multi-tenant.
- Affectations collaborateurs implementees via `dossier_user` avec un avocat responsable obligatoire.
- Partie adverse implementee depuis la fiche dossier.
- Documents dossier implementes via le service documentaire commun client/dossier.
- Cloture et archivage implementes comme actions metier dediees ; la suppression directe n'est pas exposee.
- La cloture verifie les factures non annulees lorsque la table `factures` existe.

## Phase 3 - Agenda et Synchronisation

- Événements.
- Rappels.
- Notifications.
- Synchronisation Google Calendar.
- Synchronisation Microsoft Outlook.
- Préférences de canaux de notification par utilisateur.
- Participants/collaborateurs autorisés par événement.
- Webhooks Twilio pour réception SMS/WhatsApp.
- Annulation métier des événements par soft delete.

Etat implementation module Agenda :

- Migrations Agenda implementees : evenements, participants, rappels, preferences de notification, integrations calendrier, logs de synchronisation et webhooks entrants.
- Permissions Spatie Agenda ajoutees et assignees aux roles documentes.
- Services applicatifs ajoutes pour creation/modification/annulation d'evenements, partage, rappels, preferences, notifications et synchronisation externe abstraite.
- Notifications email implementees via Laravel Notifications ; SMS/WhatsApp sortants passent par une passerelle Twilio configurable.
- Webhook entrant Twilio expose via `/api/webhooks/twilio/messages` avec validation de signature activable.
- Ecrans Livewire Agenda ajoutes avec liste, filtres, pagination, modales scrollables, preferences de notification, detail evenement et rappels.
- Tests Feature ajoutes pour workflows utilisateur, autorisation, isolation multi-tenant, queue et webhook.

## Phase 4 - Facturation et Paiements

- Modes de facturation.
- Factures.
- Lignes de facture.
- Paiements.
- Reçus.
- PDF.
- Règle de clôture des dossiers selon factures soldées.

Décisions implementation module Facturation :

- Les reçus sont générés depuis les paiements, sans table dédiée.
- Les factures soldées sont immuables ; les corrections passent par un avoir ou une nouvelle facture.
- Les factures non annulées et non soldées bloquent la clôture du dossier.
- Les PDF de factures et reçus sont servis via autorisation applicative.

Etat implementation module Facturation :

- Migrations, modèles, enums, factories, services, policies, routes et vues Livewire ajoutés.
- CRUD factures couvert : création, modification avant solde/annulation, émission, annulation et PDF.
- Paiements couverts : enregistrement, statut facture recalculé, rejet du surpaiement et reçu PDF depuis `paiements`.
- Rapports comptables ajoutés : factures en souffrance et situation client.
- Règle de clôture dossier validée par tests : blocage si facture liée non annulée et non soldée.
- Permissions Spatie ajoutées pour `admin`, `comptable` et `system_admin`.

## Phase 5 - Portail Client et Messagerie

- Messagerie interne par dossier.
- Liens sécurisés portail client.
- Expiration et révocation des liens.
- Vue client de l'avancement du dossier.

## Phase 6 - Rapports et Dashboard

- KPIs.
- Graphiques.
- Rapports de retard de paiement.
- Statistiques par période.
- Dashboard par rôle.

## Phase 7 - Optimisations, Sécurité et CI/CD

- Performance.
- Sécurité avancée.
- Backups.
- Observabilité.
- CI/CD.
- Tests browser.
- Couverture > 80%.
