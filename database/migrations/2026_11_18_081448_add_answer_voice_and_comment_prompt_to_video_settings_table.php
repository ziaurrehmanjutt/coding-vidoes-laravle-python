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
            $table->string('answer_voice')->nullable()->default('en-US-GuyNeural')->after('voice');
            $table->boolean('comment_prompt')->default(true)->after('tick_sound');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_settings', function (Blueprint $table) {
            $table->dropColumn(['answer_voice', 'comment_prompt']);
        });
    }
};
