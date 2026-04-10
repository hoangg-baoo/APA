<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identify_regions', function (Blueprint $table) {
            if (!Schema::hasColumn('identify_regions', 'proposal_source')) {
                $table->string('proposal_source', 20)
                    ->default('manual')
                    ->after('match_results');
            }

            if (!Schema::hasColumn('identify_regions', 'proposal_score')) {
                $table->float('proposal_score')
                    ->nullable()
                    ->after('proposal_source');
            }

            $table->index(['identify_session_id', 'proposal_source'], 'idx_identify_regions_session_source');
        });
    }

    public function down(): void
    {
        Schema::table('identify_regions', function (Blueprint $table) {
            $table->dropIndex('idx_identify_regions_session_source');

            if (Schema::hasColumn('identify_regions', 'proposal_score')) {
                $table->dropColumn('proposal_score');
            }

            if (Schema::hasColumn('identify_regions', 'proposal_source')) {
                $table->dropColumn('proposal_source');
            }
        });
    }
};