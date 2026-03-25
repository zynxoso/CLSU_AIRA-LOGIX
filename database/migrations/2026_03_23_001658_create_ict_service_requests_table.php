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
        Schema::create('ict_service_requests', function (Blueprint $table) {
            $table->id();
            
            // Meta & Control
            $table->string('control_no')->nullable()->unique();
            $table->string('timestamp_str')->nullable(); // Original timestamp from log
            $table->string('client_feedback_no')->nullable();

            // Client Info (Widened for encryption)
            $table->text('name')->nullable();
            $table->string('name_index')->nullable()->index();
            $table->text('position')->nullable();
            $table->text('office_unit')->nullable();
            $table->text('contact_no')->nullable();

            // Request Details
            $table->timestamp('date_of_request')->nullable();
            $table->timestamp('requested_completion_date')->nullable();
            $table->string('request_type')->nullable()->index();
            $table->text('location_venue')->nullable();
            $table->longText('request_description')->nullable();

            // Action & Personnel
            $table->text('received_by')->nullable();
            $table->timestamp('receive_date_time')->nullable();
            $table->longText('action_taken')->nullable();
            $table->longText('recommendation_conclusion')->nullable();
            $table->string('status')->nullable()->index();

            // Completion tracking
            $table->timestamp('date_time_started')->nullable();
            $table->timestamp('date_time_completed')->nullable();
            $table->text('conducted_by')->nullable();
            $table->text('noted_by')->nullable();

            $table->timestamps();
            
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ict_service_requests');
    }
};
