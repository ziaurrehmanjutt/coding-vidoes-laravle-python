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
            $table->string('language')->default('python')->index()->after('title');
            $table->foreignId('category_id')->nullable()->after('language')->constrained()->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable()->index();
            $table->string('upload_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['language', 'uploaded_at', 'upload_url']);
        });
    }
};
