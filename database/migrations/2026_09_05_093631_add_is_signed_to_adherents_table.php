<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le modèle Adherents et ReglementSignatureController utilisent `isSigned` depuis
 * la mise en place de la signature du règlement intérieur, mais aucune migration
 * ne créait cette colonne (probablement ajoutée manuellement en base). Comble
 * l'écart pour toute base fraîche ; ne fait rien si la colonne existe déjà.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('adherents', function (Blueprint $table) {
            if (!Schema::hasColumn('adherents', 'isSigned')) {
                $table->boolean('isSigned')->default(false)->after('isPresent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('adherents', function (Blueprint $table) {
            if (Schema::hasColumn('adherents', 'isSigned')) {
                $table->dropColumn('isSigned');
            }
        });
    }
};
