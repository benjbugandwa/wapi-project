# Coding Standards

## Encodage

- Tous les fichiers doivent être encodés en UTF-8.
- Les accents français doivent être conservés correctement.
- Les secrets, mots de passe, tokens et clés API ne doivent jamais être versionnés dans la documentation ou le code.

---

## PHP

- PSR-12 obligatoire.
- `declare(strict_types=1);` obligatoire dans les fichiers PHP applicatifs.
- Utiliser les typed properties, return types et value objects lorsque pertinent.
- Utiliser des Enums PHP pour les statuts, types métier, canaux de notification et constantes de noms Spatie.
- Spatie Permission est la source de vérité pour les rôles et permissions ; ne pas dupliquer ces données dans une colonne `users.role`.
- Utiliser des DTO pour transporter des données entre Livewire/Controllers, Services et Repositories.

---

## Laravel

- Validation via FormRequest ou validation serveur Livewire.
- Autorisation via Policies et Gates.
- Gestion des rôles et permissions via Spatie Permission.
- Logique métier dans des Services dédiés.
- Accès aux données via Repositories lorsque la logique de requête dépasse un simple accès Eloquent.
- Utiliser Observers, Events, Jobs, Queues et Notifications pour les effets de bord.
- Les contrôleurs et composants Livewire doivent rester fins.
- Protéger systématiquement le mass assignment via `$fillable` ou `$guarded`.

---

## Livewire

- Un composant = une responsabilité.
- Validation côté serveur obligatoire.
- Pas de JavaScript inutile.
- Alpine.js est réservé aux interactions UI légères qui ne justifient pas un composant Livewire.

---

## Base de Données

- PostgreSQL est la base cible.
- Toutes les clés primaires applicatives utilisent UUID.
- Les clés étrangères suivent les conventions Laravel : `cabinet_id`, `user_id`, `client_id`, `dossier_id`, etc.
- Les clés étrangères sont obligatoires dès qu'une relation métier existe.
- Les tables métier sensibles doivent contenir `cabinet_id` pour garantir l'isolation multi-tenant directe.
- Les suppressions métier utilisent Soft Deletes, sauf table technique explicitement justifiée.
- Les colonnes de recherche, filtrage, tri, jointure et déclenchement de jobs doivent être indexées.
- Les montants utilisent `DECIMAL(12,2)` ou une précision supérieure si nécessaire.
- Les quantités facturables utilisent `DECIMAL`, jamais `INTEGER`, afin de permettre les heures partielles.

---

## Multi-Tenant

- Le tenant est le cabinet.
- Toutes les requêtes métier doivent être filtrées par `cabinet_id`, via Global Scopes, Policies ou contraintes explicites.
- Aucune donnée d'un cabinet ne doit être accessible depuis un autre cabinet.
- Chaque nouvelle fonctionnalité doit inclure au moins un test d'isolation multi-tenant si elle manipule des données cabinet.

---

## Git

- Branches : `feature/`, `bugfix/`, `hotfix/`, `release/`.
- Les migrations existantes ne doivent jamais être supprimées une fois partagées.

---

## Tests

- Tests Feature obligatoires pour les workflows utilisateur.
- Tests Unit obligatoires pour les règles métier et services.
- Tests Browser pour les parcours Livewire critiques.
- Couverture cible : > 80%.
- Chaque fonctionnalité doit tester validation, autorisation, erreurs et isolation multi-tenant lorsque applicable.

---

## Documentation

- Mettre à jour README, API, Database et Roadmap lorsqu'une fonctionnalité les impacte.
- PHPDoc uniquement lorsque cela clarifie un contrat ou une logique non évidente.
- Les commentaires doivent expliquer le pourquoi, pas répéter le code.
