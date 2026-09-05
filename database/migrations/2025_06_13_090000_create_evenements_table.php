<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evenements', function (Blueprint $table) {
            $table->id();
            $table->string('nom_event', 150);
            $table->string('type_event', 150);
            $table->string('type_public', 35)->default('Autres');
            $table->text('desc_event');
            $table->string('commanditaire_event', 150);
            $table->integer('nbpart');
            $table->string('facture', 10);
            $table->string('numfact', 25)->nullable();
            $table->string('devis', 10);
            $table->string('numdevis', 25)->nullable();
            $table->string('reglement', 10)->default('Non');
            $table->string('type_reglement', 10)->nullable();
            $table->string('num_reglement', 25)->nullable();
            $table->string('objet', 10);
            $table->string('etat_objet', 25)->nullable();
            $table->string('auteur', 100);
            $table->dateTime('date_heure_debut');
            $table->dateTime('date_heure_fin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements');
    }
};