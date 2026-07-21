# Wapi

Wapi est un SaaS multi-tenant de gestion de cabinets d'avocats.

## Stack Cible

- Laravel 12
- Livewire 4
- PHP 8.4 cible projet
- PostgreSQL
- Redis
- Tailwind CSS
- Alpine.js
- Spatie Permission
- Pest

## Installation Locale

Le projet applicatif Laravel se trouve dans :

```text
C:\xampp\htdocs\wapi-project\wapi
```

Installer les dépendances PHP :

```bash
composer install
```

Créer le fichier `.env` à partir de `.env.example`, puis générer la clé :

```bash
php artisan key:generate
```

Configuration locale attendue :

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=wapi_local
DB_USERNAME=postgres
DB_PASSWORD=change-me

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

Si l'extension PHP Redis n'est pas installée localement, utiliser temporairement dans `.env` :

```env
QUEUE_CONNECTION=sync
CACHE_STORE=file
SESSION_DRIVER=file
```

## Note Environnement

La stack cible documentée reste PHP 8.4.

L'initialisation du squelette Laravel 12 a été effectuée avec PHP 8.2.12 après validation explicite, car c'est la version PHP disponible localement au moment de la création.

## Vérification

Afficher la version Laravel :

```bash
php artisan --version
```

Lister les routes applicatives :

```bash
php artisan route:list --except-vendor
```

Exécuter les tests :

```bash
php artisan test
```

Formater et vérifier le style :

```bash
vendor/bin/pint
vendor/bin/pint --test
```

Compiler les assets :

```bash
npm.cmd run build
```

## Dépendances Installées

- Laravel Framework `12.64.0`
- Laravel Breeze `2.4.2`
- Livewire `4.3.3`
- Livewire Volt `1.10.5`
- Spatie Laravel Permission `6.25.0`
- Pest `3.8.7`
- Pest Plugin Laravel `3.2.0`

Spatie Permission est configuré comme source de vérité pour les rôles et permissions. Le modèle `User` utilise le trait `HasRoles`, et les middlewares `role`, `permission` et `role_or_permission` sont déclarés dans `bootstrap/app.php`.

La migration Spatie publiée utilise `model_uuid` pour préparer l'association des rôles aux futurs modèles applicatifs UUID.

## Fondations Multi-Tenant

Les entites applicatives principales utilisent des UUID :

- `Cabinet`
- `User`
- `CabinetInvitation`
- `AuditLog`

Les roles et permissions sont declares dans les enums `RoleEnum` et `PermissionEnum`, puis synchronises dans Spatie Permission via `RolePermissionSeeder`.

Executer les seeders de fondation :

```bash
php artisan db:seed
```

## Authentification et Administration

Le module auth/cabinets/utilisateurs inclut maintenant :

- inscription uniquement via invitation cabinet ;
- verification email Laravel ;
- blocage des comptes inactifs a la connexion ;
- blocage des utilisateurs d'un cabinet suspendu ;
- gestion systeme des cabinets ;
- invitations cabinet avec notification email ;
- activation/desactivation des utilisateurs ;
- attribution des roles via Spatie Permission ;
- audit logs pour les actions sensibles.

Routes web principales :

```text
/admin/cabinets
/admin/cabinets/create
/admin/cabinet-invitations
/admin/users
/cabinet-invitations/{token}/accept
```

Le middleware `tenant.active` protege les pages applicatives authentifiees contre les comptes inactifs et les cabinets suspendus.

## Gestion Des Clients

Le module clients couvre :

- clients personnes physiques et personnes morales ;
- recherche, filtres et pagination ;
- creation, modification et suppression logique ;
- pieces jointes client stockees hors acces public direct ;
- telechargement de documents via autorisation applicative ;
- audit logs sur les actions sensibles ;
- isolation stricte par `cabinet_id`.

Routes web principales :

```text
/clients
/clients/create
/clients/{client}
/clients/{client}/edit
/documents/{document}/download
```

## Documentation Projet

Les spécifications fonctionnelles et techniques sont dans :

```text
C:\xampp\htdocs\wapi-project\docs
```

Ces documents restent la source unique de vérité.
