<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projets', function (Blueprint $table) {
            if (!Schema::hasColumn('projets', 'couleur')) {
                $table->string('couleur', 7)->default('#4263eb')->after('description');
            }
            if (!Schema::hasColumn('projets', 'icone')) {
                $table->string('icone', 40)->default('folder')->after('couleur');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projets', function (Blueprint $table) {
            foreach (['icone', 'couleur'] as $col) {
                if (Schema::hasColumn('projets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
