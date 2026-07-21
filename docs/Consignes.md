# Consignes

Lorsque cela n'est pas explicitement défini dans une tâche, appliquer systématiquement les consignes ci-dessous.

- Respecter les conventions Laravel pour les identifiants et clés étrangères avant toute migration.
- Utiliser UUID pour les clés primaires applicatives.
- Utiliser `cabinet_id` pour l'isolation multi-tenant.
- Formaliser les règles multi-tenant avec Global Scopes, Policies, validation serveur et tests d'isolation.
- Remplacer les suppressions métier par Soft Deletes ou actions explicites : suspendre, archiver, clôturer.
- Ajouter les audit logs pour les actions sensibles.
- Prévoir invitations utilisateurs, affectations dossier/utilisateur, parties adverses, time tracking et tokens de portail client.
- Ne jamais versionner de secrets, mots de passe, tokens ou clés API.
