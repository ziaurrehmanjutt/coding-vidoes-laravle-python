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
        Schema::table('questions', function (Blueprint $table) {
            $table->string('series')->nullable()->after('category_id');
            $table->unsignedSmallInteger('series_number')->nullable()->after('series');
            $table->string('upload_title', 100)->nullable();
            $table->text('upload_description')->nullable();
            $table->json('hashtags')->nullable();

            $table->unique(['series', 'series_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique(['series', 'series_number']);
            $table->dropColumn(['series', 'series_number', 'upload_title', 'upload_description', 'hashtags']);
        });
    }
};
