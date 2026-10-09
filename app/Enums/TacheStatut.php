<?php

namespace App\Enums;

/**
 * Statuts d'une tâche. Libellés au féminin (« une tâche terminée ») avec accord au nombre
 * pour le résumé d'un projet (« 5 terminées — 1 bloquée »).
 */
enum TacheStatut: string
{
    case AFaire = 'a_faire';
    case EnCours = 'en_cours';
    case AValider = 'a_valider';
    case Termine = 'termine';
    case EnAttente = 'en_attente';
    case Bloque = 'bloque';
    case Annule = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::AFaire => 'À faire',
            self::EnCours => 'En cours',
            self::AValider => 'À valider',
            self::Termine => 'Terminé',
            self::EnAttente => 'En attente',
            self::Bloque => 'Bloqué',
            self::Annule => 'Annulé',
        };
    }

    /** Libellé accordé pour « n tâche(s) » : 1 bloquée, 2 bloquées, 3 en cours. */
    public function accorde(int $n): string
    {
        $pluriel = $n > 1 ? 's' : '';

        return match ($this) {
            self::AFaire => 'à faire',
            self::EnCours => 'en cours',
            self::AValider => 'à valider',
            self::Termine => 'terminée'.$pluriel,
            self::EnAttente => 'en attente',
            self::Bloque => 'bloquée'.$pluriel,
            self::Annule => 'annulée'.$pluriel,
        };
    }

    /** Couleur Tabler du statut (carrés de statut des maquettes). */
    public function couleur(): string
    {
        return match ($this) {
            self::AFaire => '#667382',
            self::EnCours => '#4299e1',
            self::AValider => '#ae3ec9',
            self::Termine => '#2fb344',
            self::EnAttente => '#f59f00',
            self::Bloque => '#d63939',
            self::Annule => '#182433',
        };
    }

    /** Nom de l'icône (voir App\Support\Icones). */
    public function icone(): string
    {
        return match ($this) {
            self::AFaire => 'circle',
            self::EnCours => 'player-play',
            self::AValider => 'eye-check',
            self::Termine => 'check',
            self::EnAttente => 'hourglass',
            self::Bloque => 'hand-stop',
            self::Annule => 'x',
        };
    }

    /** Terminé ou annulé : plus rien à faire, donc jamais "en retard". */
    public function estOuvert(): bool
    {
        return !in_array($this, [self::Termine, self::Annule], true);
    }

    /** « En attente » et « Bloqué » doivent toujours être expliqués. */
    public function exigeRaison(): bool
    {
        return in_array($this, [self::EnAttente, self::Bloque], true);
    }

    /** Ordre d'affichage du résumé d'un projet. */
    public static function ordreResume(): array
    {
        return [self::Termine, self::EnCours, self::AValider, self::AFaire, self::EnAttente, self::Bloque, self::Annule];
    }
}
