<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('salles', function (Blueprint $table) {
            $table->id();
            $table->string('nom_salle', 150);
            $table->string('type_salle', 150);
        });
    }

    public function down(): void {
        Schema::dropIfExists('salles');
    }
};
