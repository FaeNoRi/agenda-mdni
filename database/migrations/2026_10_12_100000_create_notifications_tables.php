<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Notifications internes consultables dans le tiroir (cloche de la barre de navigation).
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('categorie', 12); // tache | projet | evenement | admin
            $table->string('titre', 190);
            $table->text('contexte')->nullable();      // **gras** accepté
            $table->string('projet_nom', 150)->nullable();
            $table->date('echeance')->nullable();      // sert au tri « urgence »
            $table->string('jalon', 20)->nullable();   // « J-3 », « 4 j de retard »…
            $table->string('url', 255)->nullable();
            $table->string('cle', 120)->nullable();    // anti-doublon (rappels)
            $table->timestamp('vue_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'vue_at']);
            $table->unique(['user_id', 'cle']);
        });

        // Une ligne par choix d'un utilisateur pour un type désactivable (absente = active).
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('app_notifications');
    }
};
