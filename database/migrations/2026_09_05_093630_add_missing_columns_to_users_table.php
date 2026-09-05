<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le code applicatif (UserController, UserThemeController) utilise depuis longtemps
 * les colonnes `is_email` et `theme` sur `users`, mais aucune migration ne les créait
 * (probablement ajoutées manuellement en base à un moment donné). Cette migration
 * comble l'écart pour toute base fraîche (CI, nouvel environnement), et ne fait rien
 * si les colonnes existent déjà.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_email')) {
                $table->boolean('is_email')->default(false)->after('id_horaire');
            }

            if (!Schema::hasColumn('users', 'theme')) {
                $table->string('theme', 20)->default('blue')->after('is_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'theme')) {
                $table->dropColumn('theme');
            }

            if (Schema::hasColumn('users', 'is_email')) {
                $table->dropColumn('is_email');
            }
        });
    }
};
