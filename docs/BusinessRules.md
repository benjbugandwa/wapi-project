# Business Rules

## Règles Métier Générales

- Un dossier ne peut être clôturé que si toutes ses factures non annulées sont soldées.
- La clôture d'un dossier est une action métier distincte de la suppression.
- La suppression d'un dossier est interdite côté interface utilisateur standard ; un dossier doit être clôturé ou archivé.
- Un cabinet suspendu n'est pas visible côté interface utilisateur pour les utilisateurs non administrateurs système.
- Les utilisateurs d'un cabinet suspendu ne peuvent pas accéder à l'application métier tant que le cabinet reste suspendu.
- Les données d'un cabinet ne doivent jamais être accessibles par les utilisateurs d'un autre cabinet.
- Toute action sensible doit être journalisée dans les audit logs.

## Multi-Tenant

- Le cabinet est le tenant.
- Chaque utilisateur appartient à un seul cabinet, sauf administrateur système.
- Les administrateurs système peuvent gérer les cabinets, les suspensions et les approbations, mais leurs accès doivent être tracés.
- Toutes les entités métier sensibles doivent porter `cabinet_id`.
- Les relations entre entités doivent toujours être cohérentes avec le même `cabinet_id`.

## Inscription et Approbation

- Un utilisateur ne peut pas choisir librement son cabinet sans contrôle.
- L'association d'un utilisateur à un cabinet nécessite au moins une des garanties suivantes :
  - invitation valide émise par le cabinet ou un administrateur ;
  - approbation par un administrateur ;
  - domaine email autorisé et validé ;
  - validation explicite par le cabinet.
- Après inscription, l'utilisateur est redirigé vers la page de connexion.
- Les champs email, cabinet et rôle ne peuvent pas être modifiés depuis le profil utilisateur.

## Rôles

- Spatie Permission est la source de vérité pour l'attribution des rôles et permissions.
- La table `users` ne doit pas contenir de colonne `role`.
- Les noms ci-dessous sont les rôles Spatie officiels du projet.

- `system_admin` : administrateur système global.
- `admin` : administrateur d'un cabinet.
- `avocat` : avocat du cabinet.
- `secretaire` : secrétaire juridique.
- `comptable` : comptable ou financier.
- `stagiaire` : stagiaire du cabinet.

## Gestion des Accès

- `system_admin` : accès global aux fonctions d'administration système, y compris création, suspension et réactivation des cabinets.
- `admin` : accès total aux données de son cabinet, y compris approbation des comptes utilisateurs et activation/désactivation des utilisateurs du cabinet.
- `avocat` : peut créer/éditer un client, créer/éditer un dossier, consulter/ajouter des documents à un dossier, consulter/ajouter un rendez-vous, consulter l'état d'avancement d'un dossier et clôturer un dossier.
- `secretaire` : peut créer/éditer un client, créer/éditer un dossier, consulter/ajouter des documents à un dossier, consulter/ajouter un rendez-vous et consulter l'état d'avancement d'un dossier.
- `stagiaire` : peut éditer un client, éditer un dossier, ajouter des documents à un dossier, consulter le calendrier et consulter l'état d'avancement d'un dossier.
- `comptable` : peut établir/imprimer une facture, consulter/enregistrer un paiement, imprimer un reçu de paiement et consulter les dossiers en retard de paiement.

## Facturation

- Une facture soldée ne peut plus être modifiée ; une correction doit passer par un avoir ou une nouvelle facture.
- Un paiement ne peut pas rendre le total payé supérieur au montant TTC restant dû.
- Les numéros de facture doivent être uniques, séquentiels et isolés par cabinet.
- Les factures annulées ne bloquent pas la clôture d'un dossier.
- Un dossier ne peut pas être clôturé tant qu'il existe au moins une facture non annulée dont le statut n'est pas `soldee`.
- Le reçu de paiement est généré depuis l'enregistrement de paiement, sans table `recus` séparée.
- Les PDF de facture et de reçu doivent être servis via autorisation applicative.

## Documents

- Les documents confidentiels ne sont visibles que par les rôles autorisés du cabinet.
- Un document lié à un dossier ou à un client doit avoir le même `cabinet_id` que ce dossier ou client.
- Les fichiers doivent être stockés hors du répertoire public direct et servis via une autorisation applicative.

## Agenda et Rappels

- Les rappels sont déclenchés à partir de `date_declenchement`.
- Les rappels doivent être traités par queue.
- Un événement lié à un dossier doit avoir le même `cabinet_id` que ce dossier.
- Un participant à un événement doit appartenir au même cabinet que l'événement.
- Les participants d'un événement sont les utilisateurs autorisés à voir l'événement en plus du créateur et des administrateurs autorisés du cabinet.
- Les notifications sortantes respectent les préférences de canaux configurées par chaque utilisateur.
- Les canaux SMS et WhatsApp utilisent Twilio comme fournisseur webhook et passerelle de distribution.
- Les webhooks entrants Twilio sont journalisés avant tout traitement métier afin de conserver la traçabilité.
- L'annulation d'un événement est une action métier avec soft delete et ne correspond pas à une suppression physique.
- Les connexions Google Calendar et Microsoft Outlook sont propres à chaque utilisateur et doivent être isolées par cabinet.
