<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

public function up()
{
    Schema::create('changement_horaires', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')
              ->constrained()
              ->onDelete('cascade');
        $table->string('type_chgmt', 10);
        $table->dateTime('old_start');
        $table->dateTime('old_end');
        $table->dateTime('new_start');
        $table->dateTime('new_end');
        $table->timestamps();
    });
}

    public function down()
    {
        Schema::dropIfExists('changement_horaires');
    }
};
