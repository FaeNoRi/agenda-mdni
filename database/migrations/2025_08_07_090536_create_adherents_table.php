<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('adherents', function (Blueprint $table) {
            $table->id();
            $table->string('nom_adh');
            $table->string('situation_adh');
            $table->string('dom_adh');
            $table->string('photo_adh')->nullable();
            $table->string('type_adh');
            $table->date('date_adh');
            $table->boolean('isCGU')->default(false);
            $table->boolean('isPresent')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adherents');
    }
};
