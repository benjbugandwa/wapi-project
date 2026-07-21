# Acceptance Criteria

## Principes Généraux

- Chaque tâche doit respecter `methodologie.md`, `AGENTS.md`, `CodingStandards.md` et les règles métier documentées.
- Une tâche validée ne doit modifier que les fichiers nécessaires à son objectif.
- Une tâche ne doit pas introduire de règle métier non documentée.
- Toute incohérence entre documents doit être signalée avant implémentation.
- Le code applicatif doit être créé dans `C:\xampp\htdocs\wapi-project\wapi`.

## Qualité

- Le code doit respecter PSR-12, SOLID, DRY, KISS et Clean Architecture.
- Les fichiers PHP applicatifs doivent utiliser `declare(strict_types=1);`.
- Les contrôleurs et composants Livewire doivent rester fins.
- La logique métier doit être placée dans des Services ou Actions dédiés.
- Les Repositories sont utilisés uniquement lorsque la logique d'accès aux données dépasse un accès Eloquent simple.

## Sécurité

- L'authentification est obligatoire pour les endpoints métier.
- L'autorisation doit être contrôlée par Policies, Gates ou middlewares adaptés.
- Spatie Permission est la source de vérité pour les rôles et permissions.
- Les rôles ne doivent pas être dupliqués dans une colonne `users.role`.
- Les données multi-tenant doivent être filtrées par `cabinet_id` côté serveur.
- Aucune donnée d'un cabinet ne doit être accessible par un autre cabinet.
- Les utilisateurs inactifs doivent être bloqués.
- Les utilisateurs d'un cabinet suspendu doivent être bloqués, sauf administrateurs système.
- Les actions sensibles doivent être journalisées dans `audit_logs`.

## Base de Données

- Les modèles métier applicatifs utilisent UUID comme clé primaire.
- Les clés étrangères suivent les conventions Laravel : `cabinet_id`, `user_id`, `client_id`, `dossier_id`.
- Les tables métier sensibles contiennent `cabinet_id`.
- Les colonnes de filtrage, tri, recherche et jointure doivent être indexées.
- Les suppressions métier utilisent Soft Deletes sauf justification technique documentée.

## Authentification et Utilisateurs

- Un utilisateur ne peut pas s'associer librement à un cabinet sans invitation, approbation, domaine autorisé ou validation explicite.
- L'email, le cabinet et les rôles ne sont pas modifiables depuis le profil utilisateur.
- La vérification d'adresse email est obligatoire.
- La réinitialisation et le changement de mot de passe doivent utiliser les mécanismes sécurisés Laravel.
- L'activation, la désactivation et l'approbation des comptes doivent respecter les rôles Spatie.

## Tests

- Chaque tâche d'implémentation doit inclure les tests adaptés : Unit, Feature ou Browser selon le périmètre.
- Les workflows utilisateur doivent être couverts par des tests Feature.
- Les règles métier doivent être couvertes par des tests Unit ou Feature.
- Toute fonctionnalité manipulant des données cabinet doit inclure un test d'isolation multi-tenant.
- Les tests d'autorisation sont obligatoires pour les actions sensibles.
- La couverture cible du projet est supérieure à 80%.

## Documentation

- Toute modification qui impacte l'API, la base de données, la roadmap ou les règles métier doit mettre à jour la documentation correspondante.
- Les secrets, mots de passe, tokens et clés API ne doivent jamais être versionnés.
