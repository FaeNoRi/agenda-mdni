<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module « Projets & tâches » : socle de données.
 * Les statuts sont stockés en texte (voir App\Enums\TacheStatut / ProjetEtat), pas en ENUM SQL,
 * pour pouvoir en ajouter sans migration.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('projets', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->text('description')->nullable();
            $table->date('date_limite')->nullable();
            $table->string('etat', 20)->default('en_attente');
            $table->text('raison')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('etat');
            $table->index('date_limite');
        });

        // Personnes d'un projet : plusieurs référents possibles, les autres sont "impliquées".
        Schema::create('projet_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 10)->default('implique'); // referent | implique
            $table->timestamps();

            $table->unique(['projet_id', 'user_id']);
        });

        Schema::create('recurrences', function (Blueprint $table) {
            $table->id();
            $table->string('frequence', 12); // hebdomadaire | mensuelle
            $table->date('date_fin');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('taches', function (Blueprint $table) {
            $table->id();
            // Supprimer un projet supprime ses tâches (l'interface demandera confirmation).
            $table->foreignId('projet_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('titre', 190);
            $table->text('details')->nullable();
            $table->date('date_limite');
            $table->string('statut', 20)->default('a_faire');
            $table->text('raison')->nullable();
            $table->foreignId('recurrence_id')->nullable()->constrained('recurrences')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('statut');
            $table->index('date_limite');
        });

        Schema::create('tache_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tache_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tache_id', 'user_id']);
        });

        // Historique automatique des statuts d'une tâche (« BL en cours le 01/01/2026 »).
        Schema::create('tache_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tache_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('statut', 20);
            $table->text('raison')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // Commentaires et liens utiles : communs aux projets et aux tâches.
        Schema::create('commentaires', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('contenu');
            $table->timestamps();
        });

        Schema::create('liens', function (Blueprint $table) {
            $table->id();
            $table->morphs('lienable');
            $table->string('libelle', 150)->nullable();
            $table->string('url', 500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liens');
        Schema::dropIfExists('commentaires');
        Schema::dropIfExists('tache_historiques');
        Schema::dropIfExists('tache_user');
        Schema::dropIfExists('taches');
        Schema::dropIfExists('recurrences');
        Schema::dropIfExists('projet_user');
        Schema::dropIfExists('projets');
    }
};
