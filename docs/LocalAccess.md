# Local Access

## Paramètres PostgreSQL en local

Les valeurs réelles doivent être stockées dans `.env` et ne doivent pas être versionnées.

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=wapi_local
DB_USERNAME=postgres
DB_PASSWORD=change-me
```

## Dépôt GitHub

https://github.com/benjbugandwa/wapi-project.git

## Application Laravel

Le code applicatif Laravel doit être placé dans :

```text
C:\xampp\htdocs\wapi-project\wapi
```

La cible projet reste PHP 8.4. La création initiale du squelette Laravel 12 a été autorisée avec PHP 8.2.12, version actuellement disponible localement.
