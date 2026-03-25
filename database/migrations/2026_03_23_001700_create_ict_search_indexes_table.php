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
        Schema::create('ict_search_indexes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ict_service_request_id')->constrained()->cascadeOnDelete();
            $table->string('hash')->index(); // The hash of an individual word
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ict_search_indexes');
    }
};
