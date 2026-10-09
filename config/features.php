<?php

return [
    /*
     | Module « Projets & tâches ». Tant qu'il n'est pas ouvert (FEATURE_PROJETS_TACHES=true dans le
     | .env), seuls les administrateurs peuvent y accéder, par l'adresse directe : aucune entrée de
     | menu n'est affichée.
     */
    'projets_taches' => (bool) env('FEATURE_PROJETS_TACHES', false),
];
