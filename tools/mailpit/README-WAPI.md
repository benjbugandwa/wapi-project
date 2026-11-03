# Mailpit pour Wapi

Version installee : Mailpit v1.30.4, archive officielle `mailpit-windows-amd64.zip`.

## Demarrer Mailpit

Depuis `C:\xampp\htdocs\wapi-project\wapi` :

```powershell
.\tools\mailpit\start-mailpit.ps1
```

Interface web :

```text
http://127.0.0.1:8025
```

## Configuration Laravel

Le fichier `.env` local utilise :

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="no-reply@wapi.local"
MAIL_FROM_NAME="${APP_NAME}"
```

Apres modification de `.env`, vider le cache de configuration :

```powershell
php artisan config:clear
```

Les notifications d'invitation sont queuees. En local, `.env` utilise `QUEUE_CONNECTION=sync`, donc l'email part immediatement. Si un autre environnement utilise Redis, lancer aussi :

```powershell
php artisan queue:work
```

## Tester une invitation

1. Demarrer Mailpit.
2. Demarrer Laravel.
3. Se connecter comme administrateur cabinet.
4. Aller dans `Invitations`.
5. Creer une invitation.
6. Ouvrir `http://127.0.0.1:8025`.
7. Ouvrir l'email recu et cliquer sur `Accepter l invitation`.
