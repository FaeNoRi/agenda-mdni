<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('materiels', function (Blueprint $table) {
            $table->id();
            $table->string('nom_mat', 150);
            $table->integer('nb_stock');
        });
    }

    public function down(): void {
        Schema::dropIfExists('materiels');
    }
};

