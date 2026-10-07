# Agenda MDNI — « Floppy Bord » (guide de contribution)

Agenda de l'association MDNI Calaisis (événements, salles, animateurs, congés, horaires). Application **Laravel 12**,
Blade + Bootstrap 5 / thème **Tabler** + Alpine.js, FullCalendar, Yajra DataTables. Dépôt :
`github.com/FaeNoRi/agenda-mdni` (branche `main`). Outil frère : « Floppy là » (liste de présence, lien dans la barre de navigation).
Tout est **en français** (interface, commentaires, messages de commit).

## Environnement local (Windows / Laragon)

- Le `php` du PATH est 8.2 : **utiliser PHP 8.4** — `C:\laragon\bin\php\php-8.4.7-nts-Win32-vs17-x64\php.exe`.
- Tests : `php artisan test` (PHPUnit, SQLite en mémoire, ~20 s). Tous les tests doivent passer avant tout commit.
- Le `.env` du dépôt = **configuration de production** (MySQL OVH, injoignable depuis le poste). Ne jamais lancer de commande
  destructive (`migrate:fresh`…) dessus. **Ne jamais afficher de valeur du `.env`** (ni `cat`, ni `Read`, ni `grep` sans `-o`) :
  uniquement les noms de variables (`grep -oE "^[A-Z_]+=" .env`).

## Vérifier un changement en local (rituel)

1. Sauvegarder `.env`, le remplacer par un `.env` temporaire (`APP_ENV=local`, `DB_CONNECTION=sqlite` + fichier
   `database/session-temp.sqlite`, `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=log`,
   même `APP_KEY`), puis `artisan migrate --force` et semer quelques données via `artisan tinker`.
2. Lancer `artisan serve --port=8391` (via `.claude/launch.json` + `preview_start`) et piloter le navigateur intégré.
   Un utilisateur déjà connecté est redirigé par `/login` : se déconnecter avant de changer de compte.
3. **Tout restaurer** : `.env` d'origine, supprimer le sqlite/la sauvegarde/le `launch.json`, `artisan view:clear`, relancer les tests.

## Déploiement (aucune automatisation)

Pas d'accès SSH, **FTP uniquement**, et l'utilisateur ne modifie plus jamais le serveur en direct. Après chaque changement :
commit + push sur `main`, puis **donner la liste exacte des fichiers à envoyer en FTP**. Si une migration est nécessaire,
fournir le SQL équivalent (phpMyAdmin) et rappeler de l'appliquer **avant** l'envoi des fichiers. Ne pas toucher à
`.vscode/sftp.json` / upload automatique (supprimé volontairement).

## Conventions et pièges propres au projet

- **Couleur de thème par utilisateur** : `users.theme` → `--tblr-primary` (`layouts/app.blade.php`). Utiliser `btn-primary` /
  `btn-outline-primary`, jamais une couleur fixe pour l'accent de l'interface.
- **Icônes** : la police d'icônes Tabler n'est **pas** chargée → SVG en ligne (`resources/views/dashboard/partials/icon-*.blade.php`).
- **Pas de build front en production** : préférer utilitaires Bootstrap/Tabler ou CSS dans la vue plutôt que de nouvelles classes Tailwind.
- **« Toute l'équipe »** = un seul utilisateur d'`id = 0` (pas un envoi à chaque membre) ; `is_equipe` identifie les vrais membres.
  Dans les formulaires, l'utilisateur 16 et la salle 0 sont les premières options par défaut.
- **Types d'événement** : couleurs dans `EvenementController::EVENT_TYPE_COLORS` ; l'annulation est stockée `Annule`
  (le modèle accepte aussi `Annulé`, voir `Evenements::isCancelled()`). Un événement annulé n'affiche aucune pastille et ne génère aucun conflit.
- **Formulaire événement** (`evenements/_form.blade.php`) : création et modification passent par `validateEvent()` +
  `syncRelations()` (lignes vides ignorées, doublons fusionnés). La disponibilité (`/evenements/disponibilites`) exclut l'événement édité (`exclude`).
- **Pastilles** (participation / photos / objets à remettre) : partial `dashboard/partials/event-flags.blade.php`, couleur du type,
  rouge si un objet reste « A faire ». CSS dans `dashboard/dashboard.blade.php`.
- **E-mails d'événement** : `SendEventEmailService` (situations created/updated/cancelled/added/removed/reinstated, ne lève jamais
  d'exception), ICS via `App\Services\IcsBuilder` (RFC 5545 : METHOD, SEQUENCE, UID stable), envoi par Brevo.
- Modales du dashboard : pas de `modal-blur` (flou GPU coûteux en 2K).
- Tests : chaque correctif ou fonctionnalité s'accompagne d'un test (`tests/Feature`).
