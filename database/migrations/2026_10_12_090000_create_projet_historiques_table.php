<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Journal d'un projet : changements du projet lui-même et de ses tâches.
        Schema::create('projet_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Pas de clé étrangère : la ligne survit à la suppression de la tâche (libellé conservé).
            $table->unsignedBigInteger('tache_id')->nullable();
            $table->string('type', 12); // projet | tache
            $table->string('libelle', 500);
            $table->timestamp('created_at')->nullable();

            $table->index(['projet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projet_historiques');
    }
};
