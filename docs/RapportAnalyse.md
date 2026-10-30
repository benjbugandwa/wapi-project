# Rapport d’analyse du projet Wapi

**Date :** 26 octobre 2026  
**Périmètre :** application Laravel située dans `wapi/`, documentation du dépôt `docs/`. Analyse statique du code et des configurations, sans exécuter la suite de tests.

## Synthèse

Wapi est une application SaaS de gestion pour cabinets d’avocats, construite sur Laravel 12, Livewire 4 et Volt, avec une séparation métier déjà visible entre les vues/écrans, les services, les DTO, les enums, les policies et les modèles Eloquent. Le périmètre livré est substantiel : authentification sur invitation, gestion de tenants par cabinet, clients et documents, dossiers, agenda et rappels, factures, paiements, reçus PDF, rapports comptables, tâches asynchrones et intégrations de notification.

La base paraît cohérente pour une première version applicative et la couverture de tests feature est étendue. Les priorités avant une mise en production manipulant de vrais dossiers sont : renforcer l’isolation multi-tenant au niveau des relations et de la base de données; corriger plusieurs écarts entre la configuration de production et ses protections réellement actives; terminer les fonctionnalités annoncées comme 2FA et synchronisation de calendriers; et établir une mesure reproductible de la qualité par exécution des tests, analyse statique et CI.

## Fiche technique

| Élément | Constat |
|---|---|
| Framework | Laravel `^12.0`; README indique `12.64.0` installé |
| PHP | `^8.2` dans Composer; documentation vise PHP 8.4 |
| Interface | Livewire `^4.0`, Volt, Blade, Alpine.js, Tailwind CSS |
| Authentification et autorisations | Breeze, vérification email, invitations, Spatie Laravel Permission |
| Données | PostgreSQL documenté; migrations UUID, relations Eloquent et soft deletes |
| Services transverses | Redis/queues configurables, SMTP, Twilio, génération PDF Dompdf |
| Déploiement documenté | Railway, PostgreSQL, Redis, volume persistant, web + worker + scheduler |
| Tests | Pest 3; tests unitaires et feature par domaines |
| Langue produit | Français principalement, avec quelques libellés sans accents dans le code |

## Structure et architecture

- `app/Services` concentre les cas d’usage et règles métier, souvent avec transactions et journalisation.
- `app/DTO` transporte les données validées vers les services; `app/Enums` centralise rôles, permissions et statuts.
- `app/Policies` applique les autorisations par ressource; les vues Volt invoquent les gates pour les actions.
- `app/Models` porte relations, conversions et quelques règles simples; UUID et soft deletes sont utilisés pour les entités principales.
- `resources/views/livewire` contient directement les composants Volt anonymes, leur logique PHP et leurs vues Blade. Il n’existe pratiquement pas de classes métier sous `app/Livewire`; cela accélère le démarrage mais rend les vues très volumineuses et la logique plus difficile à tester indépendamment.
- `routes/web.php` expose les écrans métier et les téléchargements PDF/documents sous `auth`, `verified`, `tenant.active`. Le webhook Twilio est déclaré dans `routes/api.php`.
- `docs/` comprend les spécifications de référence, règles métier, architecture, conception de données, feuille de route et consignes de déploiement.

## Fonctionnalités visibles dans le code

### Authentification et administration

Connexion, déconnexion, réinitialisation de mot de passe, vérification email, acceptation d’invitation cabinet, utilisateurs et rôles Spatie, activation/désactivation de comptes, gestion et suspension de cabinets, audit logs et middleware d’accès pour les comptes inactifs ou cabinets suspendus. L’inscription libre est remplacée par une inscription associée à une invitation.

### Clients, dossiers et documents

Gestion des clients physiques et moraux, recherche/filtres/pagination, mises à jour et suppression logique; dossiers avec affectations, parties adverses, statuts, clôture et archivage; documents clients et dossiers téléversés sur un disque privé puis délivrés par contrôleur après autorisation.

### Agenda et notifications

Événements, participants, préférences de notification, rappels, jobs de notification, abstraction de synchronisation calendrier, journaux de synchronisation et réception de SMS/WhatsApp via webhook Twilio. Les notifications sortantes email et Twilio sont présentes.

### Facturation et comptabilité

Factures et lignes, calcul de montants, numérotation par cabinet, émission/annulation, paiements avec contrôle de surpaiement, état recalculé, PDFs de factures et reçus, rapports de factures en souffrance et situation client. La clôture d’un dossier vérifie les factures bloquantes.

### Déploiement et interface

Fichiers Railway, Procfile, scripts de build/démarrage, exemples d’environnement de production, routes de santé, volumes privés et recommandations de sauvegarde. Les formulaires métier utilisent des modales et les actions sensibles visibles incluent des confirmations.

## Évaluation des risques et priorités

### P1 — Isolation tenant et intégrité des relations

L’architecture décrite dans `docs/Architecture.md` prévoit des global scopes, mais l’analyse des modèles ne montre pas de scope tenant commun. L’isolation repose donc surtout sur les policies et les filtres ajoutés dans chaque écran/service. Une omission dans une nouvelle requête peut exposer des données d’un autre cabinet.

Plus concrètement, les migrations appliquent des clés étrangères simples vers `clients`, `users`, `dossiers`, etc., mais ne garantissent pas que les deux lignes liées appartiennent au même `cabinet_id`. Les formulaires et certains services vérifient cette cohérence, mais ces garanties ne couvrent pas nécessairement toutes les voies d’écriture.

Dans `DocumentService::upload`, le `cabinet_id` du document vient du propriétaire, tandis que `uploaded_by` vient de l’acteur; le service ne vérifie pas lui-même que l’acteur appartient au même cabinet. Les écrans contrôlent l’accès au propriétaire, ce qui protège les voies usuelles, mais la règle métier reste dépendante de ces appels. Le champ `est_confidentiel` est enregistré; la policy de document vérifie permission et tenant mais n’applique pas de règle de confidentialité distincte. Vérifier si les permissions actuelles couvrent effectivement toutes les catégories de documents confidentiels.

**Recommandation :** renforcer les contraintes par relations tenant cohérentes et validation centralisée des services, puis envisager un scope tenant commun avec une voie explicitement testée pour les administrateurs système. Ajouter des tests adversariaux pour les relations croisées client/dossier/document/facture, y compris les écritures effectuées hors interface.

### P1 — Signature du webhook Twilio configurable et reconstruction manuelle

`services.twilio.validate_webhook_signature` est désactivé par défaut dans `config/services.php`, alors que `.env.example` destiné à la production le met à `true`. Un environnement incomplet ou mal renseigné peut donc accepter des requêtes non signées. La signature est reconstruite manuellement avec `fullUrl()` et le contenu des champs; l’URL publique, le proxy, l’encodage et l’algorithme du fournisseur doivent correspondre exactement à cette reconstruction. Le webhook conserve également le payload reçu en base.

**Recommandation :** rendre la validation obligatoire en production, employer le validateur officiel Twilio et vérifier le comportement derrière le proxy Railway. Ajouter une protection contre les doublons via l’identifiant externe du message et une rétention documentée du payload.

### P1 — Configuration de confiance des proxies et CSP

`bootstrap/app.php` fait confiance à tous les proxies par défaut (`TRUSTED_PROXIES`, valeur `*`) et installe la CSP et les headers selon `config/wapi.php`. La valeur de CSP comprend `'unsafe-inline'` pour scripts et styles, ce qui réduit la protection XSS. `TRUSTED_HOSTS` est vide dans l’exemple, donc aucune restriction d’hôte n’est appliquée à moins d’être renseignée.

**Recommandation :** documenter précisément les proxies approuvés par l’hébergeur, définir les domaines autorisés et viser une CSP sans scripts inline (nonce ou hash compatible avec Livewire) après tests navigateur.

### P1 — Chaîne de stockage transactionnelle des documents

Le fichier est écrit sur le stockage dans la transaction DB avant la création complète de la ligne. Si la création ou l’audit échoue après le stockage, le rollback SQL ne supprime pas l’objet déjà écrit; des fichiers orphelins peuvent rester. La suppression logique du document ne semble pas supprimer son fichier, ce qui peut être intentionnel pour la conservation mais nécessite une politique de rétention/purge explicite.

**Recommandation :** définir une compensation fiable en cas d’échec, une purge des fichiers orphelins et une politique de conservation. Ajouter une vérification antivirus avant ouverture à des documents sensibles; cette étape est déjà mentionnée comme future dans le guide de déploiement.

### P1 — Incohérence potentielle dans la migration initiale users/cabinets

`database/migrations/0001_01_01_000000_create_users_table.php` crée `cabinets` avant `users` puis ajoute dans `users` un champ `statut_cabinet`, alors que le schéma cabinet possède déjà `statut_cabinet` et que le modèle utilisateur ne déclare pas ce champ dans ses attributs. Cela ressemble à un reste de conception antérieure. Confirmer le schéma réel attendu et tester un déploiement propre depuis une base vide avant toute livraison.

### P2 — Fonctions documentées mais absentes ou incomplètes

- **2FA :** des colonnes `two_factor_secret` et `two_factor_recovery_codes` existent, mais aucune implémentation 2FA n’apparaît dans les parcours/références inspectés.
- **Synchronisation calendrier :** `CalendarSyncService`, jobs et provider nul structurent l’intégration; aucun flux OAuth/callback ou fournisseur Google/Microsoft réel n’est exposé par les routes examinées.
- **Portail client et messagerie :** restent des phases futures de la roadmap.
- **Dashboard et rapports avancés :** le tableau de bord est encore une vue générique; les rapports existants couvrent surtout retards et situation client.
- **Intégrations paiement :** Stripe/PayPal apparaissent comme cibles d’architecture; le flux constaté porte sur la saisie de paiements.

Les modules à venir doivent rester présentés comme roadmap et ne pas être confondus avec des capacités disponibles en production.

### P2 — Robustesse et exploitation

- Le README annonce l’exécution de `php artisan test`, Pint et le build frontend; aucune preuve de leur exécution n’a été collectée pour ce rapport.
- La feuille de route vise une couverture supérieure à 80 %, des tests navigateur et CI/CD, mais leur présence ou leur résultat n’a pas été établi ici.
- Pas de politique antivirus implémentée visible; observabilité et alertes restent à confirmer au-delà des logs et audit logs.
- Les sauvegardes sont décrites comme tâches opératoires Railway; il faut vérifier leur exécution et faire des restaurations d’essai.
- Les domaines d’administration reposent principalement sur des gates/policies; les middlewares `role` et `permission` sont déclarés mais les routes ne semblent pas les utiliser directement. C’est acceptable si l’autorisation est appliquée à chaque méthode Livewire, ce que les tests doivent continuer à couvrir.

## Tests et qualité

Le dépôt contient des tests de base, authentification, accès tenant, administration, clients/documents, dossiers/documents, agenda, facturation, paiements, rapports comptables et configuration de déploiement. Cette organisation par domaine est un bon point et inclut plusieurs scénarios d’accès inter-cabinet.

Le présent rapport n’exécute aucun test et ne conclut donc pas à leur réussite. Les commandes documentées à exécuter dans `wapi/` sont `php artisan test`, `vendor/bin/pint --test` et `npm.cmd run build`. Pour compléter l’évaluation, ajouter un contrôle d’analyse statique PHP, un contrôle de couverture, et un workflow CI qui exécute ces commandes sur chaque changement.

## Plan d’action recommandé

1. **Avant préproduction :** exécuter migrations depuis zéro, tests complets, Pint et build; confirmer les environnements réellement utilisés et supprimer les champs obsolètes après validation du schéma cible.
2. **Sécurité des données :** tests systématiques d’isolation et de relations entre tenants; contrôles centralisés dans services; clarifier et imposer les règles de confidentialité des documents.
3. **Webhook et déploiement :** validation officielle Twilio obligatoire en production; vérifier proxies, hôtes, cookies, CSP et configuration réelle Railway.
4. **Fichiers :** stratégie de compensation, purge, conservation, sauvegarde et scan antivirus; test de restauration base + documents.
5. **Produit :** terminer 2FA et fournisseurs calendrier, ou ajuster les objectifs et l’interface pour signaler clairement ce qui est indisponible.
6. **Exploitation :** CI/CD, analyse statique, observabilité, alertes et tests de restauration avec preuve datée.

## Conclusion

Wapi dispose déjà d’un socle fonctionnel riche et d’une structuration orientée métier adaptée à son domaine. Les autorisations par policy, l’audit des actions sensibles, les téléchargements protégés, les UUID et les tests feature constituent de bons mécanismes de départ. Le risque principal tient à la dépendance aux vérifications tenant dispersées et à la différence entre certains réglages de sécurité documentés et leurs valeurs effectives. La prochaine étape la plus utile est une campagne de tests de sécurité multi-tenant et de déploiement propre, avant d’activer des données juridiques réelles.
