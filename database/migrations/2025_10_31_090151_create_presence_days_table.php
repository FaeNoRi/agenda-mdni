<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('presence_days', function (Blueprint $table) {
            $table->id();

            // Référence à vos adhérents (table "adherents")
            $table->foreignId('adherent_id')
                ->constrained('adherents')
                ->cascadeOnDelete();

            // Jour de présence (au fuseau Europe/Paris côté app)
            $table->date('date');

            // Optionnel : 1er check-in horodaté (informatif)
            $table->timestamp('first_checkin_at')->nullable();

            // Optionnel : source de l’action (ex: 'presence_ui')
            $table->string('source', 32)->nullable();

            $table->timestamps();

            // 1 entrée max par (adherent, jour)
            $table->unique(['adherent_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presence_days');
    }
};
