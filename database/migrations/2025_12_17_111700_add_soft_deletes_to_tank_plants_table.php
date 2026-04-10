<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tank_plants', function (Blueprint $table) {
            if (!Schema::hasColumn('tank_plants', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tank_plants', function (Blueprint $table) {
            if (Schema::hasColumn('tank_plants', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
