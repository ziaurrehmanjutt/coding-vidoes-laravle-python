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
        Schema::create('video_settings', function (Blueprint $table) {
            $table->id();
            $table->string('voice');
            $table->smallInteger('speech_rate');
            $table->smallInteger('speech_pitch');
            $table->smallInteger('speech_volume');
            $table->boolean('narrate_question');
            $table->boolean('narrate_explanation');
            $table->boolean('tick_sound');
            $table->unsignedTinyInteger('think_seconds');
            $table->string('theme');
            $table->string('channel_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_settings');
    }
};
