<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
        });

        // Update existing media to associate with the user's default project
        // This ensures backward compatibility
        DB::statement('
            UPDATE media 
            SET project_id = (
                SELECT p.id 
                FROM projects p 
                WHERE p.user_id = media.user_id 
                AND p.is_default = true 
                LIMIT 1
            ) 
            WHERE project_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
