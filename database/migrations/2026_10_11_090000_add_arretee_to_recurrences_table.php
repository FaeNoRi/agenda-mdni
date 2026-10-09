<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurrences', function (Blueprint $table) {
            // Annuler « toutes les occurrences » arrête la série sans toucher à sa date de fin.
            $table->boolean('arretee')->default(false)->after('date_fin');
        });
    }

    public function down(): void
    {
        Schema::table('recurrences', function (Blueprint $table) {
            $table->dropColumn('arretee');
        });
    }
};
