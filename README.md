# Agenda MDNI

Application interne de la MDNI Calaisis (Maison des Nouveaux Indépendants) : agenda partagé des événements/ateliers, gestion des salles, matériels et objets associés, suivi de présence des adhérents, congés et changements d'horaires de l'équipe, signature électronique du règlement intérieur.

## Stack technique

- **Backend** : Laravel 12 (PHP 8.4)
- **Frontend** : Blade, Bootstrap 5, Alpine.js, DataTables (Yajra), FullCalendar
- **Build** : Vite
- **Emails transactionnels** : API Brevo
- **PDF** : FPDI (génération du règlement intérieur signé)

## Installation locale

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Renseigner dans `.env` : la connexion base de données, `ADMIN_PASSWORD_HASH` (hash bcrypt du mot de passe administrateur utilisé pour les actions sensibles côté présence/règlement), et les identifiants Brevo (`BREVO_API_KEY`, `BREVO_SENDER_EMAIL`, `BREVO_SENDER_NAME`) si l'envoi d'emails est nécessaire.

```bash
php artisan migrate
npm run dev   # ou: npm run build
```

## Tests

La suite de tests utilise SQLite en mémoire (voir `phpunit.xml`), aucune configuration supplémentaire n'est nécessaire :

```bash
php artisan test
```

## Fonctionnalités principales

- **Événements** : création/édition avec animateurs, salles, matériels (avec quantité) et objets (avec état) associés ; détection en temps réel des indisponibilités (animateur/salle déjà occupé sur un autre événement, en congé, ou avec un horaire modifié) lors de la saisie des dates.
- **Dashboard** : vue calendrier et vue cartes filtrables (date, type, salle, animateur), avec notifications sur les changements d'horaires et congés à venir.
- **Présence** : pointage des adhérents (présent/absent), historique quotidien, statistiques de fréquentation exportables par email (CSV).
- **Règlement intérieur** : lecture du PDF, signature manuscrite, génération du PDF signé et envoi automatique par email.
- **Ressources** : gestion des salles, matériels, objets, adhérents, congés et changements d'horaires de l'équipe.
- **Export** : export Excel générique par modèle avec sélection de colonnes et filtres.

## Notes pour la suite

Plusieurs colonnes utilisées par le code (`users.is_email`, `users.theme`, `adherents.isSigned`, `evenement_objets.etat`) n'étaient documentées par aucune migration avant l'audit de septembre 2026 — probablement ajoutées manuellement en base de production au fil du temps. Des migrations correctives (idempotentes, sans impact si la colonne existe déjà) ont été ajoutées pour que toute base fraîche corresponde au comportement réel de l'application.
