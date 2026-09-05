<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('horaire_jours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horaire_id')->constrained('horaires')->onDelete('cascade');
            $table->string('jour');
            $table->time('debut')->nullable();
            $table->time('pause_debut')->nullable();
            $table->time('pause_fin')->nullable();
            $table->time('fin')->nullable();
            $table->boolean('matin')->default(true);
            $table->boolean('aprem')->default(true);
            $table->boolean('repos')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horaire_jours');
    }
};

