<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('video_settings', function (Blueprint $table) {
            $table->boolean('hook_line')->default(true);
            $table->boolean('mascot')->default(true);
            $table->boolean('reveal_effects')->default(true);
            $table->boolean('sound_effects')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_settings', function (Blueprint $table) {
            $table->dropColumn(['hook_line', 'mascot', 'reveal_effects', 'sound_effects']);
        });
    }
};
