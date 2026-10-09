<?php

namespace App\Support;

/**
 * Catalogue des notifications internes (décisions du 09/10/2026).
 * « imposée » : toujours envoyée ; sinon l'utilisateur peut la désactiver dans son Profil.
 * Catégories (couleurs du tiroir) : tache, projet, evenement, admin.
 */
class NotificationTypes
{
    public const CATEGORIES = [
        'tache' => ['Tâches', '#4299e1'],
        'projet' => ['Projets', '#f59f00'],
        'evenement' => ['Événements', '#2fb344'],
        'admin' => ['Administration', '#ae3ec9'],
    ];

    /** type => [catégorie, libellé (Profil), icône, imposée] */
    private const TYPES = [
        'tache.assignee' => ['tache', "Une tâche vous est confiée", 'user', true],
        'tache.retiree' => ['tache', "Vous n'êtes plus responsable d'une tâche", 'user', true],
        'tache.statut' => ['tache', "Le statut d'une tâche de votre projet change", 'refresh', true],
        'tache.a_valider' => ['tache', "Une tâche attend votre validation", 'eye', true],
        'tache.terminee' => ['tache', "Une tâche de votre projet est validée ou terminée", 'check', true],
        'tache.rappel' => ['tache', "Échéance d'une tâche (J-15, J-7, J-3 et jour J)", 'clock', true],
        'tache.commentaire' => ['tache', "Nouveau commentaire sur une tâche", 'msg', false],
        'tache.modifiee' => ['tache', "Une de vos tâches est modifiée", 'edit', false],
        'tache.supprimee' => ['tache', "Une de vos tâches est supprimée", 'trash', false],
        'tache.retard' => ['tache', "Une tâche est en retard", 'alert', false],

        'projet.a_valider' => ['projet', "Un projet attend votre validation", 'eye', true],
        'projet.rappel' => ['projet', "Échéance d'un projet (J-15, J-7, J-3 et jour J)", 'clock', true],
        'projet.taches_terminees' => ['projet', "Toutes les tâches d'un projet sont terminées", 'check', true],
        'projet.commentaire' => ['projet', "Nouveau commentaire sur un projet", 'msg', false],
        'projet.etat' => ['projet', "L'état d'un projet change", 'flag', false],
        'projet.referent' => ['projet', "Vous devenez (ou cessez d'être) référent d'un projet", 'flag', false],
        'projet.supprime' => ['projet', "Un projet est supprimé", 'trash', false],

        'evenement.cree' => ['evenement', "Un événement est créé avec vous", 'cal', true],
        'evenement.modifie' => ['evenement', "Un de vos événements est modifié", 'cal', true],
        'evenement.annule' => ['evenement', "Un de vos événements est annulé ou rétabli", 'cal', true],
        'evenement.participation' => ['evenement', "Vous êtes ajouté à un événement ou retiré d'un événement", 'cal', true],
        'evenement.objets' => ['evenement', "Des objets restent à préparer pour un événement (J-7 et J-3)", 'pkg', true],
        'evenement.photos' => ['evenement', "Des photos sont à prendre lors d'un événement", 'cam', false],

        'admin.conflit' => ['admin', "Conflit de salle ou de personne à J-7 (administrateurs)", 'alert', false],
    ];

    public static function existe(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    public static function categorie(string $type): string
    {
        return self::TYPES[$type][0];
    }

    public static function libelle(string $type): string
    {
        return self::TYPES[$type][1];
    }

    public static function icone(string $type): string
    {
        return self::TYPES[$type][2];
    }

    public static function imposee(string $type): bool
    {
        return self::TYPES[$type][3];
    }

    /** @return list<string> */
    public static function desactivables(): array
    {
        return array_keys(array_filter(self::TYPES, fn ($t) => !$t[3]));
    }

    /** Types d'une catégorie, [type => [libellé, imposée]], pour la page Profil. */
    public static function parCategorie(string $categorie): array
    {
        $liste = [];
        foreach (self::TYPES as $type => [$cat, $libelle, , $imposee]) {
            if ($cat === $categorie) {
                $liste[$type] = [$libelle, $imposee];
            }
        }

        return $liste;
    }
}
