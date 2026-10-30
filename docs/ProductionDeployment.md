# Déploiement Production Railway

## Architecture cible

Wapi est déployé sur Railway avec :

- un service web Laravel ;
- un service worker pour les queues ;
- un service scheduler pour les tâches planifiées ;
- PostgreSQL Railway ;
- Redis Railway pour cache, sessions, maintenance mode et queues ;
- un Persistent Volume Railway monté pour les documents privés ;
- SMTP transactionnel pour les emails ;
- Twilio pour SMS et WhatsApp ;
- OAuth Google Calendar et Microsoft Graph pour les synchronisations calendrier.

Les documents clients et dossiers ne doivent jamais être stockés uniquement dans le filesystem éphémère du conteneur. En production, utiliser `FILESYSTEM_DISK=railway` et `DOCUMENTS_FILESYSTEM_DISK=railway`.

## Variables d'environnement

Créer les variables depuis `wapi/.env.example`.

Valeurs critiques :

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` générée par `php artisan key:generate --show`
- `APP_URL` avec le domaine Railway ou le domaine personnalisé
- `DB_*` ou `DB_URL` depuis PostgreSQL Railway
- `REDIS_URL` depuis Redis Railway
- `CACHE_STORE=redis`
- `SESSION_DRIVER=redis`
- `QUEUE_CONNECTION=redis`
- `FILESYSTEM_DISK=railway`
- `DOCUMENTS_FILESYSTEM_DISK=railway`
- `RAILWAY_STORAGE_PATH=/mnt/wapi-storage/app/private`
- `SESSION_SECURE_COOKIE=true`
- `TWILIO_VALIDATE_WEBHOOK_SIGNATURE=true`

Ne jamais versionner `.env`, tokens OAuth, clés Twilio, mots de passe SMTP ou accès base de données.

## Railway

Fichiers préparés :

- `wapi/railway.json`
- `wapi/Procfile`
- `wapi/scripts/railway-build.sh`
- `wapi/scripts/railway-start.sh`

Build command :

```bash
bash scripts/railway-build.sh
```

Start command web :

```bash
bash scripts/railway-start.sh
```

Health check :

```text
/up
```

Le script de démarrage applique les migrations, crée le lien storage si nécessaire, reconstruit les caches Laravel puis lance le serveur HTTP sur `$PORT`.

## Services Railway

Créer trois services à partir du même dépôt :

- `web` : start command `bash scripts/railway-start.sh`
- `worker` : start command `php artisan queue:work redis --queue=default --sleep=3 --tries=3 --timeout=120 --max-time=3600`
- `scheduler` : start command `php artisan schedule:work`

Le worker doit redémarrer automatiquement en cas d'échec. Les jobs échoués sont stockés dans `failed_jobs` et élagués par le scheduler après 168 heures.

## Stockage persistant

Créer un Persistent Volume Railway et le monter sur :

```text
/mnt/wapi-storage
```

Créer ou laisser Laravel créer :

```text
/mnt/wapi-storage/app/private
```

Les fichiers sont servis via contrôleur et policies, pas via URL publique directe.

À terme, pour haute disponibilité multi-instance, migrer les documents vers un stockage objet compatible S3 avec CDN privé ou URLs signées.

## Scheduler

Tâches actuellement planifiées :

- reprise des rappels dus toutes les minutes ;
- purge quotidienne des jobs échoués anciens.

Les futures synchronisations Google/Microsoft récurrentes doivent être ajoutées dans `routes/console.php` avec `withoutOverlapping()`.

## Notifications

Email :

- utiliser SMTP transactionnel : Postmark, Mailgun, Resend, SES ou équivalent ;
- garder `MAIL_FROM_ADDRESS` sur un domaine vérifié.

SMS et WhatsApp :

- utiliser Twilio ;
- activer `TWILIO_VALIDATE_WEBHOOK_SIGNATURE=true` ;
- déclarer l'URL webhook : `/api/webhooks/twilio/messages`.

WhatsApp production nécessite des templates approuvés côté Twilio/Meta.

## Google Calendar et Outlook

Préparer les applications OAuth :

- Google Cloud Console : Calendar API, redirect URI `${APP_URL}/calendar/google/callback`.
- Microsoft Entra ID : Microsoft Graph Calendar permissions, redirect URI `${APP_URL}/calendar/outlook/callback`.

Les refresh tokens doivent être chiffrés en base. Les logs de synchronisation ne doivent jamais exposer les tokens.

## Sécurité

Production obligatoire :

- `APP_DEBUG=false`
- `SESSION_SECURE_COOKIE=true`
- `SESSION_ENCRYPT=true`
- `TRUSTED_PROXIES=*` sur Railway
- `TRUSTED_HOSTS` renseigné dès que le domaine final est connu
- headers HTTP activés via `SECURITY_HEADERS_ENABLED=true`
- uploads validés par taille, extension et MIME
- documents stockés hors public
- secrets stockés dans Railway Variables uniquement

Prévoir une étape future d'antivirus asynchrone pour les documents, compatible ClamAV ou service externe.

## Sauvegardes

Base PostgreSQL :

- activer les backups Railway ;
- réaliser des exports réguliers `pg_dump` vers un espace externe sécurisé.

Documents :

- sauvegarder le Persistent Volume ;
- prévoir une réplication vers stockage objet S3 compatible.

Restaurer :

1. Mettre l'application en maintenance.
2. Restaurer PostgreSQL.
3. Restaurer le volume documents.
4. Lancer `php artisan migrate --force`.
5. Vérifier `/up`.
6. Désactiver la maintenance.

## Rollback

Procédure :

1. Identifier le dernier déploiement sain Railway.
2. Restaurer la version précédente.
3. Vérifier `/up`.
4. Vérifier login, clients, dossiers, documents, factures et workers.
5. Si une migration destructive a été appliquée, restaurer la sauvegarde base avant rollback applicatif.

## Incident

Checklist incident :

- consulter logs web, worker et scheduler ;
- vérifier PostgreSQL, Redis et volume ;
- vérifier queue failed jobs ;
- vérifier Twilio/SMTP/OAuth providers ;
- activer maintenance si risque utilisateur ;
- documenter cause, impact, correction et prévention.

## Checklist pré-production

- Toutes les variables Railway sont renseignées.
- PostgreSQL et Redis sont connectés.
- Volume monté et accessible.
- `APP_KEY` est défini.
- `APP_DEBUG=false`.
- `php artisan test` passe.
- `npm run build` passe.
- Health check `/up` répond.
- Worker et scheduler sont démarrés.
- Emails, Twilio et webhooks sont testés.
- Backups PostgreSQL et documents sont configurés.

## Checklist post-déploiement

- Tester inscription par invitation.
- Tester login/logout.
- Tester upload/download document.
- Tester création dossier et clôture bloquée par facture non soldée.
- Tester facture PDF et reçu PDF.
- Tester rappel agenda.
- Vérifier logs d'erreurs.
- Vérifier absence de jobs échoués.
- Vérifier que les documents persistent après redéploiement.

## Évolution haute disponibilité

Pour plusieurs milliers d'utilisateurs :

- remplacer le volume local par S3 compatible ;
- conserver Redis dédié pour cache, sessions et queues ;
- séparer services web, worker, scheduler ;
- ajouter monitoring applicatif avancé ;
- ajouter alerting erreurs et temps de réponse ;
- ajouter CI/CD avec tests, build, migration contrôlée et rollback automatisé ;
- ajouter CDN pour assets publics ;
- ajouter antivirus asynchrone pour uploads.
