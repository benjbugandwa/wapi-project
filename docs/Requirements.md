# Fonctionnalités

## Création et Gestion des Cabinets

- Ajouter un nouveau cabinet avec dénomination, adresse, description, statut, logo, email et téléphone du répondant.
- Le statut par défaut d'un cabinet est `actif`.
- Les administrateurs système peuvent suspendre un cabinet pour non-paiement de l'abonnement.
- Un cabinet suspendu n'est pas visible côté interface utilisateur pour les utilisateurs non administrateurs système.
- Les utilisateurs d'un cabinet suspendu ne peuvent pas accéder aux fonctionnalités métier.

## Inscription et Utilisateurs

- Une fois le cabinet créé, les membres peuvent s'inscrire avec nom complet, adresse email, mot de passe avec confirmation et rôle au sein du cabinet.
- L'association d'un utilisateur à un cabinet nécessite invitation, validation administrateur, domaine email autorisé ou approbation explicite.
- Après inscription, l'utilisateur est redirigé vers la page de connexion.
- Les utilisateurs peuvent réinitialiser leur mot de passe via un lien sécurisé.
- Les utilisateurs peuvent gérer leur profil : photo, nom et numéro de téléphone.
- Les champs email, cabinet et rôle ne peuvent pas être modifiés depuis le profil.
- Seuls les administrateurs autorisés peuvent modifier email, cabinet, rôle ou statut actif d'un utilisateur.

## Connexion et Page d'Accueil

- Connexion via email et mot de passe.
- Vérification email obligatoire.
- 2FA prévue selon la configuration de sécurité.
- Après connexion, l'utilisateur est redirigé vers un tableau de bord personnalisé selon son rôle.
- Tableau de bord avocat : dossiers en cours, état d'avancement, audiences à venir, documents liés.
- Tableau de bord comptable : dossiers en retard de paiement, revenus par catégorie et période.
- Tableau de bord administrateur cabinet : utilisateurs en attente, activité du cabinet, dossiers actifs, alertes.
- Tableau de bord administrateur système : cabinets, abonnements, suspensions, activité globale.

## Gestion des Clients

- Ajouter un client personne physique ou personne morale.
- Modifier les informations d'un client selon les droits du rôle.
- Consulter l'historique des dossiers d'un client.
- Archiver ou supprimer logiquement une fiche client si aucune règle métier ne le bloque.

## Gestion des Dossiers

- Ajouter un nouveau dossier.
- Affecter un ou plusieurs utilisateurs à un dossier.
- Désigner un avocat responsable du dossier.
- Ajouter/éditer les informations de la partie adverse.
- Définir le type de dossier : pénal, civil, social ou autre.
- Suivre et mettre à jour l'état d'avancement d'un dossier.
- Calculer et afficher l'âge d'un dossier depuis sa date d'ouverture.
- Clôturer un dossier uniquement si toutes ses factures non annulées sont soldées.
- Archiver un dossier clôturé.
- La suppression directe d'un dossier n'est pas une action métier standard.

## Documents

- Ajouter des documents ou pièces jointes à un dossier ou à un client.
- Types acceptés : Word, Excel, PDF, PNG, JPG, audio, vidéo.
- Chaque document contient type, dénomination, métadonnées techniques et confidentialité.
- Les documents confidentiels ne sont visibles que par les rôles autorisés.
- Les fichiers doivent être servis via autorisation applicative.

## Gestion des Délais et Agenda Partagé

- Ajouter un rendez-vous, une audience, une date butoir ou une réunion interne.
- Lier un événement à un dossier lorsque pertinent.
- Notifier automatiquement les avocats/collaborateurs affectés lors de l'ajout ou modification d'un événement.
- Synchroniser l'agenda avec Google Calendar ou Microsoft Outlook.
- Les rappels sont traités via queue.

## Facturation et Comptabilité

- Ajouter ou modifier le mode de facturation d'un dossier : forfait, taux horaire, honoraire de résultat, aide juridictionnelle.
- Émettre une facture.
- Imprimer ou télécharger une facture PDF.
- Enregistrer un paiement.
- Émettre un reçu de paiement.
- Suivre l'état de paiement des dossiers.
- Produire un rapport des dossiers en retard de paiement.
- Une facture soldée ne peut plus être modifiée ; toute correction passe par un avoir ou une nouvelle facture.

## Communication Interne et Portail Client

- Messagerie interne par dossier, inspirée de Microsoft Teams.
- Traçabilité des échanges internes.
- Génération d'un lien sécurisé pour permettre au client de suivre l'état d'avancement de son dossier.
- Les liens du portail client doivent être expirables et révocables.

## Sécurité et Audit

- Séparation stricte des données par cabinet.
- Autorisation par rôle et policy Laravel.
- Validation serveur obligatoire.
- Audit logs pour les actions sensibles.
- Protection contre injection SQL, XSS, CSRF et mass assignment.
