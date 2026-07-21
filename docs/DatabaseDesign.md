# Database Design

## Principes

- Base cible : PostgreSQL.
- Toutes les clés primaires utilisent UUID.
- Les clés étrangères suivent les conventions Laravel : `cabinet_id`, `user_id`, `client_id`, `dossier_id`, etc.
- Toutes les tables métier sensibles contiennent `cabinet_id` pour garantir l'isolation multi-tenant.
- Les suppressions métier utilisent Soft Deletes.
- Les statuts, types et canaux sont représentés par des Enums PHP et stockés en chaînes courtes.
- Spatie Permission est la source de vérité pour les rôles et permissions.
- Les Enums de rôles et permissions servent uniquement à centraliser les noms utilisés par Spatie ; aucune colonne `role` ne doit être ajoutée à `users`.
- Les colonnes `created_at`, `updated_at` sont obligatoires sur les tables métier.
- Les colonnes `deleted_at` sont obligatoires sur les tables métier supprimables.

## Tables d'Administration

### `cabinets`

- `id`, UUID, clé primaire.
- `denomination`, VARCHAR(255), nom du cabinet.
- `adresse_cabinet`, VARCHAR(255), adresse physique.
- `description`, TEXT, optionnel.
- `nom_repondant`, VARCHAR(255), nom du responsable principal.
- `telephone_repondant`, VARCHAR(50), téléphone du responsable principal.
- `email_repondant`, VARCHAR(255), email du responsable principal, optionnel.
- `statut_cabinet`, VARCHAR(50), enum : `actif`, `suspendu`.
- `logo_cabinet`, VARCHAR(500), chemin du logo, optionnel.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.
- `deleted_at`, TIMESTAMP, optionnel.

Index :
- index sur `statut_cabinet`.
- index unique sur `denomination` si la règle commerciale impose l'unicité globale.

### `users`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`, nullable uniquement pour `system_admin`.
- `nom`, VARCHAR(150).
- `email`, VARCHAR(255).
- `password`, VARCHAR(255).
- `email_verified_at`, TIMESTAMP, optionnel.
- `phone_number`, VARCHAR(50), optionnel.
- `profile_photo_path`, VARCHAR(500), optionnel.
- `is_active`, BOOLEAN, défaut TRUE.
- `last_login_at`, TIMESTAMP, optionnel.
- `two_factor_secret`, TEXT, optionnel, chiffré.
- `two_factor_recovery_codes`, TEXT, optionnel, chiffré.
- `remember_token`, VARCHAR(100), optionnel.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.
- `deleted_at`, TIMESTAMP, optionnel.

Index :
- unique sur `email`.
- index sur `cabinet_id`.
- index composé sur `cabinet_id`, `is_active`.

Rôles et permissions :
- Les rôles utilisateurs sont attribués via Spatie Permission.
- Un utilisateur ne doit avoir qu'un rôle principal parmi : `system_admin`, `admin`, `avocat`, `secretaire`, `comptable`, `stagiaire`.
- `system_admin` est le seul rôle autorisé sans `cabinet_id`.

### `cabinet_invitations`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `email`, VARCHAR(255).
- `role_name`, VARCHAR(50), nom du rôle Spatie à attribuer après acceptation.
- `token`, VARCHAR(255), hash SHA-256 du token envoye par notification.
- `invited_by`, UUID, FK vers `users.id`.
- `accepted_at`, TIMESTAMP, optionnel.
- `expires_at`, TIMESTAMP.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.

Index :
- unique sur `cabinet_id`, `email` pour les invitations actives.
- index sur `role_name`.
- index sur `token`.
- index sur `expires_at`.

### Tables Spatie Permission

Les tables Spatie Permission sont générées par le package `spatie/laravel-permission`.

Tables attendues :
- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`

Règles :
- Spatie Permission est la source de vérité pour les rôles et permissions.
- Les rôles documentés sont : `system_admin`, `admin`, `avocat`, `secretaire`, `comptable`, `stagiaire`.
- Les permissions doivent être seedées et assignées aux rôles via seeders.
- Le guard par défaut est `web`, sauf justification documentée.
- Les IDs des tables Spatie doivent être compatibles avec la configuration Laravel retenue lors de l'installation. Si l'adaptation UUID des tables Spatie introduit une complexité excessive, les tables Spatie peuvent conserver leurs IDs internes par défaut, à condition que les modèles métier applicatifs restent en UUID.

## Tables Client et Dossier

### `clients`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `type_client`, VARCHAR(30), enum : `personne_physique`, `personne_morale`.
- `nom`, VARCHAR(150), nom de famille ou raison sociale.
- `prenom`, VARCHAR(100), nullable pour les personnes morales.
- `email`, VARCHAR(255), optionnel.
- `telephone_principal`, VARCHAR(50), optionnel.
- `adresse_rue`, VARCHAR(255), optionnel.
- `ville`, VARCHAR(100), optionnel.
- `numero_identification`, VARCHAR(100), optionnel.
- `representant_legal`, VARCHAR(150), optionnel.
- `notes_internes`, TEXT, optionnel.
- `created_by`, UUID, FK vers `users.id`.
- `updated_by`, UUID, FK vers `users.id`, optionnel.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.
- `deleted_at`, TIMESTAMP, optionnel.

Index :
- index sur `cabinet_id`.
- index sur `nom`.
- index sur `email`.
- index composé sur `cabinet_id`, `type_client`.

### `dossiers`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `client_id`, UUID, FK vers `clients.id`.
- `numero_reference`, VARCHAR(50), identifiant lisible.
- `titre_dossier`, VARCHAR(255).
- `description`, TEXT, optionnel.
- `domaine_droit`, VARCHAR(100), enum ou référentiel : `penal`, `civil`, `social`, `autre`.
- `statut_dossier`, VARCHAR(50), enum : `ouvert`, `en_cours`, `en_attente`, `plaide`, `clos`, `archive`.
- `date_ouverture`, DATE.
- `date_cloture`, DATE, optionnel.
- `juridiction`, VARCHAR(150), optionnel.
- `type_facturation`, VARCHAR(50), enum : `forfait`, `taux_horaire`, `honoraire_resultat`, `aide_juridictionnelle`.
- `created_by`, UUID, FK vers `users.id`.
- `updated_by`, UUID, FK vers `users.id`, optionnel.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.
- `deleted_at`, TIMESTAMP, optionnel.

Index :
- unique sur `cabinet_id`, `numero_reference`.
- index sur `cabinet_id`.
- index sur `client_id`.
- index composé sur `cabinet_id`, `statut_dossier`.
- index sur `date_ouverture`.

### `dossier_user`

Table pivot d'affectation des collaborateurs aux dossiers.

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `dossier_id`, UUID, FK vers `dossiers.id`.
- `user_id`, UUID, FK vers `users.id`.
- `role_dossier`, VARCHAR(50), enum : `responsable`, `collaborateur`, `observateur`.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.

Index :
- unique sur `dossier_id`, `user_id`.
- index sur `cabinet_id`.

### `parties_adverses`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `dossier_id`, UUID, FK vers `dossiers.id`.
- `type_partie`, VARCHAR(30), enum : `personne_physique`, `personne_morale`.
- `nom`, VARCHAR(150).
- `prenom`, VARCHAR(100), optionnel.
- `email`, VARCHAR(255), optionnel.
- `telephone`, VARCHAR(50), optionnel.
- `adresse`, VARCHAR(255), optionnel.
- `avocat_nom`, VARCHAR(150), optionnel.
- `avocat_email`, VARCHAR(255), optionnel.
- `notes`, TEXT, optionnel.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.
- `deleted_at`, TIMESTAMP, optionnel.

Index :
- index sur `cabinet_id`.
- index sur `dossier_id`.

## Documents

### `documents`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `dossier_id`, UUID, FK vers `dossiers.id`, optionnel.
- `client_id`, UUID, FK vers `clients.id`, optionnel.
- `nom_original`, VARCHAR(255).
- `nom_systeme`, VARCHAR(255).
- `chemin_acces`, VARCHAR(500).
- `extension`, VARCHAR(20).
- `type_mime`, VARCHAR(150).
- `taille_octets`, BIGINT.
- `categorie`, VARCHAR(100).
- `est_confidentiel`, BOOLEAN, défaut TRUE.
- `uploaded_by`, UUID, FK vers `users.id`.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.
- `deleted_at`, TIMESTAMP, optionnel.

Contraintes :
- `dossier_id` ou `client_id` doit être renseigné.
- Le `cabinet_id` doit correspondre au cabinet du dossier ou du client lié.

Decision implementation module Clients :
- `client_id` est pleinement contraint vers `clients.id`.
- `dossier_id` est prepare en UUID nullable sans contrainte FK tant que la table `dossiers` n'est pas encore implementee.
- Une migration future du module Dossiers ajoutera la contrainte FK vers `dossiers.id`.

Decision implementation module Dossiers :
- La table `dossiers` est implementee avec UUID, `cabinet_id`, `client_id`, statuts, domaine de droit, type de facturation et Soft Deletes.
- La table pivot `dossier_user` gere les affectations collaborateurs avec `role_dossier`.
- La table `parties_adverses` gere les informations de la partie adverse liee au dossier.
- La contrainte FK `documents.dossier_id` vers `dossiers.id` est ajoutee dans le module Dossiers.
- La suppression directe d'un dossier n'est pas exposee dans l'interface standard.

Index :
- index sur `cabinet_id`.
- index sur `dossier_id`.
- index sur `client_id`.
- index composé sur `cabinet_id`, `categorie`.

## Facturation et Paiements

### `factures`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `client_id`, UUID, FK vers `clients.id`.
- `dossier_id`, UUID, FK vers `dossiers.id`, optionnel.
- `numero_facture`, VARCHAR(50).
- `type_facture`, VARCHAR(50), enum : `provision`, `definitive`, `avoir`.
- `statut`, VARCHAR(50), enum : `brouillon`, `envoyee`, `payee_partiellement`, `soldee`, `annulee`.
- `date_emission`, DATE.
- `date_echeance`, DATE.
- `montant_ht`, DECIMAL(12,2).
- `taux_tva`, DECIMAL(5,2).
- `montant_tva`, DECIMAL(12,2).
- `montant_ttc`, DECIMAL(12,2).
- `notes_client`, TEXT, optionnel.
- `created_by`, UUID, FK vers `users.id`.
- `updated_by`, UUID, FK vers `users.id`, optionnel.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.
- `deleted_at`, TIMESTAMP, optionnel.

Index :
- unique sur `cabinet_id`, `numero_facture`.
- index sur `cabinet_id`.
- index sur `client_id`.
- index sur `dossier_id`.
- index composé sur `cabinet_id`, `statut`.
- index sur `date_echeance`.

### `lignes_facture`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `facture_id`, UUID, FK vers `factures.id`.
- `description`, TEXT.
- `quantite`, DECIMAL(10,2).
- `prix_unitaire`, DECIMAL(12,2).
- `total_ligne_ht`, DECIMAL(12,2).
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.

Index :
- index sur `cabinet_id`.
- index sur `facture_id`.

### `paiements`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `facture_id`, UUID, FK vers `factures.id`.
- `montant_paye`, DECIMAL(12,2).
- `date_paiement`, DATE.
- `methode_paiement`, VARCHAR(100), enum : `virement`, `cheque`, `especes`, `carte`, `mobile_money`, `autre`.
- `reference_transaction`, VARCHAR(255), optionnel.
- `created_by`, UUID, FK vers `users.id`.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.
- `deleted_at`, TIMESTAMP, optionnel.

Index :
- index sur `cabinet_id`.
- index sur `facture_id`.
- index sur `date_paiement`.

Décision implementation module Facturation :
- Le reçu de paiement est généré depuis la table `paiements`; aucune table `recus` séparée n'est nécessaire.
- Les PDF de factures et reçus sont des rendus applicatifs sécurisés soumis aux policies.
- Les factures non annulées dont le statut n'est pas `soldee` bloquent la clôture du dossier lié.
- Les avoirs sont des factures de type `avoir` et servent à corriger une facture soldée sans la modifier.

## Agenda et Notifications

### `evenements`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `dossier_id`, UUID, FK vers `dossiers.id`, optionnel.
- `titre`, VARCHAR(255).
- `type_evenement`, VARCHAR(50), enum : `audience`, `rendez_vous`, `date_butoir`, `reunion_interne`.
- `statut_evenement`, VARCHAR(50), enum : `planifie`, `modifie`, `annule`.
- `date_debut`, TIMESTAMP.
- `date_fin`, TIMESTAMP.
- `lieu`, VARCHAR(255), optionnel.
- `description`, TEXT, optionnel.
- `created_by`, UUID, FK vers `users.id`.
- `updated_by`, UUID, FK vers `users.id`, optionnel.
- `cancelled_at`, TIMESTAMP, optionnel.
- `cancelled_by`, UUID, FK vers `users.id`, optionnel.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.
- `deleted_at`, TIMESTAMP, optionnel.

Index :
- index sur `cabinet_id`.
- index sur `dossier_id`.
- index composé sur `cabinet_id`, `date_debut`.
- index sur `type_evenement`.
- index composé sur `cabinet_id`, `statut_evenement`.

### `agenda_participants`

Table pivot des collaborateurs autorisés à voir un événement.

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `evenement_id`, UUID, FK vers `evenements.id`.
- `user_id`, UUID, FK vers `users.id`.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.

Contraintes :
- Un participant doit appartenir au même cabinet que l'événement.
- Les participants sont les utilisateurs à notifier pour les créations, modifications, annulations et rappels.

Index :
- unique sur `evenement_id`, `user_id`.
- index sur `cabinet_id`.
- index sur `user_id`.

### `rappels`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `evenement_id`, UUID, FK vers `evenements.id`.
- `user_id`, UUID, FK vers `users.id`.
- `canal_notification`, VARCHAR(30), enum : `email`, `sms`, `in_app`, `push`, `whatsapp`.
- `delai_minutes`, INTEGER.
- `date_declenchement`, TIMESTAMP.
- `est_envoye`, BOOLEAN, défaut FALSE.
- `sent_at`, TIMESTAMP, optionnel.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.

Index :
- index sur `cabinet_id`.
- index sur `evenement_id`.
- index composé sur `est_envoye`, `date_declenchement`.

### `notification_preferences`

Préférences de canaux de notification par utilisateur.

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `user_id`, UUID, FK vers `users.id`.
- `canal_notification`, VARCHAR(30), enum : `email`, `sms`, `in_app`, `push`, `whatsapp`.
- `is_enabled`, BOOLEAN, défaut TRUE.
- `destination`, VARCHAR(255), optionnel.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.

Contraintes :
- `cabinet_id` doit correspondre au cabinet de l'utilisateur.
- Une seule préférence par `user_id` et `canal_notification`.

Index :
- unique sur `user_id`, `canal_notification`.
- index sur `cabinet_id`.

### `calendar_integrations`

Connexions OAuth des utilisateurs vers Google Calendar ou Microsoft Outlook.

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `user_id`, UUID, FK vers `users.id`.
- `provider`, VARCHAR(50), enum : `google`, `outlook`.
- `external_account_id`, VARCHAR(255), optionnel.
- `access_token`, TEXT, optionnel, chiffré.
- `refresh_token`, TEXT, optionnel, chiffré.
- `token_expires_at`, TIMESTAMP, optionnel.
- `is_active`, BOOLEAN, défaut TRUE.
- `last_synced_at`, TIMESTAMP, optionnel.
- `last_error`, TEXT, optionnel.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.

Contraintes :
- Une seule intégration active par `user_id` et `provider`.
- Les secrets OAuth ne doivent jamais être écrits en clair dans la documentation.

Index :
- unique sur `user_id`, `provider`.
- index sur `cabinet_id`.
- index sur `provider`.

### `calendar_sync_logs`

Journalisation des synchronisations avec les calendriers externes.

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `calendar_integration_id`, UUID, FK vers `calendar_integrations.id`.
- `evenement_id`, UUID, FK vers `evenements.id`.
- `provider`, VARCHAR(50), enum : `google`, `outlook`.
- `external_event_id`, VARCHAR(255), optionnel.
- `sync_status`, VARCHAR(50), enum : `pending`, `synced`, `failed`.
- `error_message`, TEXT, optionnel.
- `payload`, JSONB, optionnel.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.

Index :
- index sur `cabinet_id`.
- index sur `calendar_integration_id`.
- index sur `evenement_id`.
- index composé sur `provider`, `sync_status`.

### `notification_webhook_events`

Messages entrants reçus depuis le fournisseur SMS/WhatsApp. Le fournisseur retenu pour le module est Twilio, car il supporte SMS, WhatsApp et webhooks entrants/sortants avec un contrat stable.

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`, optionnel lorsqu'un message entrant ne peut pas encore être rattaché.
- `provider`, VARCHAR(50), enum : `twilio`.
- `channel`, VARCHAR(30), enum : `sms`, `whatsapp`.
- `external_message_id`, VARCHAR(255), optionnel.
- `from`, VARCHAR(100), optionnel.
- `to`, VARCHAR(100), optionnel.
- `body`, TEXT, optionnel.
- `payload`, JSONB.
- `processed_at`, TIMESTAMP, optionnel.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.

Index :
- index sur `cabinet_id`.
- index sur `provider`.
- index sur `external_message_id`.

## Communication et Portail Client

### `messages`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `dossier_id`, UUID, FK vers `dossiers.id`.
- `sender_id`, UUID, FK vers `users.id`.
- `body`, TEXT.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.
- `deleted_at`, TIMESTAMP, optionnel.

Index :
- index sur `cabinet_id`.
- index sur `dossier_id`.
- index sur `sender_id`.

### `client_portal_links`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `client_id`, UUID, FK vers `clients.id`.
- `dossier_id`, UUID, FK vers `dossiers.id`, optionnel.
- `token`, VARCHAR(255), hashé.
- `expires_at`, TIMESTAMP.
- `revoked_at`, TIMESTAMP, optionnel.
- `created_by`, UUID, FK vers `users.id`.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.

Index :
- index sur `cabinet_id`.
- index sur `client_id`.
- index sur `dossier_id`.
- index sur `token`.
- index sur `expires_at`.

## Suivi du Temps

### `time_entries`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`.
- `dossier_id`, UUID, FK vers `dossiers.id`.
- `user_id`, UUID, FK vers `users.id`.
- `description`, TEXT, optionnel.
- `started_at`, TIMESTAMP.
- `ended_at`, TIMESTAMP, optionnel.
- `duration_minutes`, INTEGER, optionnel.
- `is_billable`, BOOLEAN, défaut TRUE.
- `created_at`, TIMESTAMP.
- `updated_at`, TIMESTAMP.
- `deleted_at`, TIMESTAMP, optionnel.

Index :
- index sur `cabinet_id`.
- index sur `dossier_id`.
- index sur `user_id`.
- index sur `started_at`.

## Audit

### `audit_logs`

- `id`, UUID, clé primaire.
- `cabinet_id`, UUID, FK vers `cabinets.id`, nullable pour actions système globales.
- `user_id`, UUID, FK vers `users.id`, nullable pour actions système.
- `action`, VARCHAR(100).
- `auditable_type`, VARCHAR(255), optionnel.
- `auditable_id`, UUID, optionnel.
- `ip_address`, VARCHAR(45), optionnel.
- `user_agent`, TEXT, optionnel.
- `metadata`, JSONB, optionnel.
- `created_at`, TIMESTAMP.

Index :
- index sur `cabinet_id`.
- index sur `user_id`.
- index composé sur `auditable_type`, `auditable_id`.
- index sur `created_at`.
