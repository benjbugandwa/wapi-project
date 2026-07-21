# UI Guidelines

## Préférences

- Toujours utiliser les formulaires modales (pop-ups) scrollables pour les formulaire d'insertion/édition;
- Toujours utiliser Laravel-Notify pour les notification des actions utilisateur;
- Utiliser un loading spinner à chaque fois que c'est nécessaire;
- Toujours demander une confirmation utilisateur pour les actions critique; ex: Suspendre un cabinet, supprimer un dossier, etc.

## Application obligatoire

Pour tous les modules existants et futurs :

- Les formulaires de creation et de modification doivent etre presentes dans des modales scrollables.
- Les formulaires longs ouverts depuis une route dediee doivent utiliser une modale persistante scrollable avec action d'annulation explicite.
- Les retours d'actions utilisateur doivent passer par Laravel-Notify.
- Les actions longues ou asynchrones doivent afficher un spinner et desactiver l'action principale pendant le traitement.
- Les actions critiques doivent demander une confirmation explicite avant execution.
- Les logos doivent provenir de `C:\xampp\htdocs\wapi-project\wapi\public\images\logos`.

## Référence

Le thème global de l'application doit s'inspirer de "free-dark-admin-dashboards", dont voici le lien:
https://www.figma.com/fr-fr/communaute/file/1325597018063319916/free-dark-admin-dashboards

## Logos

- Tous les logos à utiliser se trouve dans le dossier: "C:\xampp\htdocs\wapi-project\wapi\public\images\logos"
- favicon: "C:\xampp\htdocs\wapi-project\wapi\public\images\logos\favicon-16x16.png"
- Toujours choisir le type de logo le mieux Adapté selon le contexte

## Design

- Minimaliste.
- Professionnel.
- Responsive.
- Accessible.
- Adapté à un SaaS de gestion pour cabinets d'avocats : sobre, lisible, dense mais clair.

## Couleurs

- Primaire.
- Secondaire.
- Succès.
- Erreur.
- Warning.
- Info.

Les couleurs finales doivent respecter les contrastes d'accessibilité.

## Typographie

- Police lisible pour un usage professionnel prolongé.
- Tailles cohérentes entre dashboards, formulaires, tableaux et détails de dossier.
- Poids typographiques réservés à la hiérarchie d'information.

## Components

- Buttons.
- Cards.
- Tables.
- Forms.
- Badges.
- Modals.
- Drawer.
- Sidebar.
- Navbar.
- Pagination.
- Search.
- Toast.
- Spinner.

## Dashboard

- KPIs.
- Graphiques.
- Calendrier.
- Notifications.
- Tâches.
- Factures.
- Alertes de délais.
- Dossiers récents.

## Responsive

- Desktop.
- Tablet.
- Mobile.

## Accessibilité

- Contraste suffisant.
- Navigation clavier.
- États focus visibles.
- ARIA lorsque nécessaire.
- Textes d'erreur associés aux champs.
