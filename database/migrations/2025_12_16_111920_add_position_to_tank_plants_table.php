<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tank_plants', function (Blueprint $table) {
            $table->string('position', 255)->nullable()->after('planted_at');
        });
    }

    public function down(): void
    {
        Schema::table('tank_plants', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
