<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le modèle Evenements::objets() déclare withPivot('etat') et EvenementController
 * écrit systématiquement cette valeur ('A faire' / 'Fait') sur la table pivot, mais
 * aucune migration ne créait la colonne (probablement ajoutée manuellement en base).
 * Comble l'écart pour toute base fraîche ; ne fait rien si la colonne existe déjà.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('evenement_objets', function (Blueprint $table) {
            if (!Schema::hasColumn('evenement_objets', 'etat')) {
                $table->string('etat', 25)->default('A faire')->after('objet_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('evenement_objets', function (Blueprint $table) {
            if (Schema::hasColumn('evenement_objets', 'etat')) {
                $table->dropColumn('etat');
            }
        });
    }
};
