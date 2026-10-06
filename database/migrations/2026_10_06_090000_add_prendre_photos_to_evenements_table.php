<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            if (!Schema::hasColumn('evenements', 'prendre_photos')) {
                $table->boolean('prendre_photos')->default(false)->after('objet');
            }
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            if (Schema::hasColumn('evenements', 'prendre_photos')) {
                $table->dropColumn('prendre_photos');
            }
        });
    }
};
