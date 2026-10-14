<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mode d'affichage de l'interface : « clair » (défaut) ou « sombre ».
        Schema::table('users', function (Blueprint $table) {
            $table->string('mode', 10)->default('clair')->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};
