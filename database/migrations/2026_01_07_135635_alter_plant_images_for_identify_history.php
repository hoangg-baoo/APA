<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plant_images', function (Blueprint $table) {

            // Identify history ownership
            $table->foreignId('user_id')->nullable()->after('id')
                ->constrained('users')->nullOnDelete()->cascadeOnUpdate();

            $table->foreignId('tank_id')->nullable()->after('user_id')
                ->constrained('tanks')->nullOnDelete()->cascadeOnUpdate();

            // library | identify
            $table->string('purpose', 20)->default('library')->after('tank_id');

            // Identify saved info
            $table->json('query_vector')->nullable()->after('feature_vector');
            $table->json('match_results')->nullable()->after('query_vector');
            $table->text('note')->nullable()->after('match_results');

            $table->index(['purpose']);
            $table->index(['user_id', 'created_at']);
            $table->index(['tank_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('plant_images', function (Blueprint $table) {
            $table->dropIndex(['purpose']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['tank_id', 'created_at']);

            $table->dropConstrainedForeignId('tank_id');
            $table->dropConstrainedForeignId('user_id');

            $table->dropColumn(['purpose', 'query_vector', 'match_results', 'note']);
        });
    }
};
