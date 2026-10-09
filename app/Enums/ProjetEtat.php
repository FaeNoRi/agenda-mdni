<?php

namespace App\Enums;

/** États d'un projet (saisis par le référent ; l'application avertit en cas d'incohérence). */
enum ProjetEtat: string
{
    case EnAttente = 'en_attente';
    case EnCours = 'en_cours';
    case AValider = 'a_valider';
    case Termine = 'termine';
    case Annule = 'annule';
    case Bloque = 'bloque';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::EnCours => 'En cours',
            self::AValider => 'À valider',
            self::Termine => 'Terminé',
            self::Annule => 'Annulé',
            self::Bloque => 'Bloqué',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::EnAttente => '#f59f00',
            self::EnCours => '#4299e1',
            self::AValider => '#ae3ec9',
            self::Termine => '#2fb344',
            self::Annule => '#182433',
            self::Bloque => '#d63939',
        };
    }

    /** Nom de l'icône (voir App\Support\Icones). */
    public function icone(): string
    {
        return match ($this) {
            self::EnAttente => 'hourglass',
            self::EnCours => 'player-play',
            self::AValider => 'eye-check',
            self::Termine => 'check',
            self::Annule => 'x',
            self::Bloque => 'hand-stop',
        };
    }

    public function estOuvert(): bool
    {
        return !in_array($this, [self::Termine, self::Annule], true);
    }

    public function exigeRaison(): bool
    {
        return in_array($this, [self::EnAttente, self::Bloque], true);
    }
}
