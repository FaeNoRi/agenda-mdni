<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evenement_materiels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evenement_id')->constrained()->onDelete('cascade');
            $table->foreignId('materiel_id')->constrained()->onDelete('cascade');

            $table->unsignedInteger('quantite');

            $table->timestamps();
            $table->unique(['evenement_id', 'materiel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenement_materiels');
    }
};