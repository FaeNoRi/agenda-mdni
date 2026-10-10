<?php

return [
    /*
     | Module « Projets & tâches » (avec ses notifications et la cloche). Ouvert à tous les utilisateurs.
     | Pour le refermer en cas de besoin (réservé aux administrateurs, sans entrée de menu) :
     | FEATURE_PROJETS_TACHES=false dans le .env.
     */
    'projets_taches' => (bool) env('FEATURE_PROJETS_TACHES', true),
];
