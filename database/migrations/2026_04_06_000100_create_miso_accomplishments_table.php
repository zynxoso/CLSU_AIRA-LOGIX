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
        Schema::create('miso_accomplishments', function (Blueprint $table) {
            $table->id();
            $table->string('category')->index();
            $table->string('source_file')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->string('source_hash')->unique();
            $table->string('record_no')->nullable();

            $table->text('project_title')->nullable();
            $table->text('project_lead')->nullable();
            $table->longText('project_members')->nullable();
            $table->string('budget_cost')->nullable();
            $table->text('implementing_unit')->nullable();
            $table->longText('target_activities')->nullable();
            $table->string('intended_duration')->nullable();
            $table->string('start_date')->nullable();
            $table->string('target_end_date')->nullable();
            $table->string('reporting_period')->nullable()->index();
            $table->string('completion_percentage')->nullable();
            $table->string('overall_status')->nullable()->index();
            $table->longText('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('miso_accomplishments');
    }
};
