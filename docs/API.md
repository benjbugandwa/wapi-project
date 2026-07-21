# API Specification

## Principes

- Les endpoints utilisent les termes métier français : `dossiers`, `clients`, `factures`.
- Toutes les réponses sont en JSON.
- Les endpoints métier sont protégés par authentification.
- Les requêtes métier sont filtrées par `cabinet_id` côté serveur, jamais depuis une valeur fournie librement par le client.
- La suppression métier standard utilise Soft Deletes ou une action métier explicite.
- La clôture d'un dossier est exposée via une action dédiée, pas via `DELETE`.

## Authentication

- `POST /login`
- `POST /logout`
- `POST /forgot-password`
- `POST /reset-password`
- `POST /email/verification-notification`
- `POST /two-factor-challenge`

## Cabinets

Endpoints réservés aux administrateurs système.

- `GET /cabinets`
- `POST /cabinets`
- `GET /cabinets/{id}`
- `PUT /cabinets/{id}`
- `PATCH /cabinets/{id}/suspend`
- `PATCH /cabinets/{id}/activate`

## Utilisateurs et Invitations

- `GET /users`
- `POST /cabinet-invitations`
- `POST /cabinet-invitations/{token}/accept`
- `PATCH /users/{id}/activate`
- `PATCH /users/{id}/deactivate`
- `PATCH /users/{id}/role`

Note implementation web :

- Les parcours Livewire equivalents sont disponibles via `/admin/users`, `/admin/cabinet-invitations` et `/cabinet-invitations/{token}/accept`.
- Les routes JSON ci-dessus restent le contrat cible pour une exposition API separee.

## Clients

- `GET /clients`
- `POST /clients`
- `GET /clients/{id}`
- `PUT /clients/{id}`
- `DELETE /clients/{id}`

Note implementation web :

- Les parcours Livewire equivalents sont disponibles via `/clients`, `/clients/create`, `/clients/{client}` et `/clients/{client}/edit`.
- La suppression client est une suppression logique.
- Les listes clients sont toujours filtrees par `cabinet_id` cote serveur.

## Dossiers

- `GET /dossiers`
- `POST /dossiers`
- `GET /dossiers/{id}`
- `PUT /dossiers/{id}`
- `PATCH /dossiers/{id}/close`
- `PATCH /dossiers/{id}/archive`

Notes :
- `PATCH /dossiers/{id}/close` échoue si toutes les factures non annulées du dossier ne sont pas soldées.
- `DELETE /dossiers/{id}` n'est pas exposé dans l'interface standard.

Note implementation web :

- Les parcours Livewire equivalents sont disponibles via `/dossiers`, `/dossiers/create`, `/dossiers/{dossier}` et `/dossiers/{dossier}/edit`.
- Les affectations collaborateurs, la partie adverse, la cloture, l'archivage et les documents dossier sont geres depuis la fiche dossier.
- La suppression directe d'un dossier reste absente de l'interface standard.
- La cloture verifie les factures non annulees si le module Facturation est present.

## Documents

- `GET /documents`
- `POST /documents`
- `GET /documents/{id}`
- `GET /documents/{id}/download`
- `PUT /documents/{id}`
- `DELETE /documents/{id}`

Note implementation client :

- Les pieces jointes client sont ajoutees depuis la fiche client.
- Le telechargement web securise est expose via `/documents/{document}/download`.
- Les fichiers sont servis via autorisation applicative, jamais par URL publique directe.

## Agenda

- `GET /evenements`
- `POST /evenements`
- `GET /evenements/{id}`
- `PUT /evenements/{id}`
- `DELETE /evenements/{id}`
- `POST /evenements/{id}/rappels`

Notes :
- `DELETE /evenements/{id}` représente une annulation métier avec soft delete, jamais une suppression physique.
- Les événements liés à un dossier doivent appartenir au même cabinet que ce dossier.
- Les rappels sont traités via queue à partir de `date_declenchement`.
- Les participants d'un événement sont gérés côté serveur et doivent appartenir au même cabinet.
- La synchronisation Google Calendar et Microsoft Outlook utilise des intégrations OAuth par utilisateur.
- Les messages entrants SMS/WhatsApp sont reçus via webhook Twilio.

Note implementation web :

- Les parcours Livewire equivalents sont disponibles via `/evenements` et `/evenements/{evenement}`.
- Les formulaires de creation et modification sont présentés dans des modales scrollables.
- Les annulations demandent une confirmation utilisateur explicite.

## Préférences Notifications

- `GET /notification-preferences`
- `PUT /notification-preferences`

## Synchronisation Calendrier

- `GET /calendar-integrations`
- `POST /calendar-integrations/{provider}/connect`
- `DELETE /calendar-integrations/{provider}`

## Webhooks Notifications

- `POST /webhooks/twilio/messages`

## Facturation

- `GET /factures`
- `POST /factures`
- `GET /factures/{id}`
- `PUT /factures/{id}`
- `POST /factures/{id}/send`
- `POST /factures/{id}/cancel`
- `GET /factures/{id}/pdf`

Notes :
- Une facture soldée ne peut plus être modifiée ; toute correction passe par un avoir ou une nouvelle facture.
- Les numéros de facture sont séquentiels et isolés par cabinet.
- Les PDF sont servis via autorisation applicative.
- Les factures non annulées et non soldées bloquent la clôture du dossier lié.

## Paiements

- `GET /paiements`
- `POST /paiements`
- `GET /paiements/{id}`
- `GET /paiements/{id}/receipt`

Notes :
- Le reçu de paiement est généré depuis `paiements`; il n'existe pas de table `recus`.
- Un paiement ne peut pas dépasser le montant TTC restant dû sur la facture.

## Portail Client

- `POST /client-portal-links`
- `PATCH /client-portal-links/{id}/revoke`
- `GET /portal/{token}`

## Response Format

### Success

```json
{
  "data": {},
  "message": "Action effectuée avec succès."
}
```

### Error

```json
{
  "message": "Une erreur est survenue.",
  "error": "ERROR_CODE"
}
```

### Validation

```json
{
  "message": "Les données fournies sont invalides.",
  "errors": {
    "field": ["Message d'erreur."]
  }
}
```

### Pagination

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 0
  }
}
```
