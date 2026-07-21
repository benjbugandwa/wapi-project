# Architecture

## Stack

- Laravel 12
- Livewire 4
- PHP 8.4
- PostgreSQL
- Redis
- Queue
- Storage S3
- Docker
- Nginx

## Frontend

- Livewire
- Alpine.js
- Tailwind CSS
- Heroicons
- Chart.js
- Laravel-notify

## Backend

- Service Layer
- Repository Pattern lorsque les requêtes dépassent un accès Eloquent simple
- Policies
- Events
- Jobs
- Notifications
- DTO
- Enums
- Observers

## Auth

- Laravel Breeze
- Spatie Permission
- Email Verification
- Password Reset
- 2FA
- Invitations cabinet
- Approbation utilisateur

## Multi-Tenant

- Tenant par cabinet.
- `cabinet_id` obligatoire sur les tables métier sensibles.
- Isolation via Global Scopes, Policies et contraintes de validation.
- Tests d'isolation multi-tenant obligatoires pour les fonctionnalités métier.
- Les administrateurs système sont les seuls utilisateurs pouvant ne pas être liés à un cabinet.

## Rôles et Permissions

- Spatie Permission est la source de vérité pour les rôles et permissions.
- La table `users` ne contient pas de colonne `role`.
- Les rôles et permissions sont créés par seeders.
- Les Policies restent obligatoires pour contrôler les accès métier et l'isolation multi-tenant.
- Les middlewares Spatie peuvent être utilisés pour les accès de navigation simples, mais ne remplacent pas les Policies sur les actions métier.

## Notifications

- Email
- SMS
- WhatsApp optionnel
- Push
- In-App
- Traitement asynchrone via queues.

## Intégrations

- Google Calendar
- Microsoft Outlook
- Stripe
- PayPal
- SMTP

## Sécurité

- CSRF
- XSS
- Rate Limiting
- Audit Logs
- Encryption
- Backups
- Authorization Policies
- Mass Assignment Protection
- Fichiers servis via autorisation applicative

## Organisation Applicative Recommandée

- `app/Enums` pour centraliser les noms de rôles Spatie, permissions, statuts et types métier.
- `app/DTO` pour les objets de transfert.
- `app/Services` pour les règles métier.
- `app/Repositories` pour les accès de données complexes.
- `app/Policies` pour les autorisations.
- `app/Actions` possible pour les cas d'usage courts et atomiques.
- `app/Livewire` pour les composants UI.
- `tests/Feature` pour les workflows.
- `tests/Unit` pour les règles métier.
