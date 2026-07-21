# AGENTS.md

## Contexte

Tu participes au développement de Wapi, un SaaS de gestion de cabinets d'avocats.

L'objectif est de produire un code professionnel, maintenable, sécurisé et évolutif.

## Stack

- Laravel 12.
- Livewire 4.
- PHP 8.4.
- Tailwind CSS.
- Alpine.js.
- PostgreSQL.
- Redis.
- Docker.

## Règles

Toujours respecter :

- SOLID.
- DRY.
- KISS.
- Clean Code.
- Clean Architecture.
- PSR-12.

## Avant Chaque Implémentation

Toujours :

1. Analyser la demande.
2. Identifier les fichiers concernés.
3. Vérifier les règles métier documentées.
4. Proposer un plan.
5. Attendre validation si l'impact est important.

## Interdictions

Ne jamais :

- Casser les fonctionnalités existantes.
- Supprimer une migration existante.
- Modifier une API publique sans justification documentée.
- Dupliquer du code.
- Introduire une règle métier non documentée si une règle existe déjà dans `/docs`.
- Versionner des secrets, mots de passe, tokens ou clés API.

## Tests

Chaque fonctionnalité doit inclure :

- Validation.
- Tests unitaires ou feature selon le périmètre.
- Tests d'autorisation.
- Tests d'isolation multi-tenant lorsque des données cabinet sont manipulées.
- Gestion des erreurs.
- Logs ou audit logs pour les actions sensibles.

## Documentation

Toujours mettre à jour lorsque le changement les impacte :

- README.
- API.
- DatabaseDesign.
- Roadmap.
- BusinessRules.

## Sécurité

Toujours vérifier :

- Authentification.
- Autorisation.
- Isolation multi-tenant.
- Validation.
- Injection SQL.
- XSS.
- CSRF.
- Mass Assignment.
- Accès fichiers.
- Audit logs.

## Style de Code

Privilégier :

- Service Layer.
- Repositories.
- Form Requests.
- Policies.
- Events.
- Notifications.
- Queues.
- DTO.
- Enums.
- Observers.

## Règles UI obligatoires

Pour chaque module livré :

- Utiliser des modales scrollables pour les formulaires de création et de modification.
- Utiliser Laravel-Notify pour les notifications d'actions utilisateur.
- Afficher un spinner sur les traitements utilisateur longs ou asynchrones.
- Désactiver l'action principale pendant son traitement lorsque c'est pertinent.
- Demander une confirmation explicite avant toute action critique.
- Utiliser les logos officiels depuis `wapi/public/images/logos`.

## Réponses Attendues

Pour chaque tâche :

1. Résumé.
2. Plan.
3. Implémentation.
4. Tests.
5. Vérification.
6. Documentation.
