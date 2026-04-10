<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add strict size fields (safe even if rerun)
        Schema::table('tanks', function (Blueprint $table) {
            if (!Schema::hasColumn('tanks', 'length_cm')) {
                $table->unsignedSmallInteger('length_cm')->nullable()->after('size');
            }
            if (!Schema::hasColumn('tanks', 'width_cm')) {
                $table->unsignedSmallInteger('width_cm')->nullable()->after('length_cm');
            }
            if (!Schema::hasColumn('tanks', 'height_cm')) {
                $table->unsignedSmallInteger('height_cm')->nullable()->after('width_cm');
            }
        });

        // 2) Create new enum column co2_new (do NOT touch old co2 yet)
        if (!Schema::hasColumn('tanks', 'co2_new')) {
            Schema::table('tanks', function (Blueprint $table) {
                $table->enum('co2_new', ['none','liquid','diy','pressurized'])
                      ->default('none')
                      ->after('co2');
            });
        }

        // 3) Copy old boolean/int data into enum column
        // old co2: 1 => pressurized, else => none
        DB::statement("
            UPDATE tanks
            SET co2_new = CASE
                WHEN co2 = 1 THEN 'pressurized'
                ELSE 'none'
            END
        ");

        // 4) Drop old co2 column (boolean/int)
        if (Schema::hasColumn('tanks', 'co2')) {
            Schema::table('tanks', function (Blueprint $table) {
                $table->dropColumn('co2');
            });
        }

        // 5) Rename co2_new -> co2 (no DBAL needed)
        DB::statement("
            ALTER TABLE tanks
            CHANGE co2_new co2 ENUM('none','liquid','diy','pressurized')
            NOT NULL DEFAULT 'none'
        ");
    }

    public function down(): void
    {
        // Recreate old boolean co2_old
        if (!Schema::hasColumn('tanks', 'co2_old')) {
            Schema::table('tanks', function (Blueprint $table) {
                $table->tinyInteger('co2_old')->default(0)->after('co2');
            });
        }

        // Convert enum -> boolean (pressurized => 1 else 0)
        DB::statement("
            UPDATE tanks
            SET co2_old = CASE
                WHEN co2 = 'pressurized' THEN 1
                ELSE 0
            END
        ");

        // Drop enum co2
        if (Schema::hasColumn('tanks', 'co2')) {
            Schema::table('tanks', function (Blueprint $table) {
                $table->dropColumn('co2');
            });
        }

        // Rename co2_old -> co2
        DB::statement("ALTER TABLE tanks CHANGE co2_old co2 TINYINT(1) NOT NULL DEFAULT 0");

        // Drop size fields
        Schema::table('tanks', function (Blueprint $table) {
            if (Schema::hasColumn('tanks', 'height_cm')) $table->dropColumn('height_cm');
            if (Schema::hasColumn('tanks', 'width_cm')) $table->dropColumn('width_cm');
            if (Schema::hasColumn('tanks', 'length_cm')) $table->dropColumn('length_cm');
        });
    }
};
