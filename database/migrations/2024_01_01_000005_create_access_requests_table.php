<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('requester_id');
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->json('payload');
            $table->text('justification')->nullable();
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('requester_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_requests');
    }
};
